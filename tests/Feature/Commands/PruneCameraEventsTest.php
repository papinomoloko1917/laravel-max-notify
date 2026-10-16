<?php

namespace Tests\Feature\Commands;

use App\Models\CameraEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneCameraEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prunes_only_processed_events_older_than_retention_period(): void
    {
        config(['camera_events.retention_days' => 30]);

        $oldProcessedEvents = collect(CameraEvent::TERMINAL_STATUSES)
            ->map(fn (string $status): CameraEvent => CameraEvent::factory()->create([
                'status' => $status,
                'created_at' => now()->subDays(31),
            ]));

        $oldPendingEvent = CameraEvent::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subDays(31),
        ]);

        $recentProcessedEvent = CameraEvent::factory()->create([
            'status' => 'completed',
            'created_at' => now()->subDays(29),
        ]);

        $this->artisan('camera-events:prune')
            ->expectsOutput('Deleted 3 processed camera event(s).')
            ->assertSuccessful();

        foreach ($oldProcessedEvents as $event) {
            $this->assertDatabaseMissing('camera_events', ['id' => $event->id]);
        }

        $this->assertDatabaseHas('camera_events', ['id' => $oldPendingEvent->id]);
        $this->assertDatabaseHas('camera_events', ['id' => $recentProcessedEvent->id]);
    }

    public function test_it_rejects_an_invalid_retention_period(): void
    {
        config(['camera_events.retention_days' => 0]);

        CameraEvent::factory()->create([
            'status' => 'completed',
            'created_at' => now()->subYear(),
        ]);

        $this->artisan('camera-events:prune')
            ->expectsOutput('Camera event retention must be at least one day.')
            ->assertFailed();

        $this->assertDatabaseCount('camera_events', 1);
    }
}
