<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\SoapNote;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SoapNote>
 */
class SoapNoteFactory extends Factory
{
    protected $model = SoapNote::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'medical_record_id' => MedicalRecord::factory(),
            'doctor_id' => Doctor::factory(),
            'admission_id' => null,
            'subjective' => fake()->sentences(2, true),
            'objective' => fake()->sentences(2, true),
            'assessment' => fake()->sentence(),
            'plan' => fake()->sentences(2, true),
            'electronic_sign' => null,
            'signed_at' => null,
        ];
    }

    public function signed(): static
    {
        return $this->state(fn (array $attributes) => [
            'electronic_sign' => fake()->name(),
            'signed_at' => now(),
        ]);
    }
}