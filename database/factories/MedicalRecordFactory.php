<?php

namespace Database\Factories;

use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicalRecord>
 */
class MedicalRecordFactory extends Factory
{
    protected $model = MedicalRecord::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'patient_id' => Patient::factory(),
            'record_number' => fake()->unique()->bothify('EXP-#####'),
            'opened_at' => fake()->date(),
            'background' => fake()->optional()->paragraph(),
            'family_background' => fake()->optional()->paragraph(),
            'surgical_history' => fake()->optional()->sentence(),
            'obstetric_history' => fake()->optional()->sentence(),
        ];
    }
}

