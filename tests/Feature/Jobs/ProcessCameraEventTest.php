<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessCameraEvent;
use App\Models\Camera;
use App\Models\Client;
use App\Services\DahuaClient;
use App\Services\MaxClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
