<?php

namespace Tests\Feature;

use App\Models\Camera;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
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
        $response->assertSee('Логин webhook');
        $response->assertSee('Пароль webhook');
        $response->assertSee('Новый пароль webhook');
        $response->assertSee('Базовый URL устройства');
        $response->assertSee('Логин устройства для снимка');
        $response->assertSee('Пароль устройства для снимка');
        $response->assertSee('Новый пароль устройства для снимка');
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

    public function test_camera_can_be_created(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Livewire::test('pages::cameras.index')
            ->set('name', 'Камера №1')
            ->set('channel', '13')
            ->set('snapshotBaseUrl', 'http://10.10.0.117/')
            ->set('snapshotUsername', 'snapshot-user')
            ->set('snapshotPassword', 'snapshot-secret')
            ->set('webhookUsername', 'camera-13-events')
            ->set('webhookPassword', 'webhook-secret-13')
            ->set('is_active', true)
            ->call('createCamera');

        $component->assertHasNoErrors();
        $component->assertSet('name', '');
        $component->assertSet('channel', '');
        $component->assertSet('snapshotBaseUrl', '');
        $component->assertSet('snapshotUsername', '');
        $component->assertSet('snapshotPassword', '');
        $component->assertSet('webhookUsername', '');
        $component->assertSet('webhookPassword', '');
        $component->assertSee('Камера №1');

        $this->assertDatabaseHas('cameras', [
            'name' => 'Камера №1',
            'channel' => 13,
            'snapshot_base_url' => 'http://10.10.0.117',
            'snapshot_username' => 'snapshot-user',
            'webhook_username' => 'camera-13-events',
            'is_active' => true,
        ]);

        $camera = Camera::query()->where('webhook_username', 'camera-13-events')->firstOrFail();

        $this->assertTrue(Hash::check('webhook-secret-13', $camera->webhook_password));
        $this->assertSame('snapshot-secret', $camera->snapshot_password);
        $this->assertNotSame('snapshot-secret', $camera->getRawOriginal('snapshot_password'));
        $this->assertIsString($camera->webhook_token);
        $this->assertSame(24, strlen($camera->webhook_token));
        $this->assertNotSame($camera->webhook_token, $camera->getRawOriginal('webhook_token'));
        $this->assertArrayNotHasKey('webhook_token', $camera->toArray());
    }

    public function test_camera_name_is_required(): void
    {
        $component = Livewire::test('pages::cameras.index')
            ->set('name', '')
            ->set('is_active', true)
            ->call('createCamera');

        $component->assertHasErrors(['name' => 'required']);

        $this->assertDatabaseEmpty('cameras');
    }

    public function test_cameras_can_be_filtered_by_active_status(): void
    {
        Camera::factory()->create([
            'name' => 'Активная камера',
            'is_active' => true,
        ]);

        Camera::factory()->create([
            'name' => 'Неактивная камера',
            'is_active' => false,
        ]);

        $component = Livewire::test('pages::cameras.index')
            ->set('filter', 'active')
            ->assertSee('Активная камера')
            ->assertDontSee('Неактивная камера')
            ->set('filter', 'inactive')
            ->assertSee('Неактивная камера')
            ->assertDontSee('Активная камера')
            ->set('filter', 'all')
            ->assertSee('Активная камера')
            ->assertSee('Неактивная камера');
    }

    public function test_cameras_are_paginated_and_filter_resets_current_page(): void
    {
        $cameraNames = [
            'Камера 01' => true,
            'Камера 02' => true,
            'Камера 03' => true,
            'Камера 04' => true,
            'Камера 05' => false,
            'Камера 06' => false,
            'Камера 07' => false,
            'Камера 08' => false,
            'Камера 09' => false,
            'Камера 10' => false,
            'Камера 11' => false,
            'Камера 12' => false,
            'Камера 13' => false,
        ];

        foreach ($cameraNames as $cameraName => $status) {
            Camera::factory()->create([
                'name' => $cameraName,
                'is_active' => $status,
            ]);
        }

        $component = Livewire::test('pages::cameras.index')
            ->assertSee('Камера 01')
            ->assertDontSee('Камера 11')
            ->call('gotoPage', 2)
            ->assertSee('Камера 11')
            ->assertDontSee('Камера 01')
            ->assertSet('paginators.page', 2)
            ->set('filter', 'active')
            ->assertSet('paginators.page', 1);
    }

    public function test_editing_camera_loads_its_values(): void
    {
        $camera = Camera::factory()->create([
            'name' => 'Тестовая камера',
            'channel' => 13,
            'snapshot_base_url' => 'http://nvr.test',
            'snapshot_username' => 'snapshot-user',
            'snapshot_password' => 'existing-snapshot-secret',
            'webhook_username' => 'camera-13-events',
            'webhook_password' => 'existing-webhook-secret',
            'is_active' => true,
            'notify_from' => '21:00',
            'notify_until' => '06:00',
        ]);

        Livewire::test('pages::cameras.index')
            ->call('startEditing', $camera->id)
            ->assertSet('editingCameraId', $camera->id)
            ->assertSet('editName', $camera->name)
            ->assertSet('editChannel', '13')
            ->assertSet('editSnapshotBaseUrl', 'http://nvr.test')
            ->assertSet('editSnapshotUsername', 'snapshot-user')
            ->assertSet('editSnapshotPassword', '')
            ->assertSet('editingCameraHasSnapshotPassword', true)
            ->assertSet('editWebhookUsername', 'camera-13-events')
            ->assertSet('editWebhookPassword', '')
            ->assertSet('editingCameraHasWebhookPassword', true)
            ->assertSet('editIsActive', $camera->is_active)
            ->assertSet('editNotifyFrom', '21:00')
            ->assertSet('editNotifyUntil', '06:00');
    }

    public function test_camera_can_be_updated(): void
    {
        $camera = Camera::factory()->create([
            'name' => 'Тестовая камера',
            'channel' => 5,
            'webhook_username' => 'old-camera-events',
            'webhook_password' => 'existing-webhook-secret',
            'is_active' => true,
        ]);

        $originalPasswordHash = $camera->webhook_password;
        $originalSnapshotCiphertext = $camera->getRawOriginal('snapshot_password');

        $component = Livewire::test('pages::cameras.index')
            ->call('startEditing', $camera->id)
            ->set('editName', 'TEST')
            ->set('editChannel', '7')
            ->set('editWebhookUsername', 'new-camera-events')
            ->set('editIsActive', false)
            ->call('updateCamera');

        $component->assertHasNoErrors();

        $this->assertDatabaseHas('cameras', [
            'id' => $camera->id,
            'name' => 'TEST',
            'channel' => 7,
            'webhook_username' => 'new-camera-events',
            'webhook_password' => $originalPasswordHash,
            'is_active' => false,
        ]);

        $component->assertSet('editWebhookPassword', '');
        $component->assertSet('editSnapshotPassword', '');

        $this->assertSame(
            $originalSnapshotCiphertext,
            $camera->fresh()->getRawOriginal('snapshot_password'),
        );

        $this->assertDatabaseMissing('cameras', [
            'id' => $camera->id,
            'name' => 'Тестовая камера',
            'is_active' => true,
        ]);
    }

    public function test_webhook_credentials_can_be_added_to_an_existing_camera(): void
    {
        $camera = Camera::factory()->create([
            'webhook_username' => null,
            'webhook_password' => null,
        ]);

        Livewire::test('pages::cameras.index')
            ->call('startEditing', $camera->id)
            ->set('editWebhookUsername', 'direct-camera-events')
            ->set('editWebhookPassword', 'new-webhook-secret')
            ->call('updateCamera')
            ->assertHasNoErrors()
            ->assertSet('editWebhookPassword', '');

        $camera->refresh();

        $this->assertSame('direct-camera-events', $camera->webhook_username);
        $this->assertTrue(Hash::check('new-webhook-secret', $camera->webhook_password));
    }

    public function test_webhook_username_and_password_must_be_configured_together(): void
    {
        Livewire::test('pages::cameras.index')
            ->set('name', 'Камера без полного доступа')
            ->set('channel', '15')
            ->set('webhookUsername', 'incomplete-camera-events')
            ->call('createCamera')
            ->assertHasErrors(['webhookPassword' => 'required_with']);

        $this->assertDatabaseEmpty('cameras');
    }

    public function test_webhook_password_must_contain_at_least_twelve_characters(): void
    {
        $camera = Camera::factory()->create();

        Livewire::test('pages::cameras.index')
            ->call('startEditing', $camera->id)
            ->set('editWebhookUsername', 'camera-events')
            ->set('editWebhookPassword', 'short')
            ->call('updateCamera')
            ->assertHasErrors(['editWebhookPassword' => 'min'])
            ->assertSee('новый пароль webhook');

        $camera->refresh();

        $this->assertNull($camera->webhook_username);
        $this->assertNull($camera->webhook_password);
    }

    public function test_camera_channel_must_be_unique_for_the_same_snapshot_device(): void
    {
        Camera::factory()->create([
            'channel' => 13,
            'snapshot_base_url' => 'http://nvr.test',
        ]);

        Livewire::test('pages::cameras.index')
            ->set('name', 'Другая камера')
            ->set('channel', '13')
            ->set('snapshotBaseUrl', 'http://nvr.test')
            ->set('snapshotUsername', 'snapshot-user')
            ->set('snapshotPassword', 'snapshot-password')
            ->call('createCamera')
            ->assertHasErrors(['channel' => 'unique']);

        $this->assertDatabaseCount('cameras', 1);
    }

    public function test_same_channel_can_be_used_by_different_snapshot_devices(): void
    {
        Camera::factory()->create([
            'channel' => 1,
            'snapshot_base_url' => 'http://first-camera.test',
        ]);

        Livewire::test('pages::cameras.index')
            ->set('name', 'Вторая камера')
            ->set('channel', '1')
            ->set('snapshotBaseUrl', 'http://second-camera.test')
            ->set('snapshotUsername', 'snapshot-user')
            ->set('snapshotPassword', 'snapshot-password')
            ->call('createCamera')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('cameras', 2);
    }

    public function test_snapshot_base_url_must_not_contain_a_path_or_query(): void
    {
        Livewire::test('pages::cameras.index')
            ->set('name', 'Камера с полным URL')
            ->set('channel', '1')
            ->set('snapshotBaseUrl', 'http://camera.test/cgi-bin/snapshot.cgi?channel=1')
            ->set('snapshotUsername', 'snapshot-user')
            ->set('snapshotPassword', 'snapshot-password')
            ->call('createCamera')
            ->assertHasErrors(['snapshotBaseUrl' => 'regex']);

        $this->assertDatabaseEmpty('cameras');
    }

    public function test_camera_name_is_required_when_updating(): void
    {
        $camera = Camera::factory()->create([
            'name' => 'Тестовая камера №1',
            'is_active' => true,
        ]);

        $component = Livewire::test('pages::cameras.index')
            ->call('startEditing', $camera->id)
            ->set('editName', '')
            ->set('editIsActive', false)
            ->call('updateCamera');

        $component->assertHasErrors([
            'editName' => 'required',
        ]);

        $this->assertDatabaseHas('cameras', [
            'id' => $camera->id,
            'name' => 'Тестовая камера №1',
            'is_active' => true,
        ]);

        $component->assertNotDispatched('modal-close');
    }

    public function test_selecting_camera_for_deletion_loads_its_values(): void
    {
        $camera = Camera::factory()->create([
            'name' => 'Тестовая камера №1',
            'is_active' => true,
        ]);

        Livewire::test('pages::cameras.index')
            ->call('startDeleting', $camera->id)
            ->assertSee(__('Are you sure you want to delete the camera :name?', [
                'name' => $camera->name,
            ]))
            ->assertSet('deletingCameraId', $camera->id)
            ->assertSet('deletingCameraName', $camera->name);

        $this->assertDatabaseHas('cameras', [
            'id' => $camera->id,
            'name' => $camera->name,
            'is_active' => $camera->is_active,
        ]);
    }

    public function test_camera_can_be_deleted(): void
    {
        $camera1 = Camera::factory()->create([
            'name' => 'Тестовая камера №1',
            'is_active' => true,
        ]);

        $camera2 = Camera::factory()->create([
            'name' => 'Вторая тестовая камера',
            'is_active' => false,
        ]);

        $component = Livewire::test('pages::cameras.index')
            ->call('startDeleting', $camera1->id)
            ->call('deleteCamera');

        $this->assertDatabaseMissing('cameras', [
            'id' => $camera1->id,
            'name' => $camera1->name,
            'is_active' => $camera1->is_active,
        ]);

        $component->assertSet('deletingCameraId', null);
        $component->assertSet('deletingCameraName', '');
        $component->assertDispatched('modal-close', name: 'delete-camera');

        $this->assertDatabaseHas('cameras', [
            'id' => $camera2->id,
            'name' => $camera2->name,
            'is_active' => $camera2->is_active,
        ]);
    }

    public function test_editing_camera_without_notification_window_loads_empty_values(): void
    {
        $camera = Camera::factory()->create([
            'name' => 'Тестовая камера',
            'is_active' => true,
            'notify_from' => null,
            'notify_until' => null,
        ]);

        $component = Livewire::test('pages::cameras.index')
            ->call('startEditing', $camera->id);

        $component->assertSet('editNotifyFrom', '');

        $component->assertSet('editNotifyUntil', '');
    }

    public function test_save_camera_window(): void
    {
        $camera = Camera::factory()->create([
            'name' => 'Тестовая камера',
            'is_active' => true,
        ]);

        $component = Livewire::test('pages::cameras.index')
            ->call('startEditing', $camera->id)
            ->set('editNotifyFrom', '21:00')
            ->set('editNotifyUntil', '06:00')
            ->call('updateCamera');

        $component->assertHasNoErrors();

        $this->assertDatabaseHas('cameras', [
            'id' => $camera->id,
            'name' => $camera->name,
            'is_active' => $camera->is_active,
            'notify_from' => '21:00',
            'notify_until' => '06:00',
        ]);
    }

    public function test_camera_notification_window_can_be_cleared(): void
    {
        $camera = Camera::factory()->create([
            'name' => 'Тестовая камера',
            'is_active' => true,
            'notify_from' => '21:00',
            'notify_until' => '06:00',
        ]);

        $component = Livewire::test('pages::cameras.index')
            ->call('startEditing', $camera->id)
            ->set('editNotifyFrom', '')
            ->set('editNotifyUntil', '')
            ->call('updateCamera');

        $component->assertHasNoErrors();

        $this->assertDatabaseHas('cameras', [
            'id' => $camera->id,
            'notify_from' => null,
            'notify_until' => null,
        ]);
    }

    public function test_partial_camera_notification_window_is_rejected(): void
    {
        $camera = Camera::factory()->create([
            'name' => 'Тестовая камера',
            'is_active' => true,
        ]);

        $component = Livewire::test('pages::cameras.index')
            ->call('startEditing', $camera->id)
            ->set('editNotifyFrom', '21:00')
            ->call('updateCamera');

        $component->assertHasErrors(
            ['editNotifyUntil' => 'required_with']
        );

        $this->assertDatabaseHas('cameras', [
            'id' => $camera->id,
            'notify_from' => null,
            'notify_until' => null,
        ]);

        $component->assertNotDispatched('modal-close');
    }

    public function test_equal_camera_notification_window_boundaries_are_rejected(): void
    {
        $camera = Camera::factory()->create([
            'name' => 'Тестовая камера',
            'is_active' => true,
        ]);

        $component = Livewire::test('pages::cameras.index')
            ->call('startEditing', $camera->id)
            ->set('editNotifyFrom', '21:00')
            ->set('editNotifyUntil', '21:00')
            ->call('updateCamera');

        $component->assertHasErrors([
            'editNotifyFrom' => 'different',
        ]);

        $this->assertDatabaseHas('cameras', [
            'id' => $camera->id,
            'notify_from' => null,
            'notify_until' => null,
        ]);

        $component->assertNotDispatched('modal-close');
    }
}
