<?php

namespace Tests\Feature;

use App\Models\Diagnosis;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Notas SOAP y diagnósticos — Área 5.
 *
 * Cubre CRUD, firma (inmodificable tras firmar), diagnóstico anidado,
 * validación por hospital y permisos. Sigue el patrón de PatientTest.
 */
class SoapNoteTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->tenant = Tenant::factory()->create(['slug' => 'san-marcos']);
    }

    private function userWithRole(?string $role, ?Tenant $tenant = null): User
    {
        $user = User::factory()->create(['tenant_id' => ($tenant ?? $this->tenant)->id]);

        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user;
    }

    /** @return array<string, string> */
    private function headersFor(User $user, ?Tenant $tenant = null): array
    {
        return [
            'Authorization' => 'Bearer '.JWTAuth::fromUser($user),
            'X-Tenant-ID' => ($tenant ?? $this->tenant)->id,
        ];
    }

    /** @return array{0: User, 1: Doctor, 2: MedicalRecord} */
    private function soapFixture(?Tenant $tenant = null): array
    {
        $tenant = $tenant ?? $this->tenant;
        $doctorUser = $this->userWithRole('Médico', $tenant);
        $specialty = Specialty::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Medicina Interna']);
        $doctor = Doctor::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $doctorUser->id,
            'specialty_id' => $specialty->id,
            'license_number' => 'MED-SM-'.fake()->unique()->numerify('#####'),
        ]);
        $patient = Patient::factory()->create(['tenant_id' => $tenant->id]);
        $record = MedicalRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'patient_id' => $patient->id,
        ]);

        return [$doctorUser, $doctor, $record];
    }

    /** @param array<string, mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'subjective' => 'Paciente refiere dolor de cabeza desde hace dos días.',
            'objective' => 'TA 120/80, FC 72, afebril.',
            'assessment' => 'Migraña sin aura.',
            'plan' => 'Reposo y analgésico; control en 48h.',
        ], $overrides);
    }

    // ── Permisos ───────────────────────────────────────────────────────────

    public function test_soap_endpoints_require_authentication(): void
    {
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/api/v1/soap-notes')->assertStatus(401);

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/soap-notes', $this->payload())->assertStatus(401);
    }

    public function test_receptionist_can_not_manage_soap_notes(): void
    {
        // La Recepcionista no tiene permisos soap.*.
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->getJson('/api/v1/soap-notes')->assertStatus(403);

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson('/api/v1/soap-notes', $this->payload())->assertStatus(403);
    }

    public function test_nurse_can_read_but_cannot_create_nor_sign_notes(): void
    {
        [, $doctor, $record] = $this->soapFixture();
        $doctorUser = $this->userWithRole('Médico');
        $this->withHeaders($this->headersFor($doctorUser))
            ->postJson('/api/v1/soap-notes', $this->payload([
                'medical_record_id' => $record->id,
                'doctor_id' => $doctor->id,
            ]))
            ->assertCreated();

        $nurse = $this->userWithRole('Enfermera');

        $this->withHeaders($this->headersFor($nurse))
            ->getJson('/api/v1/soap-notes')->assertOk();

        $this->withHeaders($this->headersFor($nurse))
            ->postJson('/api/v1/soap-notes', $this->payload([
                'medical_record_id' => $record->id,
                'doctor_id' => $doctor->id,
            ]))
            ->assertStatus(403);
    }

    // ── Crear ──────────────────────────────────────────────────────────────

    public function test_doctor_can_create_a_soap_note(): void
    {
        [$doctorUser, $doctor, $record] = $this->soapFixture();

        $this->withHeaders($this->headersFor($doctorUser))
            ->postJson('/api/v1/soap-notes', $this->payload([
                'medical_record_id' => $record->id,
                'doctor_id' => $doctor->id,
            ]))
            ->assertCreated()
            ->assertJsonPath('soap_note.medical_record_id', $record->id)
            ->assertJsonPath('soap_note.doctor_id', $doctor->id)
            ->assertJsonPath('soap_note.signed_at', null)
            ->assertJsonPath('soap_note.tenant_id', $this->tenant->id);
    }

    public function test_create_requires_soap_fields_and_valid_doctor(): void
    {
        [$doctorUser, , $record] = $this->soapFixture();

        $this->withHeaders($this->headersFor($doctorUser))
            ->postJson('/api/v1/soap-notes', [
                'medical_record_id' => $record->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['doctor_id', 'objective', 'assessment', 'plan']);
    }

    public function test_create_rejects_a_medical_record_of_another_hospital(): void
    {
        $otherTenant = Tenant::factory()->create();
        [, $doctor, $record] = $this->soapFixture();
        // doctor del hospital del fixture, pero expediente de otro hospital.
        $foreignRecord = MedicalRecord::factory()->create([
            'tenant_id' => $otherTenant->id,
            'patient_id' => Patient::factory()->create(['tenant_id' => $otherTenant->id])->id,
        ]);
        $doctorUser = $this->userWithRole('Médico');

        $this->withHeaders($this->headersFor($doctorUser))
            ->postJson('/api/v1/soap-notes', $this->payload([
                'medical_record_id' => $foreignRecord->id,
                'doctor_id' => $doctor->id,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('medical_record_id');
    }

    public function test_create_rejects_a_doctor_of_another_hospital(): void
    {
        $otherTenant = Tenant::factory()->create();
        $foreignDoctorUser = $this->userWithRole('Médico', $otherTenant);
        $specialty = Specialty::factory()->create(['tenant_id' => $otherTenant->id]);
        $foreignDoctor = Doctor::factory()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $foreignDoctorUser->id,
            'specialty_id' => $specialty->id,
            'license_number' => 'MED-X-'.fake()->unique()->numerify('#####'),
        ]);
        [, , $record] = $this->soapFixture();
        $doctorUser = $this->userWithRole('Médico');

        $this->withHeaders($this->headersFor($doctorUser))
            ->postJson('/api/v1/soap-notes', $this->payload([
                'medical_record_id' => $record->id,
                'doctor_id' => $foreignDoctor->id,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('doctor_id');
    }

    public function test_create_ignores_a_tenant_id_sent_by_the_client(): void
    {
        $otherTenant = Tenant::factory()->create();
        [$doctorUser, $doctor, $record] = $this->soapFixture();

        $this->withHeaders($this->headersFor($doctorUser))
            ->postJson('/api/v1/soap-notes', $this->payload([
                'medical_record_id' => $record->id,
                'doctor_id' => $doctor->id,
                'tenant_id' => $otherTenant->id,
            ]))
            ->assertCreated()
            ->assertJsonPath('soap_note.tenant_id', $this->tenant->id);
    }

    // ── Detalle ────────────────────────────────────────────────────────────

    public function test_show_returns_the_note_with_diagnoses(): void
    {
        [$doctorUser, $doctor, $record] = $this->soapFixture();

        $noteId = $this->withHeaders($this->headersFor($doctorUser))
            ->postJson('/api/v1/soap-notes', $this->payload([
                'medical_record_id' => $record->id,
                'doctor_id' => $doctor->id,
            ]))
            ->assertCreated()->json('soap_note.id');

        $this->withHeaders($this->headersFor($doctorUser))
            ->getJson("/api/v1/soap-notes/{$noteId}")
            ->assertOk()
            ->assertJsonPath('soap_note.assessment', 'Migraña sin aura.')
            ->assertJsonPath('soap_note.diagnoses', []);
    }

    public function test_show_returns_404_for_a_note_of_another_hospital(): void
    {
        $otherTenant = Tenant::factory()->create();
        [$foreignDoctorUser, $foreignDoctor, $foreignRecord] = $this->soapFixture($otherTenant);

        $foreignId = $this->withHeaders($this->headersFor($foreignDoctorUser, $otherTenant))
            ->postJson('/api/v1/soap-notes', $this->payload([
                'medical_record_id' => $foreignRecord->id,
                'doctor_id' => $foreignDoctor->id,
            ]))
            ->assertCreated()->json('soap_note.id');

        $doctorUser = $this->userWithRole('Médico');

        $this->withHeaders($this->headersFor($doctorUser))
            ->getJson("/api/v1/soap-notes/{$foreignId}")
            ->assertStatus(404);
    }

    // ── Editar / firmar ────────────────────────────────────────────────────

    public function test_doctor_can_update_a_note_while_unsigned(): void
    {
        [$doctorUser, $doctor, $record] = $this->soapFixture();

        $noteId = $this->withHeaders($this->headersFor($doctorUser))
            ->postJson('/api/v1/soap-notes', $this->payload([
                'medical_record_id' => $record->id,
                'doctor_id' => $doctor->id,
            ]))
            ->assertCreated()->json('soap_note.id');

        $this->withHeaders($this->headersFor($doctorUser))
            ->putJson("/api/v1/soap-notes/{$noteId}", [
                'plan' => 'Reposo, paracetamol cada 8h y control en 24h.',
            ])
            ->assertOk()
            ->assertJsonPath('soap_note.plan', 'Reposo, paracetamol cada 8h y control en 24h.');
    }

    public function test_doctor_can_sign_a_note(): void
    {
        [$doctorUser, $doctor, $record] = $this->soapFixture();
        $headers = $this->headersFor($doctorUser);

        $noteId = $this->withHeaders($headers)
            ->postJson('/api/v1/soap-notes', $this->payload([
                'medical_record_id' => $record->id,
                'doctor_id' => $doctor->id,
            ]))
            ->assertCreated()->json('soap_note.id');

        $this->withHeaders($headers)
            ->postJson("/api/v1/soap-notes/{$noteId}/sign", [
                'electronic_sign' => 'Dr. Juan Pérez',
            ])
            ->assertOk()
            ->assertJsonPath('soap_note.electronic_sign', 'Dr. Juan Pérez')
            ->assertJsonPath('soap_note.signed_at', now()->startOfSecond()->toISOString());
    }

    public function test_signed_note_cannot_be_updated(): void
    {
        [$doctorUser, $doctor, $record] = $this->soapFixture();
        $headers = $this->headersFor($doctorUser);

        $noteId = $this->withHeaders($headers)
            ->postJson('/api/v1/soap-notes', $this->payload([
                'medical_record_id' => $record->id,
                'doctor_id' => $doctor->id,
            ]))
            ->assertCreated()->json('soap_note.id');

        $this->withHeaders($headers)
            ->postJson("/api/v1/soap-notes/{$noteId}/sign", [
                'electronic_sign' => 'Dr. Juan Pérez',
            ])->assertOk();

        $this->withHeaders($headers)
            ->putJson("/api/v1/soap-notes/{$noteId}", ['plan' => 'Cambio ilegal.'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'La nota SOAP ya está firmada y no puede modificarse.');
    }

    public function test_signed_note_cannot_be_signed_again(): void
    {
        [$doctorUser, $doctor, $record] = $this->soapFixture();
        $headers = $this->headersFor($doctorUser);

        $noteId = $this->withHeaders($headers)
            ->postJson('/api/v1/soap-notes', $this->payload([
                'medical_record_id' => $record->id,
                'doctor_id' => $doctor->id,
            ]))
            ->assertCreated()->json('soap_note.id');

        $this->withHeaders($headers)
            ->postJson("/api/v1/soap-notes/{$noteId}/sign", [
                'electronic_sign' => 'Dr. Uno',
            ])->assertOk();

        $this->withHeaders($headers)
            ->postJson("/api/v1/soap-notes/{$noteId}/sign", [
                'electronic_sign' => 'Dr. Dos',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'La nota SOAP ya está firmada.');
    }

    public function test_sign_requires_electronic_sign(): void
    {
        [$doctorUser, $doctor, $record] = $this->soapFixture();
        $headers = $this->headersFor($doctorUser);

        $noteId = $this->withHeaders($headers)
            ->postJson('/api/v1/soap-notes', $this->payload([
                'medical_record_id' => $record->id,
                'doctor_id' => $doctor->id,
            ]))
            ->assertCreated()->json('soap_note.id');

        $this->withHeaders($headers)
            ->postJson("/api/v1/soap-notes/{$noteId}/sign", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('electronic_sign');
    }

    // ── Diagnósticos ───────────────────────────────────────────────────────

    public function test_doctor_can_add_a_diagnosis_to_a_note(): void
    {
        [$doctorUser, $doctor, $record] = $this->soapFixture();
        $headers = $this->headersFor($doctorUser);

        $noteId = $this->withHeaders($headers)
            ->postJson('/api/v1/soap-notes', $this->payload([
                'medical_record_id' => $record->id,
                'doctor_id' => $doctor->id,
            ]))
            ->assertCreated()->json('soap_note.id');

        $this->withHeaders($headers)
            ->postJson("/api/v1/soap-notes/{$noteId}/diagnoses", [
                'cie10_code' => 'G43.9',
                'description' => 'Migraña no especificada',
                'type' => 'principal',
            ])
            ->assertCreated()
            ->assertJsonPath('diagnosis.cie10_code', 'G43.9')
            ->assertJsonPath('diagnosis.soap_note_id', $noteId)
            ->assertJsonPath('diagnosis.tenant_id', $this->tenant->id);
    }

    public function test_add_diagnosis_rejects_a_type_outside_the_enum(): void
    {
        [$doctorUser, $doctor, $record] = $this->soapFixture();
        $headers = $this->headersFor($doctorUser);

        $noteId = $this->withHeaders($headers)
            ->postJson('/api/v1/soap-notes', $this->payload([
                'medical_record_id' => $record->id,
                'doctor_id' => $doctor->id,
            ]))
            ->assertCreated()->json('soap_note.id');

        $this->withHeaders($headers)
            ->postJson("/api/v1/soap-notes/{$noteId}/diagnoses", [
                'cie10_code' => 'G43.9',
                'description' => 'Migraña',
                'type' => 'inexistente',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    }

    public function test_signed_note_does_not_accept_new_diagnoses(): void
    {
        [$doctorUser, $doctor, $record] = $this->soapFixture();
        $headers = $this->headersFor($doctorUser);

        $noteId = $this->withHeaders($headers)
            ->postJson('/api/v1/soap-notes', $this->payload([
                'medical_record_id' => $record->id,
                'doctor_id' => $doctor->id,
            ]))
            ->assertCreated()->json('soap_note.id');

        $this->withHeaders($headers)
            ->postJson("/api/v1/soap-notes/{$noteId}/sign", [
                'electronic_sign' => 'Dr. Juan Pérez',
            ])->assertOk();

        $this->withHeaders($headers)
            ->postJson("/api/v1/soap-notes/{$noteId}/diagnoses", [
                'cie10_code' => 'G43.9',
                'description' => 'Migraña',
            ])
            ->assertUnprocessable();
    }

    public function test_doctor_can_update_and_delete_a_diagnosis(): void
    {
        [$doctorUser, $doctor, $record] = $this->soapFixture();
        $headers = $this->headersFor($doctorUser);

        $noteId = $this->withHeaders($headers)
            ->postJson('/api/v1/soap-notes', $this->payload([
                'medical_record_id' => $record->id,
                'doctor_id' => $doctor->id,
            ]))
            ->assertCreated()->json('soap_note.id');

        $diagnosisId = $this->withHeaders($headers)
            ->postJson("/api/v1/soap-notes/{$noteId}/diagnoses", [
                'cie10_code' => 'G43.9',
                'description' => 'Migraña sin aura',
                'type' => 'presuntivo',
            ])
            ->assertCreated()->json('diagnosis.id');

        $this->withHeaders($headers)
            ->putJson("/api/v1/diagnoses/{$diagnosisId}", [
                'type' => 'definitivo',
                'description' => 'Migraña con aura',
            ])
            ->assertOk()
            ->assertJsonPath('diagnosis.type', 'definitivo')
            ->assertJsonPath('diagnosis.description', 'Migraña con aura');

        $this->withHeaders($headers)
            ->deleteJson("/api/v1/diagnoses/{$diagnosisId}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('diagnoses', ['id' => $diagnosisId]);
    }

    public function test_diagnosis_of_another_hospital_returns_404_on_update(): void
    {
        $otherTenant = Tenant::factory()->create();
        [$foreignDoctorUser, $foreignDoctor, $foreignRecord] = $this->soapFixture($otherTenant);

        $foreignNoteId = $this->withHeaders($this->headersFor($foreignDoctorUser, $otherTenant))
            ->postJson('/api/v1/soap-notes', $this->payload([
                'medical_record_id' => $foreignRecord->id,
                'doctor_id' => $foreignDoctor->id,
            ]))
            ->assertCreated()->json('soap_note.id');

        $foreignDiagId = $this->withHeaders($this->headersFor($foreignDoctorUser, $otherTenant))
            ->postJson("/api/v1/soap-notes/{$foreignNoteId}/diagnoses", [
                'cie10_code' => 'G43.9',
                'description' => 'Migraña',
            ])
            ->assertCreated()->json('diagnosis.id');

        $doctorUser = $this->userWithRole('Médico');

        $this->withHeaders($this->headersFor($doctorUser))
            ->putJson("/api/v1/diagnoses/{$foreignDiagId}", ['description' => 'Intrusión'])
            ->assertStatus(404);
    }

    // ── Listado ────────────────────────────────────────────────────────────

    public function test_list_filters_by_doctor_and_paginates(): void
    {
        [$doctorUser, $doctor, $record] = $this->soapFixture();
        $headers = $this->headersFor($doctorUser);

        for ($i = 1; $i <= 3; $i++) {
            $this->withHeaders($headers)
                ->postJson('/api/v1/soap-notes', $this->payload([
                    'medical_record_id' => $record->id,
                    'doctor_id' => $doctor->id,
                ]))->assertCreated();
        }

        $this->withHeaders($headers)
            ->getJson('/api/v1/soap-notes?doctor_id='.$doctor->id.'&per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('total', 3)
            ->assertJsonPath('per_page', 2);

        $this->withHeaders($headers)
            ->getJson('/api/v1/soap-notes?per_page=500')
            ->assertOk()
            ->assertJsonPath('per_page', 50);
    }

    public function test_list_does_not_leak_notes_of_another_tenant(): void
    {
        [$doctorUser, $doctor, $record] = $this->soapFixture();
        $headers = $this->headersFor($doctorUser);

        $this->withHeaders($headers)
            ->postJson('/api/v1/soap-notes', $this->payload([
                'medical_record_id' => $record->id,
                'doctor_id' => $doctor->id,
            ]))->assertCreated();

        $otherTenant = Tenant::factory()->create();
        [$foreignDoctorUser, $foreignDoctor, $foreignRecord] = $this->soapFixture($otherTenant);

        $this->withHeaders($this->headersFor($foreignDoctorUser, $otherTenant))
            ->postJson('/api/v1/soap-notes', $this->payload([
                'medical_record_id' => $foreignRecord->id,
                'doctor_id' => $foreignDoctor->id,
            ]))->assertCreated();

        $this->withHeaders($headers)
            ->getJson('/api/v1/soap-notes')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}