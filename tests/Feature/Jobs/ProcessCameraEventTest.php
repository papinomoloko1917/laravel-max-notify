<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessCameraEvent;
use App\Models\Camera;
use App\Models\Client;
use App\Services\DahuaClient;
use App\Services\MaxClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProcessCameraEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleted_camera_does_not_make_http_requests(): void
    {
        $camera = Camera::factory()->create();
        $cameraId = (int) $camera->id;

        $camera->delete();

        Http::preventStrayRequests();

        $job = new ProcessCameraEvent($cameraId, 13);

        $job->handle(new DahuaClient, new MaxClient);

        Http::assertNothingSent();
    }

    public function test_inactive_camera_does_not_make_http_requests(): void
    {
        $camera = Camera::factory()->create(['is_active' => false]);
        $client = Client::factory()->create();

        $camera->clients()->attach($client);

        Http::preventStrayRequests();

        $job = new ProcessCameraEvent((int) $camera->id, 13);

        $job->handle(new DahuaClient, new MaxClient);

        Http::assertNothingSent();
    }

    public function test_active_camera_without_clients_does_not_make_http_requests(): void
    {
        $camera = Camera::factory()->create(['is_active' => true]);

        Http::preventStrayRequests();

        $job = new ProcessCameraEvent((int) $camera->id, 13);

        $job->handle(new DahuaClient, new MaxClient);

        Http::assertNothingSent();
    }

    public function test_active_camera_with_client_uploads_snapshot_to_max(): void
    {
        $camera = Camera::factory()->create(['is_active' => true]);
        $client = Client::factory()->create();

        $camera->clients()->attach($client);

        config([
            'services.dahua.base_url' => 'http://nvr.test',
            'services.dahua.username' => 'test-user',
            'services.dahua.password' => 'test-password',
            'services.max.base_url' => 'https://max.test',
            'services.max.token' => 'test-token',
        ]);

        Http::preventStrayRequests();

        $jpeg = 'fake-jpeg-content';
        $uploadUrl = 'https://upload.test/image?key=test';

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
                    ['token' => 'test-media-token'],
                ],
            ]),
        ]);

        $job = new ProcessCameraEvent((int) $camera->id, 13);

        $job->handle(new DahuaClient, new MaxClient);

        Http::assertSentCount(3);

        Http::assertSent(
            fn (Request $request): bool => $request->method() === 'POST'
                && $request->url() === $uploadUrl
                && $request->hasFile('data', $jpeg, 'snapshot.jpg'),
        );
    }
}
