<?php

namespace Tests\Feature;

use App\Models\Camera;
use App\Models\CameraEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class CameraEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_camera_events_page(): void
    {
        $response = $this->get(route('events.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_camera_events_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('events.index'));

        $response->assertOk();
        $response->assertSee(__('The history of events'));
        $response->assertSee('Обработанные события хранятся 30 дней.');
        $response->assertSee('Очистить обработанную историю');
    }

    public function test_authenticated_user_sees_camera_events_newest_first_with_processing_details(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $olderEvent = CameraEvent::factory()->create([
            'camera_name' => 'Older event camera',
            'channel' => 3,
            'rule' => 'older-rule',
            'status' => 'failed',
            'recipients_total' => 3,
            'recipients_sent' => 1,
        ]);

        $newerEvent = CameraEvent::factory()->create([
            'camera_name' => 'Newer event camera',
            'channel' => 7,
            'rule' => 'newer-rule',
            'status' => 'completed',
            'recipients_total' => 2,
            'recipients_sent' => 2,
        ]);

        $response = $this->get(route('events.index'));

        $response->assertOk();
        $response->assertSee($newerEvent->created_at->format('d.m.Y H:i:s'));
        $response->assertSee($olderEvent->created_at->format('d.m.Y H:i:s'));
        $response->assertSeeInOrder([
            $newerEvent->camera_name,
            (string) $newerEvent->channel,
            $newerEvent->rule,
            __('Completed'),
            '2 / 2',
            $olderEvent->camera_name,
            (string) $olderEvent->channel,
            $olderEvent->rule,
            __('Failed'),
            '1 / 3',
        ]);
    }

    public function test_authenticated_user_sees_empty_state_when_no_camera_events_exist(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('events.index'));

        $response->assertOk();
        $response->assertSee(__('The event history is empty...'));
    }

    public function test_camera_events_can_be_filtered_by_status(): void
    {
        $events = [
            'pending' => 'Pending event camera',
            'skipped' => 'Skipped event camera',
            'completed' => 'Completed event camera',
            'failed' => 'Failed event camera',
        ];

        foreach ($events as $status => $cameraName) {
            CameraEvent::factory()->create([
                'camera_name' => $cameraName,
                'status' => $status,
            ]);
        }

        $component = Livewire::test('pages::events.index');

        foreach ($events as $status => $cameraName) {
            $component
                ->set('filter', $status)
                ->assertSee($cameraName);

            foreach (array_diff($events, [$cameraName]) as $otherCameraName) {
                $component->assertDontSee($otherCameraName);
            }
        }

        $component
            ->set('filter', 'all')
            ->assertSee(array_values($events));
    }

    public function test_camera_events_are_paginated_and_filter_resets_current_page(): void
    {
        foreach (range(1, 13) as $number) {
            CameraEvent::factory()->create([
                'camera_name' => sprintf('Event camera %02d', $number),
                'status' => $number <= 3 ? 'completed' : 'failed',
            ]);
        }

        Livewire::test('pages::events.index')
            ->assertSee('Event camera 13')
            ->assertDontSee('Event camera 03')
            ->call('gotoPage', 2)
            ->assertSee('Event camera 03')
            ->assertDontSee('Event camera 13')
            ->assertSet('paginators.page', 2)
            ->set('filter', 'completed')
            ->assertSet('paginators.page', 1)
            ->assertSee('Event camera 03')
            ->assertDontSee('Event camera 13');
    }

    public function test_selecting_camera_event_loads_read_only_processing_details(): void
    {
        $processedAt = now()->startOfSecond();

        $event = CameraEvent::factory()->create([
            'camera_name' => 'Detailed event camera',
            'channel' => 13,
            'rule' => 'perimeter',
            'status' => 'failed',
            'recipients_total' => 2,
            'recipients_sent' => 1,
            'failure_type' => RuntimeException::class,
            'processed_at' => $processedAt,
        ]);

        Livewire::test('pages::events.index')
            ->assertDontSee(RuntimeException::class)
            ->call('showDetails', $event->id)
            ->assertSet('selectedEventId', $event->id)
            ->assertSee(__('Event details'))
            ->assertSee($event->camera_name)
            ->assertSee($processedAt->format('d.m.Y H:i:s'))
            ->assertSee(RuntimeException::class)
            ->assertSee('1 / 2');
    }

    public function test_pending_event_details_show_unprocessed_and_no_failure(): void
    {
        $event = CameraEvent::factory()->create([
            'status' => 'pending',
            'failure_type' => null,
            'processed_at' => null,
        ]);

        Livewire::test('pages::events.index')
            ->call('showDetails', $event->id)
            ->assertSee(__('Not processed yet'))
            ->assertSee(__('None'));
    }

    public function test_event_details_keep_camera_snapshot_after_camera_is_deleted(): void
    {
        $camera = Camera::factory()->create([
            'name' => 'Camera to delete',
        ]);

        $event = CameraEvent::factory()->for($camera)->create([
            'camera_name' => 'Historical camera name',
        ]);

        $camera->delete();
        $event->refresh();

        $this->assertNull($event->camera_id);

        Livewire::test('pages::events.index')
            ->call('showDetails', $event->id)
            ->assertSee('Historical camera name')
            ->assertSee(__('Deleted camera'));
    }

    public function test_clearing_history_deletes_only_processed_events(): void
    {
        foreach (CameraEvent::TERMINAL_STATUSES as $status) {
            CameraEvent::factory()->create(['status' => $status]);
        }

        $pendingEvent = CameraEvent::factory()->create(['status' => 'pending']);

        Livewire::test('pages::events.index')
            ->call('clearProcessedHistory')
            ->assertHasNoErrors()
            ->assertDispatched('modal-close', name: 'clear-event-history');

        $this->assertDatabaseCount('camera_events', 1);
        $this->assertDatabaseHas('camera_events', [
            'id' => $pendingEvent->id,
            'status' => 'pending',
        ]);
    }
}
