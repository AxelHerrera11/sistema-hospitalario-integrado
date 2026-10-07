<?php

namespace Tests\Unit;

use App\Http\Resources\PatientSummaryResource;
use App\Models\Patient;
use Tests\TestCase;

class PatientSummaryResourceTest extends TestCase
{
    private function resumen(): array
    {
        $patient = (new Patient)->forceFill([
            'id' => 7,
            'code' => 'P001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birth_date' => '1990-01-01',
            'gender' => 'M',
            'dpi' => '1234567890101',
            'phone' => '555-1234',
            'email' => 'john.doe@example.com',
            'address' => '123 Main St',
            'notes' => 'Nota clinica privada',
        ]);

        return (new PatientSummaryResource($patient))->resolve();
    }

    public function test_expone_solo_los_campos_minimos(): void
    {
        $this->assertSame([
            'id', 'code', 'first_name', 'last_name', 'birth_date', 'gender'],
            array_keys($this->resumen())
        );
    }

    public function test_no_expone_datos_sensibles(): void
    {
        $data = $this->resumen();
        foreach (['tenant_id', 'dpi', 'nit', 'phone', 'email', 'address',
            'insurance_company', 'insurance_policy', 'emergency_contact_name',
            'emergency_contact_phone', 'blood_type', 'notes'] as $campo) {
            $this->assertArrayNotHasKey($campo, $data);
        }
    }

    public function test_fecha_de_nacimiento_sin_hora(): void
    {
        $this->assertSame('1990-01-01', $this->resumen()['birth_date']);
    }
}
