<?php

namespace Database\Factories;

use App\Models\Specialty;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Specialty>
 */
class SpecialtyFactory extends Factory
{
    protected $model = Specialty::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->randomElement([
                'Medicina Interna',
                'Pediatría',
                'Ginecología',
                'Cirugía General',
                'Cardiología',
                'Medicina Familiar',
            ]),
            'description' => fake()->optional()->sentence(10),
        ];
    }
}

