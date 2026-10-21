<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessCameraEvent;
use App\Models\Camera;
use App\Models\CameraEvent;
use App\Models\Client;
use App\Services\CameraEventSnapshotStore;
use App\Services\MaxClient;
use App\Services\MaxNotificationMessage;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class ProcessCameraEventTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_deleted_camera_does_not_make_http_requests(): void
    {
        $camera = Camera::factory()->create();
        $cameraEvent = CameraEvent::factory()->for($camera)->create();
        $cameraId = (int) $camera->id;

        $camera->delete();

        $this->storeSnapshot((int) $cameraEvent->id);

        Http::preventStrayRequests();

        $job = new ProcessCameraEvent($cameraId, 13, (int) $cameraEvent->id);

        $job->handle(app(CameraEventSnapshotStore::class), app(MaxClient::class), new MaxNotificationMessage);

        Http::assertNothingSent();

        $cameraEvent->refresh();

        $this->assertSame('skipped', $cameraEvent->status);
        $this->assertSame('camera_missing', $cameraEvent->failure_type);
        $this->assertNotNull($cameraEvent->processed_at);
        $this->assertSame(0, $cameraEvent->recipients_total);
        $this->assertSame(0, $cameraEvent->recipients_sent);
        $this->assertSnapshotWasDeleted((int) $cameraEvent->id);
    }

    public function test_inactive_camera_does_not_make_http_requests(): void
    {
        $camera = Camera::factory()->create(['is_active' => false]);
        $cameraEvent = CameraEvent::factory()->for($camera)->create();
        $client = Client::factory()->create();

        $camera->clients()->attach($client);

        $this->storeSnapshot((int) $cameraEvent->id);

        Http::preventStrayRequests();

        $job = new ProcessCameraEvent((int) $camera->id, 13, (int) $cameraEvent->id);

        $job->handle(app(CameraEventSnapshotStore::class), app(MaxClient::class), new MaxNotificationMessage);

        Http::assertNothingSent();

        $cameraEvent->refresh();

        $this->assertSame('skipped', $cameraEvent->status);
        $this->assertSame('camera_inactive', $cameraEvent->failure_type);
        $this->assertNotNull($cameraEvent->processed_at);
        $this->assertSame(0, $cameraEvent->recipients_total);
        $this->assertSame(0, $cameraEvent->recipients_sent);
        $this->assertSnapshotWasDeleted((int) $cameraEvent->id);
    }

    public function test_active_camera_without_clients_does_not_make_http_requests(): void
    {
        $camera = Camera::factory()->create(['is_active' => true]);
        $cameraEvent = CameraEvent::factory()->for($camera)->create();

        $this->storeSnapshot((int) $cameraEvent->id);

        Http::preventStrayRequests();

        $job = new ProcessCameraEvent((int) $camera->id, 13, (int) $cameraEvent->id);

        $job->handle(app(CameraEventSnapshotStore::class), app(MaxClient::class), new MaxNotificationMessage);

        Http::assertNothingSent();

        $cameraEvent->refresh();

        $this->assertSame('skipped', $cameraEvent->status);
        $this->assertSame('no_recipients', $cameraEvent->failure_type);
        $this->assertNotNull($cameraEvent->processed_at);
        $this->assertSame(0, $cameraEvent->recipients_total);
        $this->assertSame(0, $cameraEvent->recipients_sent);
        $this->assertSnapshotWasDeleted((int) $cameraEvent->id);
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
            'created_at' => CarbonImmutable::parse('2026-10-12 19:37:42', 'Europe/Moscow'),
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
        $notificationText = "🚨 Обнаружен транспорт\n\n📍 Въезд центральный\n🕒 12.10 19:37:42 МСК";

        $this->storeSnapshot((int) $cameraEvent->id, $jpeg);

        Http::fake([
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

        $job->handle(app(CameraEventSnapshotStore::class), app(MaxClient::class), new MaxNotificationMessage);

        Http::assertSentCount(4);

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
        $this->assertSnapshotWasDeleted((int) $cameraEvent->id);
    }

    public function test_snapshot_is_deleted_when_max_delivery_fails(): void
    {
        $camera = Camera::factory()->create(['is_active' => true]);
        $cameraEvent = CameraEvent::factory()->for($camera)->create();
        $client = Client::factory()->create();

        $camera->clients()->attach($client);
        $this->storeSnapshot((int) $cameraEvent->id);

        config([
            'services.max.base_url' => 'https://max.test',
            'services.max.token' => 'test-token',
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'https://max.test/uploads*' => Http::response(['error' => 'unavailable'], 503),
        ]);

        $job = new ProcessCameraEvent((int) $camera->id, 13, (int) $cameraEvent->id);

        $exception = null;

        try {
            $job->handle(app(CameraEventSnapshotStore::class), app(MaxClient::class), new MaxNotificationMessage);
        } catch (Throwable $caught) {
            $exception = $caught;
        }

        $this->assertInstanceOf(RequestException::class, $exception);
        $this->assertSnapshotWasDeleted((int) $cameraEvent->id);
    }

    public function test_failed_callback_records_exception_type_and_preserves_recipient_progress(): void
    {
        $camera = Camera::factory()->create();
        $cameraEvent = CameraEvent::factory()->for($camera)->create([
            'recipients_total' => 2,
            'recipients_sent' => 1,
        ]);

        $this->storeSnapshot((int) $cameraEvent->id);

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
        $this->assertSnapshotWasDeleted((int) $cameraEvent->id);
    }

    public function test_failed_callback_records_unknown_when_exception_is_missing(): void
    {
        $camera = Camera::factory()->create();
        $cameraEvent = CameraEvent::factory()->for($camera)->create();

        $this->storeSnapshot((int) $cameraEvent->id);

        $job = new ProcessCameraEvent((int) $camera->id, 13, (int) $cameraEvent->id);

        $job->failed(null);

        $cameraEvent->refresh();

        $this->assertSame('failed', $cameraEvent->status);
        $this->assertSame('unknown', $cameraEvent->failure_type);
        $this->assertNotNull($cameraEvent->processed_at);
        $this->assertSnapshotWasDeleted((int) $cameraEvent->id);
    }

    public function test_failed_callback_ignores_missing_camera_event(): void
    {
        $camera = Camera::factory()->create();

        $this->storeSnapshot(999999);

        $job = new ProcessCameraEvent((int) $camera->id, 13, 999999);

        $job->failed(new RuntimeException('Controlled failure'));

        $this->assertDatabaseCount('camera_events', 0);
        $this->assertSnapshotWasDeleted(999999);
    }

    private function storeSnapshot(int $cameraEventId, string $jpeg = 'fake-jpeg-content'): void
    {
        app(CameraEventSnapshotStore::class)->put($cameraEventId, $jpeg);
    }

    private function assertSnapshotWasDeleted(int $cameraEventId): void
    {
        Storage::disk('local')->assertMissing(
            app(CameraEventSnapshotStore::class)->path($cameraEventId),
        );
    }
}
