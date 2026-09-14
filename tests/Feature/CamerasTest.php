<?php

namespace Tests\Feature;

use App\Models\Camera;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CamerasTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_cameras_page(): void
    {
        $response = $this->get(route('cameras.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_cameras_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('cameras.index'));

        $response->assertOk();

        $response->assertSee(__('Cameras'));
    }

    public function test_authenticated_user_sees_cameras_ordered_by_name_with_statuses(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $camera1 = Camera::factory()->create([
            'name' => 'Zulu Camera',
            'is_active' => true,
        ]);

        $camera2 = Camera::factory()->create([
            'name' => 'Alpha Camera',
            'is_active' => false,
        ]);

        $response = $this->get(route('cameras.index'));

        $response->assertOk();

        $response->assertSeeInOrder([$camera2->name, __('Inactive'), $camera1->name, __('Active')]);
    }

    public function test_authenticated_user_sees_empty_state_when_no_cameras_exist(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('cameras.index'));

        $response->assertOk();

        $response->assertSee(__('The list of cameras is empty...'));
    }
}
