<?php

namespace Tests\Feature\Models;

use App\Models\Camera;
use App\Models\CameraEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CameraEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_camera_event_is_stored_with_defaults_and_casts(): void
    {
        $camera = Camera::factory()->create([
            'name' => 'Вход в офис',
        ]);

        $event = CameraEvent::create([
            'camera_id' => $camera->id,
            'camera_name' => $camera->name,
            'channel' => 13,
            'rule' => 'CrossLineDetection',
        ]);

        $event = CameraEvent::findOrFail($event->id);

        $this->assertDatabaseHas('camera_events', [
            'id' => $event->id,
            'camera_id' => $camera->id,
            'camera_name' => 'Вход в офис',
            'channel' => 13,
            'rule' => 'CrossLineDetection',
            'status' => 'pending',
            'recipients_total' => 0,
            'recipients_sent' => 0,
            'failure_type' => null,
            'processed_at' => null,
        ]);

        $this->assertIsInt($event->channel);
        $this->assertIsInt($event->recipients_total);
        $this->assertIsInt($event->recipients_sent);
        $this->assertSame('pending', $event->status);
        $this->assertNull($event->failure_type);
        $this->assertNull($event->processed_at);
    }

    public function test_camera_event_and_camera_are_related_from_both_sides(): void
    {
        $camera = Camera::factory()->create();

        $event = CameraEvent::factory()->for($camera)->create([
            'camera_name' => $camera->name,
        ]);

        $this->assertTrue($event->camera->is($camera));
        $this->assertTrue($camera->cameraEvents->contains($event));
    }

    public function test_deleting_camera_keeps_event_and_camera_name(): void
    {
        $camera = Camera::factory()->create([
            'name' => 'Архивная камера',
        ]);

        $event = CameraEvent::factory()->for($camera)->create([
            'camera_name' => $camera->name,
        ]);

        $camera->delete();
        $event->refresh();

        $this->assertNull($event->camera_id);
        $this->assertNull($event->camera);
        $this->assertSame('Архивная камера', $event->camera_name);
        $this->assertDatabaseHas('camera_events', [
            'id' => $event->id,
            'camera_id' => null,
            'camera_name' => 'Архивная камера',
        ]);
    }
}
