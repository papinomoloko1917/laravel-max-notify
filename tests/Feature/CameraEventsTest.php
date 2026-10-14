<?php

namespace Tests\Feature;

use App\Models\CameraEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
