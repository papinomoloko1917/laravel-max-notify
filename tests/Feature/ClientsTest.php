<?php

namespace Tests\Feature;

use App\Models\Camera;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_clients_page(): void
    {
        $response = $this->get(route('clients.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_clients_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('clients.index'));

        $response->assertOk();

        $response->assertSee(__('Clients'));
    }

    public function test_authenticated_user_sees_clients_ordered_with_chat_ids_and_camera_counts(): void
    {

        $client1 = Client::factory()->create([
            'name' => 'Яндекс',
            'max_chat_id' => 3000,
        ]);

        $client2 = Client::factory()->create([
            'name' => 'Альфа',
            'max_chat_id' => 1000,
        ]);

        $camera1 = Camera::factory()->create(
            [
                'name' => 'Камера №1',
                'is_active' => true,
            ]
        );

        $camera2 = Camera::factory()->create(
            [
                'name' => 'Камера №2',
                'is_active' => false,
            ]
        );

        $client1->cameras()->attach($camera1->id);

        $client2->cameras()->attach($camera2->id);
        $client2->cameras()->attach($camera1->id);

        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('clients.index'));

        $response->assertOk();

        $response->assertSeeInOrder([
            $client2->name,
            (string) $client2->max_chat_id,
            (string) $client2->cameras()->count(),
            $client1->name,
            (string) $client1->max_chat_id,
            (string) $client1->cameras()->count(),
        ]);
    }

    public function test_authenticated_user_sees_empty_state_when_no_clients_exist(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('clients.index'));

        $response->assertSee(__('The list of clients is empty...'));
    }

    public function test_successfully_create_client(): void
    {
        $component = Livewire::test('pages::clients.index')
            ->set('name', 'Test')
            ->set('max_chat_id', 123)
            ->call('createClient');

        $component->assertHasNoErrors();

        $this->assertDatabaseHas('clients', [
            'name' => 'Test',
            'max_chat_id' => 123,
        ]);

        $component->assertSet('name', '');
        $component->assertSet('max_chat_id', '');
        $component->assertNotDispatched('modal-close', name: 'create-client');
    }

    public function test_mandatory_field(): void
    {
        $component = Livewire::test('pages::clients.index')
            ->call('createClient');

        $component->assertHasErrors([
            'name' => 'required',
            'max_chat_id' => 'required',
        ]);

        $this->assertDatabaseEmpty('clients');
    }

    public function test_integer_for_max_chat_id(): void
    {
        $component = Livewire::test('pages::clients.index')
            ->set('name', 'Test')
            ->set('max_chat_id', 'abc')
            ->call('createClient');

        $component->assertHasErrors([
            'max_chat_id' => 'integer',
        ]);

        $this->assertDatabaseEmpty('clients');
    }

    public function test_max_chat_id_must_be_unique(): void
    {
        $client = Client::factory()->create([
            'name' => 'Яндекс',
            'max_chat_id' => 3000,
        ]);

        $component = Livewire::test('pages::clients.index')
            ->set('name', 'Max')
            ->set('max_chat_id', 3000)
            ->call('createClient');

        $component->assertHasErrors([
            'max_chat_id' => 'unique',
        ]);

        $this->assertDatabaseCount('clients', 1);

        $this->assertDatabaseHas('clients', [
            'name' => $client->name,
            'max_chat_id' => $client->max_chat_id,
        ]);

        $this->assertDatabaseMissing('clients', [
            'name' => 'Max',
            'max_chat_id' => 3000,
        ]);
    }

    public function test_editing_client_loads_its_values(): void
    {

        $client = Client::factory()->create();

        $camera1 = Camera::factory()->create();

        $camera2 = Camera::factory()->create();

        $client->cameras()->attach($camera1->id);

        $component = Livewire::test('pages::clients.index')
            ->call('startEditing', $client->id);

        $component->assertSet('editingClientId', $client->id);

        $component->assertSet('editName', $client->name);

        $component->assertSet('editMaxChatId', (string) $client->max_chat_id);

        $component->assertSet('editCameraIds', [$camera1->id]);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => $client->name,
            'max_chat_id' => $client->max_chat_id,
        ]);
    }

    public function test_client_can_be_updated(): void
    {
        $client = Client::factory()->create();

        $camera1 = Camera::factory()->create();

        $camera2 = Camera::factory()->create();

        $client->cameras()->attach($camera1->id);

        $component = Livewire::test('pages::clients.index')
            ->call('startEditing', $client->id)
            ->set('editName', 'Test123')
            ->set('editCameraIds', [$camera2->id])
            ->set('editMaxChatId', 7171177)
            ->call('updateClient');

        $this->assertDatabaseHas('camera_client', [
            'camera_id' => $camera2->id,
            'client_id' => $client->id,
        ]);

        $this->assertDatabaseMissing('camera_client', [
            'camera_id' => $camera1->id,
            'client_id' => $client->id,
        ]);

        $component->assertHasNoErrors();

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Test123',
            'max_chat_id' => 7171177,
        ]);

        $component->assertSet('editingClientId', null);

        $component->assertSet('editName', '');

        $component->assertSet('editMaxChatId', '');

        $component->assertSet('editCameraIds', []);

        $component->assertDispatched('modal-close', name: 'edit-client');
    }

    public function test_client_name_is_required_when_updating(): void
    {
        $client = Client::factory()->create();

        $component = Livewire::test('pages::clients.index')
            ->call('startEditing', $client->id)
            ->set('editName', '')
            ->call('updateClient');

        $component->assertHasErrors(['editName' => 'required']);

        $component->assertSet('editingClientId', $client->id);

        $component->assertSet('editMaxChatId', (string) $client->max_chat_id);

        $component->assertSet('editName', '');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => $client->name,
            'max_chat_id' => $client->max_chat_id,
        ]);

        $component->assertNotDispatched('modal-close', name: 'edit-client');
    }

    public function test_another_clients_max_chat_id_cannot_be_used_when_updating(): void
    {
        $client1 = Client::factory()->create([
            'name' => 'Первый',
            'max_chat_id' => 100,
        ]);

        $client2 = Client::factory()->create([
            'name' => 'Второй',
            'max_chat_id' => 200,
        ]);

        $component = Livewire::test('pages::clients.index')
            ->call('startEditing', $client1->id)
            ->set('editName', 'Новое имя')
            ->set('editMaxChatId', '200')
            ->call('updateClient');

        $component->assertHasErrors(['editMaxChatId' => 'unique']);

        $this->assertDatabaseCount('clients', 2);

        $this->assertDatabaseHas('clients', [
            'name' => 'Первый',
            'max_chat_id' => 100,
        ]);

        $this->assertDatabaseHas('clients', [
            'name' => 'Второй',
            'max_chat_id' => 200,
        ]);

        $component->assertSet('editingClientId', $client1->id);
        $component->assertSet('editName', 'Новое имя');
        $component->assertSet('editMaxChatId', '200');

        $component->assertNotDispatched('modal-close', name: 'edit-client');
    }

    public function test_user_changes_the_clients_name_but_keeps_the_max_chat_id(): void
    {
        $client = Client::factory()->create();

        $component = Livewire::test('pages::clients.index')
            ->call('startEditing', $client->id)
            ->set('editName', 'Новое имя')
            ->call('updateClient');

        $component->assertHasNoErrors();

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Новое имя',
            'max_chat_id' => $client->max_chat_id,
        ]);

        $component->assertSet('editingClientId', null);

        $component->assertSet('editName', '');

        $component->assertSet('editMaxChatId', '');

        $component->assertDispatched('modal-close', name: 'edit-client');
    }

    public function test_selecting_client_for_deletion_loads_its_values(): void
    {
        $client = Client::factory()->create();

        $this->assertDatabaseCount('clients', 1);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
        ]);

        $component = Livewire::test('pages::clients.index')
            ->call('startDeleting', $client->id)
            ->assertSee(__('Are you sure you want to delete the client :name?', [
                'name' => $client->name,
            ]));

        $component->assertHasNoErrors();

        $component->assertSet('deletingClientId', $client->id);
        $component->assertSet('deletingClientName', $client->name);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
        ]);
    }

    public function test_client_can_be_deleted(): void
    {
        $client1 = Client::factory()->create([
            'name' => 'Первый клиент',
            'max_chat_id' => 100,
        ]);

        $client2 = Client::factory()->create([
            'name' => 'Второй клиент',
            'max_chat_id' => 200,
        ]);

        $component = Livewire::test('pages::clients.index')
            ->call('startDeleting', $client1->id)
            ->call('deleteClient');

        $this->assertDatabaseMissing('clients', [
            'id' => $client1->id,
            'name' => $client1->name,
            'max_chat_id' => $client1->max_chat_id,
        ]);

        $this->assertDatabaseHas('clients', [
            'id' => $client2->id,
            'name' => $client2->name,
            'max_chat_id' => $client2->max_chat_id,
        ]);

        $this->assertDatabaseCount('clients', 1);

        $component->assertSet('deletingClientId', null);
        $component->assertSet('deletingClientName', '');

        $component->assertDispatched('modal-close', name: 'delete-client');
    }

    public function test_nonexistent_camera_cannot_be_assigned_when_updating_client(): void
    {
        $client = Client::factory()->create();

        $camera = Camera::factory()->create();

        $client->cameras()->attach($camera->id);

        $component = Livewire::test('pages::clients.index')
            ->call('startEditing', $client->id)
            ->set('editName', 'Новое имя')
            ->set('editCameraIds', [999999])
            ->call('updateClient');

        $component->assertHasErrors([
            'editCameraIds.0' => 'exists',
        ]);

        $this->assertDatabaseHas('clients', [
            'name' => $client->name,
        ]);

        $this->assertDatabaseHas('camera_client', [
            'client_id' => $client->id,
            'camera_id' => $camera->id,
        ]);

        $component->assertSet('editingClientId', $client->id);

        $component->assertSet('editName', 'Новое имя');

        $component->assertSet('editCameraIds', [999999]);

        $component->assertNotDispatched('modal-close', name: 'edit-client');
    }
}
