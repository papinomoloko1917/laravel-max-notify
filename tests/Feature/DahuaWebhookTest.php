<?php

namespace Tests\Feature;

use App\Models\Camera;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DahuaWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_camera_can_send_authenticated_ivs_webhook(): void
    {
        $username = 'user';

        $password = 'password';

        $url = '/webhooks/dahua?event=ivs&channel=13&rule=perimeter';

        Camera::factory()->create([
            'webhook_username' => $username,
            'webhook_password' => $password,
            'is_active' => true,
        ]);

        $response = $this->withBasicAuth($username, $password)->get($url);

        $response->assertOk();
        $response->assertContent('OK');
        $response->assertHeader(
            'Content-Type',
            'text/plain; charset=utf-8',
        );
    }

    public function test_request_without_basic_credentials_is_rejected(): void
    {
        $url = '/webhooks/dahua?event=ivs&channel=13&rule=perimeter';

        $response = $this->get($url);

        $response->assertStatus(401);

        $response->assertContent('Unauthorized');
    }

    public function test_unknown_webhook_username_is_rejected(): void
    {
        $username = 'user';

        $password = 'password';

        $url = '/webhooks/dahua?event=ivs&channel=13&rule=perimeter';

        Camera::factory()->create([
            'webhook_username' => $username,
            'webhook_password' => $password,
            'is_active' => true,
        ]);

        $response = $this->withBasicAuth('Новый пользователь', $password)->get($url);

        $response->assertStatus(401);

        $response->assertContent('Unauthorized');
    }

    public function test_wrong_webhook_password_is_rejected(): void
    {
        $username = 'user';

        $password = 'password';

        $url = '/webhooks/dahua?event=ivs&channel=13&rule=perimeter';

        Camera::factory()->create([
            'webhook_username' => $username,
            'webhook_password' => $password,
            'is_active' => true,
        ]);

        $response = $this->withBasicAuth($username, 'Новый пароль')->get($url);

        $response->assertStatus(401);

        $response->assertContent('Unauthorized');
    }

    public function test_inactive_camera_is_rejected(): void
    {
        $username = 'user';

        $password = 'password';

        $url = '/webhooks/dahua?event=ivs&channel=13&rule=perimeter';

        Camera::factory()->create([
            'webhook_username' => $username,
            'webhook_password' => $password,
            'is_active' => false,
        ]);

        $response = $this->withBasicAuth($username, $password)->get($url);

        $response->assertStatus(401);

        $response->assertContent('Unauthorized');
    }

    public function test_authenticated_request_rejects_non_ivs_event(): void
    {
        $username = 'user';

        $password = 'password';

        $url = '/webhooks/dahua?event=smd&channel=13&rule=perimeter';

        Camera::factory()->create([
            'webhook_username' => $username,
            'webhook_password' => $password,
            'is_active' => true,
        ]);

        $response = $this->withBasicAuth($username, $password)->get($url);

        $response->assertStatus(400);

        $response->assertContent('Invalid webhook request');
    }

    public function test_authenticated_request_rejects_non_integer_channel(): void
    {
        $username = 'user';

        $password = 'password';

        $url = '/webhooks/dahua?event=ivs&channel=abc&rule=perimeter';

        Camera::factory()->create([
            'webhook_username' => $username,
            'webhook_password' => $password,
            'is_active' => true,
        ]);

        $response = $this->withBasicAuth($username, $password)->get($url);

        $response->assertStatus(400);

        $response->assertContent('Invalid webhook request');
    }

    public function test_authenticated_request_rejects_missing_rule(): void
    {
        $username = 'user';

        $password = 'password';

        $url = '/webhooks/dahua?event=ivs&channel=13';

        Camera::factory()->create([
            'webhook_username' => $username,
            'webhook_password' => $password,
            'is_active' => true,
        ]);

        $response = $this->withBasicAuth($username, $password)->get($url);

        $response->assertStatus(400);

        $response->assertContent('Invalid webhook request');
    }

    public function test_authenticated_webhook_is_ignored_outside_camera_notification_window(): void
    {
        $url = '/webhooks/dahua?event=ivs&channel=13&rule=perimeter';

        $username = 'user';

        $password = 'password';

        Camera::factory()->create([
            'webhook_username' => $username,
            'webhook_password' => $password,
            'notify_from' => '21:00',
            'notify_until' => '06:00',
            'is_active' => true,
        ]);

        $time = CarbonImmutable::create(
            2026,
            9,
            28,
            12,
            0,
            0,
            'Europe/Moscow',
        );

        $this->travelTo($time);

        $response = $this->withBasicAuth($username, $password)->get($url);

        $response->assertStatus(200);

        $response->assertContent('Ignored');
    }

    public function test_authenticated_webhook_is_accepted_inside_camera_notification_window(): void
    {
        $url = '/webhooks/dahua?event=ivs&channel=13&rule=perimeter';

        $username = 'user';

        $password = 'password';

        Camera::factory()->create([
            'webhook_username' => $username,
            'webhook_password' => $password,
            'notify_from' => '21:00',
            'notify_until' => '06:00',
            'is_active' => true,
        ]);

        $time = CarbonImmutable::create(
            2026,
            9,
            28,
            22,
            0,
            0,
            'Europe/Moscow',
        );

        $this->travelTo($time);

        $response = $this->withBasicAuth($username, $password)->get($url);

        $response->assertStatus(200);

        $response->assertContent('OK');
    }
}
