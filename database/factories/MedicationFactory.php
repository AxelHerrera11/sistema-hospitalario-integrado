<?php

namespace Database\Factories;

use App\Models\Medication;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medication>
 */
class MedicationFactory extends Factory
{
    protected $model = Medication::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => 'Medicamento '.fake()->unique()->numerify('#####'),
            'generic_name' => fake()->randomElement(['Amoxicilina', 'Ibuprofeno', 'Metformina']),
            'category' => fake()->randomElement(['Antibiótico', 'Analgésico', 'Antidiabético']),
            'presentation' => fake()->randomElement(['Tableta', 'Cápsula', 'Ampolla']),
            'concentration' => fake()->randomElement(['500 mg', '250 mg/5 ml', '1 g']),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
