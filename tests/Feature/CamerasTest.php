<?php

namespace Tests\Feature;

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
}
