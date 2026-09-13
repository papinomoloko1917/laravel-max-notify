<?php

namespace Tests\Feature\Models;

use App\Models\Camera;
use App\Models\Client;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CameraTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_camera(): void
    {
        $camera = Camera::factory()->create([
            'name' => 'Камера 1',
            'is_active' => true,
        ]);

        $camera = Camera::findOrFail($camera->id);

        $this->assertDatabaseHas(
            'cameras',
            [
                'name' => $camera->name,
                'is_active' => $camera->is_active,
            ]
        );

        $this->assertIsBool($camera->is_active);
        $this->assertTrue($camera->is_active);
    }

    public function test_same_camera_client_pair_cannot_be_attached_twice(): void
    {
        $client = Client::create([
            'name' => 'testName',
            'max_chat_id' => 7,
        ]);

        $camera = Camera::create([
            'name' => 'testCamera',
            'is_active' => true,
        ]);

        $client->cameras()->attach($camera->id);

        $this->expectException(QueryException::class);

        $client->cameras()->attach($camera->id);
    }

    public function test_deleting_a_camera_detaches_it_from_clients_but_keeps_clients(): void
    {
        $client = Client::create([
            'name' => 'testName',
            'max_chat_id' => 7,
        ]);

        $camera = Camera::create([
            'name' => 'testCamera',
            'is_active' => true,
        ]);

        $client->cameras()->attach($camera->id);

        $camera->delete();

        $this->assertDatabaseMissing('camera_client', [
            'camera_id' => $camera->id,
            'client_id' => $client->id,
        ]);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
        ]);
    }
}
