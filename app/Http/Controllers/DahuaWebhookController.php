<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Services\NotificationWindow;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DahuaWebhookController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $username = $request->getUser();

        $password = $request->getPassword();

        $rules = [
            'event' => ['required', 'in:ivs'],
            'channel' => ['required', 'integer', 'min:1'],
            'rule' => ['required', 'string', 'max:100'],
        ];

        if (! is_string($username) || ! is_string($password)) {
            return response('Unauthorized', 401)
                ->header('Content-Type', 'text/plain');
        }

        $camera = Camera::query()
            ->where('webhook_username', $username)
            ->where('is_active', true)
            ->first();

        $storedPassword = $camera?->webhook_password;

        if (! is_string($storedPassword) || ! Hash::check($password, $storedPassword)) {
            return response('Unauthorized', 401)
                ->header('Content-Type', 'text/plain');
        }

        $validator = Validator::make($request->query(), $rules);

        if ($validator->fails()) {
            return response('Invalid webhook request', 400)
                ->header('Content-Type', 'text/plain');
        }

        $validated = $validator->validated();

        $window = new NotificationWindow($camera->notify_from, $camera->notify_until);

        $isAllowed = $window->allows(now());

        if ($isAllowed === false) {
            return response('Ignored', 200)
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
}
