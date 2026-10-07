<?php

namespace Tests\Feature;

use App\Models\Appointment;
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

    /** @return array{0: User, 1: Specialty, 2: Doctor, 3: Patient} */
    private function schedulingFixture(): array
    {
        $doctorUser = $this->userWithRole('Médico');
        $specialty = Specialty::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Pediatría']);
        $doctor = Doctor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $doctorUser->id,
            'specialty_id' => $specialty->id,
            'license_number' => 'MED-SM-'.fake()->unique()->numerify('#####'),
        ]);
        $patient = Patient::factory()->create(['tenant_id' => $this->tenant->id]);

        return [$doctorUser, $specialty, $doctor, $patient];
    }

    private function appointment(array $overrides = []): Appointment
    {
        [, $specialty, $doctor, $patient] = $this->schedulingFixture();

        return Appointment::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'specialty_id' => $specialty->id,
            'scheduled_at' => now()->addDays(2)->setTime(9, 0),
            'duration_min' => 30,
            'status' => 'pendiente',
            'reason' => 'Consulta',
            ...$overrides,
        ]);
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
        [$doctorUser, $specialty, $doctor, $patient] = $this->schedulingFixture();

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson('/api/v1/appointments', [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'specialty_id' => $specialty->id,
                'scheduled_at' => now()->addDay()->setTime(9, 30)->format('Y-m-d H:i:s'),
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

    public function test_doctor_profile_requires_user_with_medico_role(): void
    {
        $admin = $this->userWithRole('Admin');
        $nurse = $this->userWithRole('Enfermera');
        $specialty = Specialty::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->withHeaders($this->headersFor($admin))
            ->postJson('/api/v1/doctors', [
                'user_id' => $nurse->id,
                'specialty_id' => $specialty->id,
                'license_number' => 'MED-SM-04004',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('user_id');
    }

    public function test_appointment_store_and_update_reject_status_field(): void
    {
        $receptionist = $this->userWithRole('Recepcionista');
        [, $specialty, $doctor, $patient] = $this->schedulingFixture();
        $appointment = $this->appointment([
            'doctor_id' => $doctor->id,
            'specialty_id' => $specialty->id,
            'patient_id' => $patient->id,
        ]);

        $payload = [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'specialty_id' => $specialty->id,
            'scheduled_at' => now()->addDays(3)->setTime(10, 0)->format('Y-m-d H:i:s'),
            'duration_min' => 30,
            'status' => 'cancelada',
            'reason' => 'Intento de atajo',
        ];

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson('/api/v1/appointments', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->withHeaders($this->headersFor($receptionist))
            ->putJson("/api/v1/appointments/{$appointment->id}", $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_appointment_rejects_past_schedule_and_doctor_overlap(): void
    {
        $receptionist = $this->userWithRole('Recepcionista');
        [, $specialty, $doctor, $patient] = $this->schedulingFixture();
        Appointment::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'specialty_id' => $specialty->id,
            'scheduled_at' => now()->addDays(2)->setTime(9, 0),
            'duration_min' => 60,
            'status' => 'confirmada',
            'reason' => 'Cita existente',
        ]);

        $basePayload = [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'specialty_id' => $specialty->id,
            'duration_min' => 30,
            'reason' => 'Consulta',
        ];

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson('/api/v1/appointments', [
                ...$basePayload,
                'scheduled_at' => now()->subDay()->format('Y-m-d H:i:s'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('scheduled_at');

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson('/api/v1/appointments', [
                ...$basePayload,
                'scheduled_at' => now()->addDays(2)->setTime(9, 30)->format('Y-m-d H:i:s'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('scheduled_at');
    }

    public function test_appointment_status_happy_paths_and_invalid_transitions(): void
    {
        $receptionist = $this->userWithRole('Recepcionista');
        $appointment = $this->appointment();

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson("/api/v1/appointments/{$appointment->id}/status", ['status' => 'confirmada'])
            ->assertOk()
            ->assertJsonPath('appointment.status', 'confirmada');

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson("/api/v1/appointments/{$appointment->id}/status", ['status' => 'completada'])
            ->assertOk()
            ->assertJsonPath('appointment.status', 'completada');

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson("/api/v1/appointments/{$appointment->id}/status", ['status' => 'pendiente'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson("/api/v1/appointments/{$appointment->id}/cancel", ['notes' => 'Cancelacion tardia'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_receptionist_can_update_and_cancel_appointment(): void
    {
        $receptionist = $this->userWithRole('Recepcionista');
        $appointment = $this->appointment();

        $this->withHeaders($this->headersFor($receptionist))
            ->putJson("/api/v1/appointments/{$appointment->id}", [
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id,
                'specialty_id' => $appointment->specialty_id,
                'scheduled_at' => now()->addDays(4)->setTime(11, 0)->format('Y-m-d H:i:s'),
                'duration_min' => 45,
                'reason' => 'Reprogramada',
                'notes' => 'Paciente llamo para cambiar horario',
            ])
            ->assertOk()
            ->assertJsonPath('appointment.duration_min', 45)
            ->assertJsonPath('appointment.reason', 'Reprogramada');

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson("/api/v1/appointments/{$appointment->id}/cancel", ['notes' => 'Paciente cancela'])
            ->assertOk()
            ->assertJsonPath('appointment.status', 'cancelada')
            ->assertJsonPath('appointment.notes', 'Paciente cancela');
    }

    public function test_medico_cannot_create_appointment_without_permission(): void
    {
        $medico = $this->userWithRole('Médico');
        [, $specialty, $doctor, $patient] = $this->schedulingFixture();

        $this->withHeaders($this->headersFor($medico))
            ->postJson('/api/v1/appointments', [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'specialty_id' => $specialty->id,
                'scheduled_at' => now()->addDay()->setTime(12, 0)->format('Y-m-d H:i:s'),
                'duration_min' => 30,
            ])
            ->assertStatus(403);
    }
}
