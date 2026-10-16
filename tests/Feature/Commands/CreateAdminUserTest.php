<?php

namespace Tests\Feature\Commands;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_verified_administrator_without_exposing_the_password_as_an_option(): void
    {
        $this->artisan('admin:create', [
            '--name' => 'Администратор',
            '--email' => 'admin@example.test',
        ])
            ->expectsQuestion('Пароль (минимум 12 символов)', 'long-secure-password')
            ->expectsQuestion('Повторите пароль', 'long-secure-password')
            ->expectsOutput('Администратор admin@example.test создан.')
            ->assertSuccessful();

        $user = User::query()->where('email', 'admin@example.test')->firstOrFail();

        $this->assertSame('Администратор', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('long-secure-password', $user->password));
    }

    public function test_it_rejects_a_duplicate_email_and_weak_password(): void
    {
        User::factory()->create(['email' => 'admin@example.test']);

        $this->artisan('admin:create', [
            '--name' => 'Другой администратор',
            '--email' => 'admin@example.test',
        ])
            ->expectsQuestion('Пароль (минимум 12 символов)', 'short')
            ->expectsQuestion('Повторите пароль', 'short')
            ->assertFailed();

        $this->assertDatabaseCount('users', 1);
    }
}
