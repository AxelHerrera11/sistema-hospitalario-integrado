<?php

declare(strict_types=1);

namespace Tests\Feature\Citas;

use App\Http\Middleware\EnsureHospitalDatabaseTenant;
use App\Repositories\Contracts\AppointmentRepositoryContract;
use Illuminate\Contracts\Http\Kernel;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use Tests\TestCase;

/** API v1 de citas (ASII-05, semana 16) con RequirePermission y config/rbac.php reales; HOSPITAL en memoria. */
final class AppointmentApiTest extends TestCase
{
    private const PACIENTE = '00000000-0000-4000-8000-0000000000a1';
    private const DOCTOR = '00000000-0000-4000-8000-0000000000d1';
    private const ESPECIALIDAD = '00000000-0000-4000-8000-0000000000e1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', CitasTestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', CitasTestIdentity::class);
        $this->app->instance(RoleResolver::class, new CitasTestIdentity());
        $this->app->instance(DenialRecorder::class, new CitasTestIdentity());
        $this->app->instance(AppointmentRepositoryContract::class, new CitasEnMemoria());
        $this->app->instance(EnsureHospitalDatabaseTenant::class, new class {
            public function handle($request, $next)
            {
                return $next($request);
            }
        });
        CitasTestIdentity::como('recepcion');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    /** @return array<string, string> */
    private function cita(string $inicio = '2026-11-02T09:00:00-06:00', string $fin = '2026-11-02T09:30:00-06:00', string $doctor = self::DOCTOR): array
    {
        return ['local_patient_id' => self::PACIENTE, 'doctor_profile_id' => $doctor, 'specialty_id' => self::ESPECIALIDAD,
            'starts_at' => $inicio, 'ends_at' => $fin, 'reason' => 'Control de presion'];
    }

    public function test_sin_sesion_responde_401_y_rol_sin_permiso_responde_403(): void
    {
        CitasTestIdentity::como(null);
        $this->getJson('/api/v1/citas')->assertStatus(401)->assertJsonPath('code', 'AUTH_REQUIRED');

        CitasTestIdentity::como('enfermeria'); // tiene citas.ver, no citas.crear
        $this->getJson('/api/v1/citas')->assertOk();
        $this->postJson('/api/v1/citas', $this->cita())->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
    }

    public function test_recepcion_agenda_y_consulta_la_cita(): void
    {
        $creada = $this->postJson('/api/v1/citas', $this->cita())
            ->assertCreated()
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonPath('data.starts_at', '2026-11-02T15:00:00+00:00') // 09:00 en -06:00, sin perder la zona
            ->assertHeader('Cache-Control', 'no-store, private');
        $id = $creada->json('data.appointment_id');

        $this->getJson("/api/v1/citas/{$id}")->assertOk()->assertJsonPath('data.local_patient_id', self::PACIENTE);
        $this->getJson('/api/v1/citas?status=scheduled')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonCount(1, 'data');
    }

    public function test_valida_en_el_borde_con_el_sobre_comun(): void
    {
        $this->postJson('/api/v1/citas', ['local_patient_id' => 'no-uuid'] + $this->cita('2026-11-02T10:00:00-06:00', '2026-11-02T09:00:00-06:00'), ['X-Request-Id' => 'req-citas-0001'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonPath('trace_id', 'req-citas-0001')
            ->assertJsonValidationErrors(['local_patient_id', 'ends_at'], 'errors');

        $this->getJson('/api/v1/citas?status=borrada&per_page=500')->assertStatus(422)->assertJsonValidationErrors(['status.0', 'per_page'], 'errors');
    }

    public function test_el_solape_se_traduce_a_409_appointments_overlap(): void
    {
        $this->postJson('/api/v1/citas', $this->cita())->assertCreated();
        $this->postJson('/api/v1/citas', $this->cita('2026-11-02T09:15:00-06:00', '2026-11-02T09:45:00-06:00'))
            ->assertStatus(409)
            ->assertJsonPath('code', 'appointments_overlap');
        $this->postJson('/api/v1/citas', $this->cita('2026-11-02T09:30:00-06:00', '2026-11-02T10:00:00-06:00'))->assertCreated();
    }

    public function test_transiciones_de_estado_y_cita_finalizada_inmutable(): void
    {
        $id = $this->postJson('/api/v1/citas', $this->cita())->json('data.appointment_id');

        $this->postJson("/api/v1/citas/{$id}/confirmar")->assertOk()->assertJsonPath('data.status', 'confirmed');
        $this->postJson("/api/v1/citas/{$id}/cancelar")->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->postJson("/api/v1/citas/{$id}/confirmar")->assertStatus(409)->assertJsonPath('code', 'appointments_finalized_immutable');
    }

    public function test_cita_inexistente_404_y_borrado_fisico_405(): void
    {
        $this->getJson('/api/v1/citas/00000000-0000-4000-8000-000000000999')->assertNotFound()->assertJsonPath('code', 'appointment_not_found');
        $this->getJson('/api/v1/citas/no-es-uuid')->assertNotFound()->assertJsonPath('code', 'appointment_not_found');
        $this->deleteJson('/api/v1/citas/00000000-0000-4000-8000-000000000999')->assertStatus(405)->assertJsonPath('code', 'appointments_delete_protection');
    }

    public function test_sin_base_hospital_asignada_la_guarda_real_responde_503(): void
    {
        $this->app->forgetInstance(EnsureHospitalDatabaseTenant::class);
        config()->set('tenants.hospital_database_tenant_id', null);

        $this->getJson('/api/v1/citas')->assertStatus(503)->assertJsonPath('error', 'HOSPITAL_DATABASE_TENANT_UNCONFIGURED');
    }
}
