<?php

namespace Database\Factories;

use App\Models\MedicalRecord;
use App\Models\SoapNote;
use App\Models\Tenant;
use App\Models\VitalSign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VitalSign>
 */
class VitalSignFactory extends Factory
{
    protected $model = VitalSign::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'medical_record_id' => MedicalRecord::factory(),
            'admission_id' => null,
            'measured_at' => now(),
            'temperature' => fake()->randomFloat(1, 36, 38),
            'heart_rate' => fake()->numberBetween(60, 100),
            'respiratory_rate' => fake()->numberBetween(12, 20),
            'systolic_bp' => fake()->numberBetween(90, 140),
            'diastolic_bp' => fake()->numberBetween(60, 90),
            'oxygen_saturation' => fake()->randomFloat(2, 94, 100),
            'has_alert' => false,
            'alert_details' => null,
        ];
    }
}