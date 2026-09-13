<?php

namespace Tests\Feature\Models;

use App\Models\Camera;
use App\Models\Client;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_client(): void
    {
        $client = Client::create([
            'name' => 'Max',
            'max_chat_id' => 7,
        ]);

        $reloadedClient = Client::findOrFail($client->id);

        $this->assertDatabaseHas('clients', [
            'name' => $reloadedClient->name,
            'max_chat_id' => $reloadedClient->max_chat_id,
        ]);

        $this->assertIsInt($reloadedClient->max_chat_id);
        $this->assertSame(7, $reloadedClient->max_chat_id);
    }

    public function test_unique_constraint(): void
    {
        Client::create([
            'name' => 'testClient1',
            'max_chat_id' => 10,
        ]);

        $this->expectException(QueryException::class);

        Client::create([
            'name' => 'testClient2',
            'max_chat_id' => 10,
        ]);
    }

    public function test_client_attaches_to_the_camera_and_reads_it_from_both_sides(): void
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

        $this->assertDatabaseHas('camera_client', [
            'camera_id' => $camera->id,
            'client_id' => $client->id,
        ]);

        $client = Client::findOrFail($client->id);

        $camera = Camera::findOrFail($camera->id);

        $this->assertTrue($client->cameras->contains('id', $camera->id));

        $this->assertTrue($camera->clients->contains('id', $client->id));
    }

    public function test_deleting_a_client_detaches_it_from_cameras_but_keeps_cameras(): void
    {
        $client = Client::create([
            'name' => 'testName',
            'max_chat_id' => 7,
        ]);

        $camera = Camera::create([
            'name' => 'testCamera',
            'is_active' => true,
        ]);

        $camera->clients()->attach($client->id);

        $client->delete();

        $this->assertDatabaseMissing('camera_client', [
            'camera_id' => $camera->id,
            'client_id' => $client->id,
        ]);

        $this->assertDatabaseHas('cameras', [
            'id' => $camera->id,
        ]);
    }
}
