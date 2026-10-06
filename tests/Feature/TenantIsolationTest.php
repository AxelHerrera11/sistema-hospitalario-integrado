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
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Garantiza que el trait BelongsToTenant aísla los datos por hospital.
 * Si esta prueba falla, hay riesgo de mostrar pacientes de otro hospital.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_queries_only_return_records_of_the_current_tenant(): void
    {
        $hospitalA = Tenant::factory()->create();
        $hospitalB = Tenant::factory()->create();

        Patient::factory()->count(3)->create(['tenant_id' => $hospitalA->id]);
        Patient::factory()->count(2)->create(['tenant_id' => $hospitalB->id]);

        app()->instance('currentTenant', $hospitalA);
        $this->assertSame(3, Patient::query()->count());

        app()->instance('currentTenant', $hospitalB);
        $this->assertSame(2, Patient::query()->count());
    }

    public function test_records_from_another_tenant_cannot_be_found_by_id(): void
    {
        $hospitalA = Tenant::factory()->create();
        $hospitalB = Tenant::factory()->create();

        $foreignPatient = Patient::factory()->create(['tenant_id' => $hospitalB->id]);

        app()->instance('currentTenant', $hospitalA);

        $this->assertNull(Patient::query()->find($foreignPatient->id));
    }

    public function test_new_records_get_the_current_tenant_automatically(): void
    {
        $hospital = Tenant::factory()->create();
        app()->instance('currentTenant', $hospital);

        $patient = Patient::factory()->create(['tenant_id' => null]);

        $this->assertSame($hospital->id, $patient->tenant_id);
    }

    // ── Binding implícito de rutas ({appointment}, {doctor}, {specialty}) ──
    //
    // SubstituteBindings (grupo 'api') resuelve el modelo de la URL. Si corre
    // antes que 'tenant', el global scope todavía no tiene hospital y un id de
    // otro hospital resuelve. bootstrap/app.php obliga a que 'tenant' corra
    // primero; estas pruebas fallan si ese orden se pierde.

    public function test_appointment_routes_return_404_for_an_appointment_of_another_tenant(): void
    {
        $this->seed(RoleSeeder::class);
        [$hospitalA, $hospitalB] = [Tenant::factory()->create(), Tenant::factory()->create()];

        $own = $this->schedulingFixture($hospitalA);
        $foreign = $this->schedulingFixture($hospitalB);
        $receptionist = $this->userWithRole('Recepcionista', $hospitalA);

        $this->requestAs($receptionist, 'putJson', "/api/v1/appointments/{$foreign['appointment']->id}", [
            'patient_id' => $own['patient']->id,
            'doctor_id' => $own['doctor']->id,
            'specialty_id' => $own['specialty']->id,
            'duration_min' => 45,
        ])->assertNotFound();

        $this->requestAs($receptionist, 'postJson', "/api/v1/appointments/{$foreign['appointment']->id}/cancel")
            ->assertNotFound();

        $this->requestAs($receptionist, 'postJson', "/api/v1/appointments/{$foreign['appointment']->id}/status", [
            'status' => 'completada',
        ])->assertNotFound();

        $this->assertDatabaseHas('appointments', [
            'id' => $foreign['appointment']->id,
            'tenant_id' => $hospitalB->id,
            'patient_id' => $foreign['patient']->id,
            'duration_min' => 30,
            'status' => 'pendiente',
        ]);
    }

    public function test_doctor_and_specialty_updates_return_404_for_records_of_another_tenant(): void
    {
        $this->seed(RoleSeeder::class);
        [$hospitalA, $hospitalB] = [Tenant::factory()->create(), Tenant::factory()->create()];

        $own = $this->schedulingFixture($hospitalA);
        $foreign = $this->schedulingFixture($hospitalB);
        $admin = $this->userWithRole('Admin', $hospitalA);

        $this->requestAs($admin, 'putJson', "/api/v1/doctors/{$foreign['doctor']->id}", [
            'specialty_id' => $own['specialty']->id,
            'license_number' => 'MED-INTRUSO',
        ])->assertNotFound();

        $this->requestAs($admin, 'putJson', "/api/v1/specialties/{$foreign['specialty']->id}", [
            'name' => 'Especialidad intrusa',
        ])->assertNotFound();

        $this->assertDatabaseHas('doctors', [
            'id' => $foreign['doctor']->id,
            'specialty_id' => $foreign['specialty']->id,
            'license_number' => $foreign['doctor']->license_number,
        ]);
        $this->assertDatabaseHas('specialties', [
            'id' => $foreign['specialty']->id,
            'name' => $foreign['specialty']->name,
        ]);
    }

    /**
     * Ejecuta la petición como en producción: cada request de PHP-FPM empieza
     * sin 'currentTenant'. En pruebas el contenedor sobrevive entre requests y
     * el hospital de una petición anterior ocultaría el defecto.
     *
     * @param  array<string, mixed>  $data
     */
    private function requestAs(User $user, string $method, string $uri, array $data = []): TestResponse
    {
        $this->app->forgetInstance('currentTenant');

        return $this->withHeaders([
            'Authorization' => 'Bearer '.JWTAuth::fromUser($user),
            'X-Tenant-ID' => $user->tenant_id,
        ])->{$method}($uri, $data);
    }

    private function userWithRole(string $role, Tenant $tenant): User
    {
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole($role);

        return $user;
    }

    /** @return array{specialty: Specialty, doctor: Doctor, patient: Patient, appointment: Appointment} */
    private function schedulingFixture(Tenant $tenant): array
    {
        $specialty = Specialty::factory()->create(['tenant_id' => $tenant->id]);
        $doctor = Doctor::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => User::factory()->create(['tenant_id' => $tenant->id])->id,
            'specialty_id' => $specialty->id,
        ]);
        $patient = Patient::factory()->create(['tenant_id' => $tenant->id]);
        $appointment = Appointment::query()->create([
            'tenant_id' => $tenant->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'specialty_id' => $specialty->id,
            'scheduled_at' => '2026-11-10 09:00:00',
            'duration_min' => 30,
            'status' => 'pendiente',
        ]);

        return compact('specialty', 'doctor', 'patient', 'appointment');
    }
}
