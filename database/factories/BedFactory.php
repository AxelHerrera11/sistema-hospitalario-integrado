<?php

namespace Database\Factories;

use App\Models\Bed;
use App\Models\Tenant;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bed>
 */
class BedFactory extends Factory
{
    protected $model = Bed::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'ward_id' => Ward::factory(),
            'code' => fake()->unique()->bothify('CAM-###'),
            'status' => fake()->randomElement(['disponible', 'ocupada', 'limpieza', 'mantenimiento']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}

