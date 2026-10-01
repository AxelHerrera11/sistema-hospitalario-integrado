<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class MedicalSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->tenant = Tenant::factory()->create();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->assignRole($role);

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

    public function test_admin_can_create_specialty_and_doctor_profile(): void
    {
        $admin = $this->userWithRole('Admin');
        $doctorUser = $this->userWithRole('Médico');

        $specialtyId = $this->withHeaders($this->headersFor($admin))
            ->postJson('/api/v1/specialties', [
                'name' => 'Cardiología',
                'description' => 'Atención cardiovascular',
            ])
            ->assertCreated()
            ->json('specialty.id');

        $this->withHeaders($this->headersFor($admin))
            ->postJson('/api/v1/doctors', [
                'user_id' => $doctorUser->id,
                'specialty_id' => $specialtyId,
                'license_number' => 'MED-SM-01001',
                'phone' => '5555-1111',
            ])
            ->assertCreated()
            ->assertJsonPath('doctor.user.email', $doctorUser->email)
            ->assertJsonPath('doctor.specialty.name', 'Cardiología');

        $this->assertDatabaseHas('doctors', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $doctorUser->id,
            'license_number' => 'MED-SM-01001',
        ]);
    }

    public function test_receptionist_cannot_manage_specialties(): void
    {
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson('/api/v1/specialties', [
                'name' => 'Neurología',
            ])
            ->assertStatus(403);
    }

    public function test_receptionist_can_create_appointment_for_matching_doctor_specialty(): void
    {
        $receptionist = $this->userWithRole('Recepcionista');
        $doctorUser = $this->userWithRole('Médico');
        $specialty = Specialty::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Pediatría']);
        $doctor = Doctor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $doctorUser->id,
            'specialty_id' => $specialty->id,
            'license_number' => 'MED-SM-02002',
        ]);
        $patient = Patient::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson('/api/v1/appointments', [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'specialty_id' => $specialty->id,
                'scheduled_at' => '2026-10-05 09:30:00',
                'duration_min' => 30,
                'reason' => 'Consulta de seguimiento',
            ])
            ->assertCreated()
            ->assertJsonPath('appointment.status', 'pendiente')
            ->assertJsonPath('appointment.patient.code', $patient->code)
            ->assertJsonPath('appointment.doctor.user.email', $doctorUser->email);

        $this->assertDatabaseHas('appointments', [
            'tenant_id' => $this->tenant->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'pendiente',
        ]);
    }

    public function test_appointment_rejects_specialty_that_does_not_match_doctor(): void
    {
        $receptionist = $this->userWithRole('Recepcionista');
        $doctorUser = $this->userWithRole('Médico');
        $doctorSpecialty = Specialty::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Medicina Interna']);
        $wrongSpecialty = Specialty::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Cirugía General']);
        $doctor = Doctor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $doctorUser->id,
            'specialty_id' => $doctorSpecialty->id,
            'license_number' => 'MED-SM-03003',
        ]);
        $patient = Patient::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson('/api/v1/appointments', [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'specialty_id' => $wrongSpecialty->id,
                'scheduled_at' => '2026-10-05 10:00:00',
                'duration_min' => 30,
                'reason' => 'Consulta inicial',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('specialty_id');
    }
}
