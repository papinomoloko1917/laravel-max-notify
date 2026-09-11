<?php

namespace Tests\Feature\Models;

use App\Models\Camera;
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
}
