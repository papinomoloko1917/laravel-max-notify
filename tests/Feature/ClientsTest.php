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
}
