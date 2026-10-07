<?php

namespace Database\Factories;

use App\Models\Allergy;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Allergy>
 */
class AllergyFactory extends Factory
{
    protected $model = Allergy::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'patient_id' => fn (array $attributes) => Patient::factory()
                ->state(['tenant_id' => $attributes['tenant_id']]),
            'registered_by' => fn (array $attributes) => User::factory()
                ->state(['tenant_id' => $attributes['tenant_id']]),
            'allergen' => fake()->randomElement(['Penicilina', 'Aspirina', 'Mani', 'Polen']),
            'allergen_type' => fake()->randomElement(['medicamento', 'alimento', 'ambiental', 'otro']),
            'severity' => fake()->randomElement(['leve', 'moderada', 'grave', 'anafilaxis']),
            'reaction' => fake()->optional()->sentence(),
            'active' => true,
        ];
    }
}
