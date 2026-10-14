<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_not_available(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_guest_cannot_access_administration_pages(): void
    {
        foreach ($this->administrationRoutes() as $routeName) {
            $this->get(route($routeName))->assertRedirect(route('login'));
        }
    }

    public function test_existing_user_without_verified_email_can_access_administration_pages(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user);

        foreach ($this->administrationRoutes() as $routeName) {
            $this->get(route($routeName))->assertOk();
        }
    }

    /** @return list<string> */
    private function administrationRoutes(): array
    {
        return [
            'dashboard',
            'cameras.index',
            'clients.index',
            'events.index',
        ];
    }
}
