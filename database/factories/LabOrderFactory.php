<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\LabOrder;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\SoapNote;
use App\Models\Specialty;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Paciente, expediente, médico y nota SOAP se crean en el mismo hospital que
 * la orden (hallazgo H-03 de docs/modulo-laboratorio.md).
 *
 * @extends Factory<LabOrder>
 */
class LabOrderFactory extends Factory
{
    protected $model = LabOrder::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'patient_id' => fn (array $attributes) => Patient::factory()
                ->state(['tenant_id' => $attributes['tenant_id']]),
            'medical_record_id' => fn (array $attributes) => MedicalRecord::factory()->state([
                'tenant_id' => $attributes['tenant_id'],
                'patient_id' => $attributes['patient_id'],
            ]),
            'ordered_by' => fn (array $attributes) => User::factory()
                ->state(['tenant_id' => $attributes['tenant_id']]),
            'soap_note_id' => fn (array $attributes) => $this->soapNoteFor($attributes),
            'code' => fake()->unique()->numerify('LAB-FAC-2026####'),
            'priority' => 'rutina',
            'status' => 'pendiente',
            'clinical_info' => fake()->optional()->sentence(),
            'ordered_at' => now(),
        ];
    }

    /**
     * TODO(#11): usar SoapNote::factory() cuando el área 5 publique
     * SoapNoteFactory. Mientras tanto la nota se crea aquí, firmada.
     */
    private function soapNoteFor(array $attributes): int
    {
        $doctor = Doctor::factory()->create([
            'tenant_id' => $attributes['tenant_id'],
            'user_id' => $attributes['ordered_by'],
            'specialty_id' => Specialty::factory()->create(['tenant_id' => $attributes['tenant_id']])->id,
        ]);

        return SoapNote::query()->create([
            'tenant_id' => $attributes['tenant_id'],
            'medical_record_id' => $attributes['medical_record_id'],
            'doctor_id' => $doctor->id,
            'subjective' => 'Paciente refiere malestar general.',
            'objective' => 'Signos vitales estables.',
            'assessment' => 'En estudio.',
            'plan' => 'Exámenes de laboratorio.',
            'signed_at' => now(),
        ])->id;
    }
}
