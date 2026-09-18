<?php

namespace Tests\Feature;

use App\Models\Camera;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->set('is_active', true)
            ->call('createCamera');

        $component->assertHasNoErrors();
        $component->assertSet('name', '');
        $component->assertSee('Камера №1');

        $this->assertDatabaseHas('cameras', [
            'name' => 'Камера №1',
            'is_active' => true,
        ]);
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
            'is_active' => true,
        ]);

        $component = Livewire::test('pages::cameras.index')
            ->call('startEditing', $camera->id)
            ->assertSet('editingCameraId', $camera->id)
            ->assertSet('editName', $camera->name)
            ->assertSet('editIsActive', $camera->is_active);
    }

    public function test_camera_can_be_updated(): void
    {
        $camera = Camera::factory()->create([
            'name' => 'Тестовая камера',
            'is_active' => true,
        ]);

        $component = Livewire::test('pages::cameras.index')
            ->call('startEditing', $camera->id)
            ->set('editName', 'TEST')
            ->set('editIsActive', false)
            ->call('updateCamera');

        $component->assertHasNoErrors();

        $this->assertDatabaseHas('cameras', [
            'id' => $camera->id,
            'name' => 'TEST',
            'is_active' => false,
        ]);

        $this->assertDatabaseMissing('cameras', [
            'id' => $camera->id,
            'name' => 'Тестовая камера',
            'is_active' => true,
        ]);
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
            ->assertSet('deletingCameraId', $camera->id)
            ->assertSet('deletingCameraName', $camera->name);

        $this->assertDatabaseHas('cameras', [
            'id' => $camera->id,
            'name' => $camera->name,
            'is_active' => $camera->is_active,
        ]);
    }
}
