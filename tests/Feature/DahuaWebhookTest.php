<?php

namespace Tests\Feature;

use App\Jobs\ProcessCameraEvent;
use App\Models\Camera;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DahuaWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_camera_can_send_authenticated_ivs_webhook(): void
    {
        $username = 'user';

        $password = 'password';

        $url = '/webhooks/dahua?event=ivs&channel=13&rule=perimeter';

        $camera = Camera::factory()->create([
            'webhook_username' => $username,
            'webhook_password' => $password,
            'is_active' => true,
        ]);

        Queue::fake();

        $response = $this->withBasicAuth($username, $password)->get($url);

        $response->assertOk();
        $response->assertContent('OK');
        $response->assertHeader(
            'Content-Type',
            'text/plain; charset=utf-8',
        );

        Queue::assertPushed(
            ProcessCameraEvent::class,
            fn (ProcessCameraEvent $job): bool => $job->cameraId === $camera->id
                && $job->channel === 13,
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

        Queue::fake();

        $response = $this->withBasicAuth($username, $password)->get($url);

        $response->assertStatus(200);

        $response->assertContent('Ignored');

        Queue::assertNotPushed(ProcessCameraEvent::class);
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

    public function test_duplicate_authenticated_webhook_is_ignored_inside_duplicate_window(): void
    {
        $username = 'user';

        $password = 'password';

        $url = '/webhooks/dahua?event=ivs&channel=13&rule=perimeter';

        Camera::factory()->create([
            'webhook_username' => $username,
            'webhook_password' => $password,
            'is_active' => true,
        ]);

        config(['cache.default' => 'database']);

        Queue::fake();

        $firstResponse = $this->withBasicAuth($username, $password)->get($url);

        $secondResponse = $this->withBasicAuth($username, $password)->get($url);

        $firstResponse->assertOk();
        $firstResponse->assertContent('OK');

        $secondResponse->assertOk();
        $secondResponse->assertContent('Ignored');

        Queue::assertPushed(ProcessCameraEvent::class, 1);
    }

    public function test_identical_webhook_is_accepted_after_duplicate_window_expires(): void
    {
        $username = 'user';

        $password = 'password';

        $url = '/webhooks/dahua?event=ivs&channel=13&rule=perimeter';

        Camera::factory()->create([
            'webhook_username' => $username,
            'webhook_password' => $password,
            'is_active' => true,
        ]);

        $time = CarbonImmutable::create(
            2026,
            10,
            13,
            12,
            0,
            0,
            'Europe/Moscow',
        );

        $this->travelTo($time);

        Queue::fake();

        $firstResponse = $this->withBasicAuth($username, $password)->get($url);

        $this->travelTo($time->addSeconds(6));

        $secondResponse = $this->withBasicAuth($username, $password)->get($url);

        $firstResponse->assertOk();
        $firstResponse->assertContent('OK');

        $secondResponse->assertOk();
        $secondResponse->assertContent('OK');

        Queue::assertPushed(ProcessCameraEvent::class, 2);
    }

    public function test_different_camera_channel_or_rule_are_not_duplicates(): void
    {
        $firstUsername = 'first-user';

        $secondUsername = 'second-user';

        $password = 'password';

        Camera::factory()->create([
            'webhook_username' => $firstUsername,
            'webhook_password' => $password,
            'is_active' => true,
        ]);

        Camera::factory()->create([
            'webhook_username' => $secondUsername,
            'webhook_password' => $password,
            'is_active' => true,
        ]);

        Queue::fake();

        $responses = [
            $this->withBasicAuth($firstUsername, $password)
                ->get('/webhooks/dahua?event=ivs&channel=13&rule=perimeter'),
            $this->withBasicAuth($firstUsername, $password)
                ->get('/webhooks/dahua?event=ivs&channel=14&rule=perimeter'),
            $this->withBasicAuth($firstUsername, $password)
                ->get('/webhooks/dahua?event=ivs&channel=13&rule=intrusion'),
            $this->withBasicAuth($secondUsername, $password)
                ->get('/webhooks/dahua?event=ivs&channel=13&rule=perimeter'),
        ];

        foreach ($responses as $response) {
            $response->assertOk();
            $response->assertContent('OK');
        }

        Queue::assertPushed(ProcessCameraEvent::class, 4);
    }

    public function test_webhook_outside_notification_window_does_not_claim_duplicate_key(): void
    {
        $username = 'user';

        $password = 'password';

        $url = '/webhooks/dahua?event=ivs&channel=13&rule=perimeter';

        Camera::factory()->create([
            'webhook_username' => $username,
            'webhook_password' => $password,
            'notify_from' => '21:00',
            'notify_until' => '06:00',
            'is_active' => true,
        ]);

        $time = CarbonImmutable::create(
            2026,
            10,
            13,
            12,
            0,
            0,
            'Europe/Moscow',
        );

        $this->travelTo($time);

        Queue::fake();

        $ignoredResponse = $this->withBasicAuth($username, $password)->get($url);

        $this->travelTo($time->setTime(22, 0));

        $acceptedResponse = $this->withBasicAuth($username, $password)->get($url);

        $ignoredResponse->assertOk();
        $ignoredResponse->assertContent('Ignored');

        $acceptedResponse->assertOk();
        $acceptedResponse->assertContent('OK');

        Queue::assertPushed(ProcessCameraEvent::class, 1);
    }
}
