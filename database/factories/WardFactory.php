<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ward>
 */
class WardFactory extends Factory
{
    protected $model = Ward::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->randomElement(['Emergencias', 'UCI', 'Pediatría', 'Cirugía', 'Medicina Interna']),
            'floor' => (string) fake()->numberBetween(1, 5),
            'building' => fake()->randomElement(['A', 'B', 'C']),
        ];
    }
}

