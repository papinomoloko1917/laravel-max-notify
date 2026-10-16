<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessCameraEvent;
use App\Models\Camera;
use App\Models\CameraEvent;
use App\Models\Client;
use App\Services\DahuaClient;
use App\Services\MaxClient;
use App\Services\MaxNotificationMessage;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class ProcessCameraEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleted_camera_does_not_make_http_requests(): void
    {
        $camera = Camera::factory()->create();
        $cameraEvent = CameraEvent::factory()->for($camera)->create();
        $cameraId = (int) $camera->id;

        $camera->delete();

        Http::preventStrayRequests();

        $job = new ProcessCameraEvent($cameraId, 13, (int) $cameraEvent->id);

        $job->handle(new DahuaClient, app(MaxClient::class), new MaxNotificationMessage);

        Http::assertNothingSent();

        $cameraEvent->refresh();

        $this->assertSame('skipped', $cameraEvent->status);
        $this->assertSame('camera_missing', $cameraEvent->failure_type);
        $this->assertNotNull($cameraEvent->processed_at);
        $this->assertSame(0, $cameraEvent->recipients_total);
        $this->assertSame(0, $cameraEvent->recipients_sent);
    }

    public function test_inactive_camera_does_not_make_http_requests(): void
    {
        $camera = Camera::factory()->create(['is_active' => false]);
        $cameraEvent = CameraEvent::factory()->for($camera)->create();
        $client = Client::factory()->create();

        $camera->clients()->attach($client);

        Http::preventStrayRequests();

        $job = new ProcessCameraEvent((int) $camera->id, 13, (int) $cameraEvent->id);

        $job->handle(new DahuaClient, app(MaxClient::class), new MaxNotificationMessage);

        Http::assertNothingSent();

        $cameraEvent->refresh();

        $this->assertSame('skipped', $cameraEvent->status);
        $this->assertSame('camera_inactive', $cameraEvent->failure_type);
        $this->assertNotNull($cameraEvent->processed_at);
        $this->assertSame(0, $cameraEvent->recipients_total);
        $this->assertSame(0, $cameraEvent->recipients_sent);
    }

    public function test_active_camera_without_clients_does_not_make_http_requests(): void
    {
        $camera = Camera::factory()->create(['is_active' => true]);
        $cameraEvent = CameraEvent::factory()->for($camera)->create();

        Http::preventStrayRequests();

        $job = new ProcessCameraEvent((int) $camera->id, 13, (int) $cameraEvent->id);

        $job->handle(new DahuaClient, app(MaxClient::class), new MaxNotificationMessage);

        Http::assertNothingSent();

        $cameraEvent->refresh();

        $this->assertSame('skipped', $cameraEvent->status);
        $this->assertSame('no_recipients', $cameraEvent->failure_type);
        $this->assertNotNull($cameraEvent->processed_at);
        $this->assertSame(0, $cameraEvent->recipients_total);
        $this->assertSame(0, $cameraEvent->recipients_sent);
    }

    public function test_active_camera_sends_snapshot_to_all_clients(): void
    {
        $camera = Camera::factory()->create([
            'name' => 'Въезд центральный',
            'is_active' => true,
            'snapshot_base_url' => 'http://nvr.test',
            'snapshot_username' => 'camera-user',
            'snapshot_password' => 'camera-password',
        ]);
        $cameraEvent = CameraEvent::factory()->for($camera)->create([
            'camera_name' => $camera->name,
            'rule' => 'VehicleDetection',
            'rule_title' => 'Обнаружен транспорт',
            'created_at' => CarbonImmutable::parse('2026-10-12 19:37:00', 'Europe/Moscow'),
        ]);
        $firstClient = Client::factory()->create(['max_chat_id' => 12345]);
        $secondClient = Client::factory()->create(['max_chat_id' => 67890]);

        $camera->clients()->attach([$firstClient->id, $secondClient->id]);

        config([
            'services.max.base_url' => 'https://max.test',
            'services.max.token' => 'test-token',
        ]);

        Http::preventStrayRequests();

        $jpeg = 'fake-jpeg-content';
        $uploadUrl = 'https://upload.test/image?key=test';
        $mediaToken = 'test-media-token';
        $notificationText = "🚨 Обнаружен транспорт\n\n📍 Въезд центральный\n🕒 12.10 19:37 МСК";

        Http::fake([
            'http://nvr.test/cgi-bin/snapshot.cgi*' => Http::response(
                $jpeg,
                200,
                ['Content-Type' => 'image/jpeg'],
            ),
            'https://max.test/uploads*' => Http::response([
                'url' => $uploadUrl,
            ]),
            $uploadUrl => Http::response([
                'photos' => [
                    ['token' => $mediaToken],
                ],
            ]),
            'https://max.test/messages*' => Http::response([
                'message' => [
                    'body' => [
                        'mid' => 'test-message-id',
                    ],
                ],
            ]),
        ]);

        $job = new ProcessCameraEvent((int) $camera->id, 13, (int) $cameraEvent->id);

        $job->handle(new DahuaClient, app(MaxClient::class), new MaxNotificationMessage);

        Http::assertSentCount(5);

        Http::assertSent(
            fn (Request $request): bool => $request->method() === 'GET'
                && $request->url() === 'http://nvr.test/cgi-bin/snapshot.cgi?channel=13',
        );

        Http::assertSent(
            fn (Request $request): bool => $request->method() === 'POST'
                && $request->url() === $uploadUrl
                && $request->hasFile('data', $jpeg, 'snapshot.jpg'),
        );

        Http::assertSent(
            fn (Request $request): bool => $request->method() === 'POST'
                && $request->url() === 'https://max.test/messages?chat_id=12345'
                && $request->isJson()
                && $request->data() === [
                    'text' => $notificationText,
                    'attachments' => [
                        [
                            'type' => 'image',
                            'payload' => [
                                'token' => $mediaToken,
                            ],
                        ],
                    ],
                ],
        );

        Http::assertSent(
            fn (Request $request): bool => $request->method() === 'POST'
                && $request->url() === 'https://max.test/messages?chat_id=67890'
                && $request->isJson()
                && $request->data() === [
                    'text' => $notificationText,
                    'attachments' => [
                        [
                            'type' => 'image',
                            'payload' => [
                                'token' => $mediaToken,
                            ],
                        ],
                    ],
                ],
        );

        $cameraEvent->refresh();

        $this->assertSame('completed', $cameraEvent->status);
        $this->assertNull($cameraEvent->failure_type);
        $this->assertNotNull($cameraEvent->processed_at);
        $this->assertSame(2, $cameraEvent->recipients_total);
        $this->assertSame(2, $cameraEvent->recipients_sent);
    }

    public function test_failed_callback_records_exception_type_and_preserves_recipient_progress(): void
    {
        $camera = Camera::factory()->create();
        $cameraEvent = CameraEvent::factory()->for($camera)->create([
            'recipients_total' => 2,
            'recipients_sent' => 1,
        ]);

        $job = new ProcessCameraEvent((int) $camera->id, 13, (int) $cameraEvent->id);

        $job->failed(new RuntimeException('Sensitive failure details'));

        $cameraEvent->refresh();

        $this->assertSame('failed', $cameraEvent->status);
        $this->assertSame(RuntimeException::class, $cameraEvent->failure_type);
        $this->assertNotNull($cameraEvent->processed_at);
        $this->assertSame(2, $cameraEvent->recipients_total);
        $this->assertSame(1, $cameraEvent->recipients_sent);
        $this->assertNotSame('Sensitive failure details', $cameraEvent->failure_type);
        $this->assertStringNotContainsString(
            'Sensitive failure details',
            json_encode($cameraEvent->getAttributes(), JSON_THROW_ON_ERROR),
        );
    }

    public function test_failed_callback_records_unknown_when_exception_is_missing(): void
    {
        $camera = Camera::factory()->create();
        $cameraEvent = CameraEvent::factory()->for($camera)->create();

        $job = new ProcessCameraEvent((int) $camera->id, 13, (int) $cameraEvent->id);

        $job->failed(null);

        $cameraEvent->refresh();

        $this->assertSame('failed', $cameraEvent->status);
        $this->assertSame('unknown', $cameraEvent->failure_type);
        $this->assertNotNull($cameraEvent->processed_at);
    }

    public function test_failed_callback_ignores_missing_camera_event(): void
    {
        $camera = Camera::factory()->create();

        $job = new ProcessCameraEvent((int) $camera->id, 13, 999999);

        $job->failed(new RuntimeException('Controlled failure'));

        $this->assertDatabaseCount('camera_events', 0);
    }
}
