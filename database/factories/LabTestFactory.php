<?php

namespace Database\Factories;

use App\Models\LabTest;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Por defecto genera una prueba numérica con rangos tipo glucosa.
 *
 * @extends Factory<LabTest>
 */
class LabTestFactory extends Factory
{
    protected $model = LabTest::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => 'Prueba '.fake()->unique()->numerify('#####'),
            'category' => 'Química',
            'unit' => 'mg/dL',
            'reference_min' => 70,
            'reference_max' => 110,
            'critical_min' => 40,
            'critical_max' => 400,
            'turnaround_min' => 60,
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }

    /** Prueba de resultado en texto (cultivos, tipificación…). */
    public function textual(): static
    {
        return $this->state([
            'unit' => null,
            'reference_min' => null,
            'reference_max' => null,
            'critical_min' => null,
            'critical_max' => null,
        ]);
    }
}
