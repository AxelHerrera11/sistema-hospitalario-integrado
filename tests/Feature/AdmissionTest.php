<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\Bed;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Ward;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdmissionTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->tenant = Tenant::factory()->create([
            'slug' => 'san-marcos',
        ]);
    }

    private function userWithRole(?string $role): User
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user;
    }

    /** @return array<string, string> */
    private function headersFor(User $user): array
    {
        return [
            'Authorization' => 'Bearer '.JWTAuth::fromUser($user),
            'X-Tenant-ID' => $this->tenant->id,
        ];
    }

    private function makePatient(bool $withMedicalRecord = true): Patient
    {
        $patient = Patient::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        if ($withMedicalRecord) {
            MedicalRecord::factory()->create([
                'tenant_id' => $this->tenant->id,
                'patient_id' => $patient->id,
            ]);
        }

        return $patient;
    }

    private function makeBed(string $status = 'disponible'): Bed
    {
        $ward = Ward::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        return Bed::factory()->create([
            'tenant_id' => $this->tenant->id,
            'ward_id' => $ward->id,
            'status' => $status,
        ]);
    }

    private function makeDoctor(): Doctor
    {
        $doctorUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $specialty = Specialty::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        return Doctor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $doctorUser->id,
            'specialty_id' => $specialty->id,
        ]);
    }

    public function test_can_create_admission_and_mark_bed_as_occupied(): void
    {
        $patient = $this->makePatient();
        $bed = $this->makeBed();
        $doctor = $this->makeDoctor();
        $user = $this->userWithRole('Recepcionista');

        $response = $this->withHeaders($this->headersFor($user))
            ->postJson('/api/v1/admissions', [
                'patient_id' => $patient->id,
                'bed_id' => $bed->id,
                'doctor_id' => $doctor->id,
            ])
            ->assertCreated()
            ->assertJsonPath('admission.patient.id', $patient->id)
            ->assertJsonPath('admission.bed.id', $bed->id)
            ->assertJsonPath('admission.bed.status', 'ocupada')
            ->assertJsonPath('admission.doctor.id', $doctor->id)
            ->assertJsonPath('admission.status', 'activa');

        $this->assertMatchesRegularExpression(
            '/^ADM-SANMAR-\d{8}$/',
            $response->json('admission.code')
        );

        $this->assertDatabaseHas('admissions', [
            'patient_id' => $patient->id,
            'bed_id' => $bed->id,
            'doctor_id' => $doctor->id,
            'admitted_by' => $user->id,
            'status' => 'activa',
        ]);

        $this->assertDatabaseHas('beds', [
            'id' => $bed->id,
            'status' => 'ocupada',
        ]);
    }

    public function test_patient_without_medical_record_cannot_be_admitted(): void
    {
        $patient = $this->makePatient(false);
        $bed = $this->makeBed();
        $doctor = $this->makeDoctor();
        $user = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($user))
            ->postJson('/api/v1/admissions', [
                'patient_id' => $patient->id,
                'bed_id' => $bed->id,
                'doctor_id' => $doctor->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('patient_id');

        $this->assertDatabaseMissing('admissions', [
            'patient_id' => $patient->id,
        ]);

        $this->assertDatabaseHas('beds', [
            'id' => $bed->id,
            'status' => 'disponible',
        ]);
    }

    public function test_patient_with_active_admission_cannot_be_admitted_again(): void
    {
        $patient = $this->makePatient();
        $firstBed = $this->makeBed('ocupada');
        $secondBed = $this->makeBed();
        $doctor = $this->makeDoctor();
        $user = $this->userWithRole('Recepcionista');

        Admission::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_id' => $patient->id,
            'bed_id' => $firstBed->id,
            'doctor_id' => $doctor->id,
            'admitted_by' => $user->id,
            'code' => 'ADM-SANMAR-20260001',
            'admitted_at' => now(),
            'discharged_at' => null,
            'status' => 'activa',
            'discharge_type' => null,
            'discharge_notes' => null,
        ]);

        $this->withHeaders($this->headersFor($user))
            ->postJson('/api/v1/admissions', [
                'patient_id' => $patient->id,
                'bed_id' => $secondBed->id,
                'doctor_id' => $doctor->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('patient_id');

        $this->assertDatabaseHas('beds', [
            'id' => $secondBed->id,
            'status' => 'disponible',
        ]);
    }

    public function test_cannot_admit_patient_into_unavailable_bed(): void
    {
        $patient = $this->makePatient();
        $bed = $this->makeBed('mantenimiento');
        $doctor = $this->makeDoctor();
        $user = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($user))
            ->postJson('/api/v1/admissions', [
                'patient_id' => $patient->id,
                'bed_id' => $bed->id,
                'doctor_id' => $doctor->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('bed_id');

        $this->assertDatabaseMissing('admissions', [
            'patient_id' => $patient->id,
        ]);
    }

    public function test_user_without_permission_cannot_create_admission(): void
    {
        $patient = $this->makePatient();
        $bed = $this->makeBed();
        $doctor = $this->makeDoctor();
        $user = $this->userWithRole(null);

        $this->withHeaders($this->headersFor($user))
            ->postJson('/api/v1/admissions', [
                'patient_id' => $patient->id,
                'bed_id' => $bed->id,
                'doctor_id' => $doctor->id,
            ])
            ->assertStatus(403);

        $this->assertDatabaseMissing('admissions', [
            'patient_id' => $patient->id,
        ]);
    }

    public function test_admission_requires_authentication(): void
    {
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/admissions', [])
            ->assertStatus(401);
    }

    public function test_bed_cannot_be_marked_as_occupied_manually(): void
    {
        $bed = $this->makeBed('disponible');
        $user = $this->userWithRole('Admin');

        $this->withHeaders($this->headersFor($user))
            ->patchJson("/api/v1/beds/{$bed->id}/status", [
                'status' => 'ocupada',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('beds', [
            'id' => $bed->id,
            'status' => 'disponible',
        ]);
    }

    public function test_bed_with_active_admission_cannot_be_changed_manually(): void
    {
        $patient = $this->makePatient();
        $bed = $this->makeBed('ocupada');
        $doctor = $this->makeDoctor();
        $user = $this->userWithRole('Admin');

        Admission::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_id' => $patient->id,
            'bed_id' => $bed->id,
            'doctor_id' => $doctor->id,
            'admitted_by' => $user->id,
            'code' => 'ADM-SANMAR-20260001',
            'admitted_at' => now(),
            'discharged_at' => null,
            'status' => 'activa',
            'discharge_type' => null,
            'discharge_notes' => null,
        ]);

        $this->withHeaders($this->headersFor($user))
            ->patchJson("/api/v1/beds/{$bed->id}/status", [
                'status' => 'disponible',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('beds', [
            'id' => $bed->id,
            'status' => 'ocupada',
        ]);
    }
}
