<?php

namespace Database\Factories;

use App\Models\Camera;
use App\Models\CameraEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CameraEvent>
 */
class CameraEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'camera_id' => Camera::factory(),
            'camera_name' => fake()->name(),
            'channel' => fake()->numberBetween(1, 64),
            'rule' => fake()->text(100),
            'status' => 'pending',
            'recipients_total' => 0,
            'recipients_sent' => 0,
            'failure_type' => null,
            'processed_at' => null,
        ];
    }
}
