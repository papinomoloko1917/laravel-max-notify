<?php

namespace Tests\Feature;

use App\Models\Camera;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CameraRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_camera_rules_page(): void
    {
        $camera = Camera::factory()->create();

        $this->get(route('cameras.rules', $camera))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_manage_multiple_rules_for_camera(): void
    {
        $camera = Camera::factory()->create();

        $this->actingAs(User::factory()->create());

        $component = Livewire::test('pages::cameras.rules', ['camera' => $camera])
            ->set('code', 'VehicleDetection')
            ->set('title', 'Обнаружен транспорт')
            ->call('createRule')
            ->assertHasNoErrors()
            ->set('code', 'CrossLineDetection')
            ->set('title', 'Пересечение линии')
            ->call('createRule')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('camera_rules', [
            'camera_id' => $camera->id,
            'code' => 'VehicleDetection',
            'title' => 'Обнаружен транспорт',
        ]);
        $this->assertDatabaseHas('camera_rules', [
            'camera_id' => $camera->id,
            'code' => 'CrossLineDetection',
            'title' => 'Пересечение линии',
        ]);

        $rule = $camera->rules()->where('code', 'VehicleDetection')->firstOrFail();

        $component
            ->call('startEditing', $rule->id)
            ->assertSet('editCode', 'VehicleDetection')
            ->set('editTitle', 'Обнаружен автомобиль')
            ->call('updateRule')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('camera_rules', [
            'id' => $rule->id,
            'title' => 'Обнаружен автомобиль',
        ]);

        $component
            ->call('startDeleting', $rule->id)
            ->call('deleteRule');

        $this->assertDatabaseMissing('camera_rules', ['id' => $rule->id]);
        $this->assertDatabaseCount('camera_rules', 1);
    }

    public function test_preset_creates_rule_and_renders_copyable_webhook_url(): void
    {
        config([
            'app.url' => 'https://10.10.0.141',
            'services.dahua.webhook_base_url' => 'http://10.10.0.141',
        ]);

        $camera = Camera::factory()->create(['channel' => 13]);

        $this->actingAs(User::factory()->create());

        $component = Livewire::test('pages::cameras.rules', ['camera' => $camera])
            ->set('presetCode', 'line_crossed')
            ->call('createPresetRule')
            ->assertHasNoErrors()
            ->assertSet('presetCode', '');

        $this->assertDatabaseHas('camera_rules', [
            'camera_id' => $camera->id,
            'code' => 'line_crossed',
            'title' => 'Пересечение линии',
        ]);

        $expectedUrl = 'http://10.10.0.141/webhooks/dahua?' . http_build_query([
            'camera' => $camera->id,
            'token' => $camera->webhook_token,
            'event' => 'ivs',
            'channel' => 13,
            'rule' => 'line_crossed',
        ]);

        $component
            ->assertSee(e($expectedUrl), false)
            ->assertDontSee('https://10.10.0.141/webhooks/dahua', false)
            ->assertDontSee('http://localhost/webhooks/dahua', false);
    }

    public function test_webhook_token_can_be_rotated(): void
    {
        $camera = Camera::factory()->create();
        $oldToken = $camera->webhook_token;
        $oldCiphertext = $camera->getRawOriginal('webhook_token');

        $this->actingAs(User::factory()->create());

        Livewire::test('pages::cameras.rules', ['camera' => $camera])
            ->call('rotateWebhookToken');

        $camera->refresh();

        $this->assertIsString($camera->webhook_token);
        $this->assertSame(64, strlen($camera->webhook_token));
        $this->assertNotSame($oldToken, $camera->webhook_token);
        $this->assertNotSame($oldCiphertext, $camera->getRawOriginal('webhook_token'));
    }

    public function test_configured_webhook_username_is_shown_separately_from_url(): void
    {
        $camera = Camera::factory()->create([
            'channel' => 1,
            'webhook_username' => 'camera-events',
            'webhook_password' => 'secret-webhook-password',
        ]);

        $camera->rules()->create([
            'code' => 'area_intrusion',
            'title' => 'Вторжение в область',
        ]);

        $this->actingAs(User::factory()->create());

        Livewire::test('pages::cameras.rules', ['camera' => $camera])
            ->assertSee('Basic Auth')
            ->assertSee('camera-events')
            ->assertDontSee('secret-webhook-password');
    }

    public function test_custom_rule_identifier_rejects_url_unsafe_characters(): void
    {
        $camera = Camera::factory()->create();

        $this->actingAs(User::factory()->create());

        Livewire::test('pages::cameras.rules', ['camera' => $camera])
            ->set('code', 'Пересечение линии')
            ->set('title', 'Пересечение линии')
            ->call('createRule')
            ->assertHasErrors(['code' => 'regex']);

        $this->assertDatabaseEmpty('camera_rules');
    }

    public function test_rule_code_must_be_unique_within_same_camera(): void
    {
        $camera = Camera::factory()->create();

        $camera->rules()->create([
            'code' => 'VehicleDetection',
            'title' => 'Первый заголовок',
        ]);

        $this->actingAs(User::factory()->create());

        Livewire::test('pages::cameras.rules', ['camera' => $camera])
            ->set('code', 'VehicleDetection')
            ->set('title', 'Другой заголовок')
            ->call('createRule')
            ->assertHasErrors(['code' => 'unique']);

        $this->assertDatabaseCount('camera_rules', 1);
    }

    public function test_rule_from_another_camera_cannot_be_changed(): void
    {
        $camera = Camera::factory()->create();
        $otherCamera = Camera::factory()->create();
        $otherRule = $otherCamera->rules()->create([
            'code' => 'VehicleDetection',
            'title' => 'Обнаружен транспорт',
        ]);

        $this->actingAs(User::factory()->create());

        $this->expectException(ModelNotFoundException::class);

        Livewire::test('pages::cameras.rules', ['camera' => $camera])
            ->call('startEditing', $otherRule->id);
    }
}
