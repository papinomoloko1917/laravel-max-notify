<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCameraEvent;
use App\Models\Camera;
use App\Models\CameraEvent;
use App\Services\CameraEventSnapshotStore;
use App\Services\DahuaClient;
use App\Services\NotificationWindow;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;
use UnexpectedValueException;

class DahuaWebhookController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        Request $request,
        DahuaClient $dahuaClient,
        CameraEventSnapshotStore $snapshotStore,
    ): Response {
        $username = $request->getUser();

        $password = $request->getPassword();

        $authorization = $request->header('Authorization');
        $authorizationScheme = is_string($authorization)
            ? strtolower(strtok($authorization, ' ') ?: 'unknown')
            : 'none';

        $rules = [
            'event' => ['required', 'in:ivs'],
            'channel' => ['required', 'integer', 'min:1'],
            'rule' => ['required', 'string', 'max:100'],
        ];

        $camera = null;

        if (is_string($authorization)) {
            if (! is_string($username) || ! is_string($password)) {
                Log::warning('Dahua webhook rejected', [
                    'reason' => 'credentials_missing_or_unsupported',
                    'authorization_scheme' => $authorizationScheme,
                ]);

                return $this->unauthorizedResponse();
            }

            $camera = Camera::query()
                ->where('webhook_username', $username)
                ->where('is_active', true)
                ->first();

            $storedPassword = $camera?->webhook_password;

            if (! is_string($storedPassword) || ! Hash::check($password, $storedPassword)) {
                Log::warning('Dahua webhook rejected', [
                    'reason' => 'credentials_invalid',
                    'authorization_scheme' => $authorizationScheme,
                ]);

                return $this->unauthorizedResponse();
            }
        } else {
            $cameraId = filter_var(
                $request->query('camera'),
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]],
            );
            $token = $request->query('token');

            if ($cameraId !== false && is_string($token)) {
                $camera = Camera::query()
                    ->whereKey($cameraId)
                    ->where('is_active', true)
                    ->first();
            }

            $storedToken = $camera?->webhook_token;

            if (! is_string($token) || ! is_string($storedToken) || ! hash_equals($storedToken, $token)) {
                $hasTokenCredentials = $cameraId !== false || is_string($token);

                Log::warning('Dahua webhook rejected', [
                    'reason' => $hasTokenCredentials ? 'credentials_invalid' : 'credentials_missing_or_unsupported',
                    'authorization_scheme' => $hasTokenCredentials ? 'token' : 'none',
                ]);

                return $this->unauthorizedResponse();
            }
        }

        $validator = Validator::make($request->query(), $rules);

        if ($validator->fails()) {
            Log::warning('Dahua webhook rejected', [
                'reason' => 'query_invalid',
                'invalid_fields' => array_keys($validator->errors()->messages()),
            ]);

            return response('Invalid webhook request', 400)
                ->header('Content-Type', 'text/plain');
        }

        $validated = $validator->validated();

        if ($camera->channel !== null && $camera->channel !== (int) $validated['channel']) {
            Log::warning('Dahua webhook rejected', [
                'reason' => 'channel_mismatch',
                'camera_id' => $camera->id,
                'configured_channel' => $camera->channel,
                'received_channel' => (int) $validated['channel'],
            ]);

            return response('Invalid webhook request', 400)
                ->header('Content-Type', 'text/plain');
        }

        $window = new NotificationWindow($camera->notify_from, $camera->notify_until);

        $isAllowed = $window->allows(now());

        if ($isAllowed === false) {
            return response('Ignored', 200)
                ->header('Content-Type', 'text/plain');
        }

        $fingerprint = implode('|', [
            (string) $camera->id,
            (string) $validated['channel'],
            (string) $validated['rule'],
        ]);

        $duplicateKey = 'dahua-event:' . hash('sha256', $fingerprint);

        $isFirstRequest = Cache::add(
            $duplicateKey,
            true,
            5,
        );

        if ($isFirstRequest === false) {
            return response('Ignored', 200)
                ->header('Content-Type', 'text/plain');
        }

        $ruleTitle = $camera->rules()
            ->where('code', $validated['rule'])
            ->value('title');

        $cameraEvent = CameraEvent::create([
            'camera_id' => $camera->id,
            'camera_name' => $camera->name,
            'channel' => (int) $validated['channel'],
            'rule' => $validated['rule'],
            'rule_title' => is_string($ruleTitle) ? $ruleTitle : $validated['rule'],
        ]);

        try {
            if (
                ! is_string($camera->snapshot_base_url)
                || $camera->snapshot_base_url === ''
                || ! is_string($camera->snapshot_username)
                || $camera->snapshot_username === ''
                || ! is_string($camera->snapshot_password)
                || $camera->snapshot_password === ''
            ) {
                throw new UnexpectedValueException('Camera snapshot connection is not configured.');
            }

            $jpeg = $dahuaClient->snapshot(
                $camera->snapshot_base_url,
                $camera->snapshot_username,
                $camera->snapshot_password,
                (int) $validated['channel'],
            );

            $snapshotStore->put($cameraEvent->id, $jpeg);

            ProcessCameraEvent::dispatch($camera->id, (int) $validated['channel'], $cameraEvent->id);
        } catch (Throwable $exception) {
            $snapshotStore->delete($cameraEvent->id);
            Cache::forget($duplicateKey);

            $cameraEvent->update([
                'status' => 'failed',
                'failure_type' => $exception::class,
                'processed_at' => now(),
            ]);

            Log::error('Dahua webhook snapshot capture failed', [
                'camera_event_id' => $cameraEvent->id,
                'camera_id' => $camera->id,
                'failure_type' => $exception::class,
            ]);

            return response('Snapshot unavailable', 503)
                ->header('Content-Type', 'text/plain');
        }

        Log::info('Dahua IVS webhook accepted', [
            'camera_id' => $camera->id,
            'camera_name' => $camera->name,
            'event' => $validated['event'],
            'channel' => (int) $validated['channel'],
            'rule' => $validated['rule'],
        ]);

        return response('OK', 200)
            ->header('Content-Type', 'text/plain');
    }

    private function unauthorizedResponse(): Response
    {
        return response('Unauthorized', 401)
            ->header('Content-Type', 'text/plain')
            ->header('WWW-Authenticate', 'Basic realm="max-notify"');
    }
}
