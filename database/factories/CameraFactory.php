<?php

namespace Database\Factories;

use App\Models\Camera;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Camera>
 */
class CameraFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'snapshot_base_url' => 'http://camera.test',
            'snapshot_username' => 'snapshot-user',
            'snapshot_password' => 'snapshot-password',
            'is_active' => fake()->boolean(50),
        ];
    }
}
