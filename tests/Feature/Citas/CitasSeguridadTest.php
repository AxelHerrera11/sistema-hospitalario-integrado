<?php

declare(strict_types=1);

namespace Tests\Feature\Citas;

use App\Http\Middleware\EnsureHospitalDatabaseTenant;
use App\Repositories\Contracts\AppointmentRepositoryContract;
use Illuminate\Contracts\Http\Kernel;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use Tests\TestCase;

/** Seguridad de la API de citas (ASII-05, semana 17): matriz de permisos, tenant y datos sensibles. */
final class CitasSeguridadTest extends TestCase
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
    private function cita(string $inicio = '2026-11-02T09:00:00-06:00', string $fin = '2026-11-02T09:30:00-06:00'): array
    {
        return [
            'local_patient_id' => self::PACIENTE,
            'doctor_profile_id' => self::DOCTOR,
            'specialty_id' => self::ESPECIALIDAD,
            'starts_at' => $inicio,
            'ends_at' => $fin,
            'reason' => 'Control de presion',
        ];
    }

    public function test_matriz_de_permisos_por_rol(): void
    {
        CitasTestIdentity::como('enfermeria');
        $this->getJson('/api/v1/citas')->assertOk();
        $this->postJson('/api/v1/citas', $this->cita())->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');

        CitasTestIdentity::como('medico');
        $this->getJson('/api/v1/citas')->assertOk();
        $this->postJson('/api/v1/citas', $this->cita())->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');

        CitasTestIdentity::como('tecnico_laboratorio');
        $this->getJson('/api/v1/citas')->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');

        CitasTestIdentity::como('admin_hospital');
        $this->postJson('/api/v1/citas', $this->cita())->assertCreated();

        CitasTestIdentity::como('recepcion');
        $id = $this->postJson('/api/v1/citas', $this->cita('2026-11-02T10:00:00-06:00', '2026-11-02T10:30:00-06:00'))->json('data.appointment_id');
        $this->postJson("/api/v1/citas/{$id}/confirmar")->assertOk();
        $this->postJson("/api/v1/citas/{$id}/cancelar")->assertOk();
    }

    public function test_aislamiento_entre_hospitales(): void
    {
        CitasTestIdentity::como('recepcion');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-b'])
            ->getJson('/api/v1/citas')
            ->assertStatus(403)
            ->assertJsonPath('code', 'TENANT_MISMATCH');

        $this->app->instance(RoleResolver::class, new class implements RoleResolver {
            public function rolesIn(string $userId, string $hospitalId): array
            {
                return [];
            }
        });
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a'])
            ->getJson('/api/v1/citas')
            ->assertStatus(403)
            ->assertJsonPath('code', 'RBAC_DENIED');
    }

    public function test_no_expone_datos_internos_ni_trazas_utiles(): void
    {
        CitasTestIdentity::como('recepcion');
        $respuesta = $this->postJson('/api/v1/citas', $this->cita())->assertCreated();
        $cuerpo = $respuesta->getContent();
        foreach (['password', 'token', 'secret', 'SQLSTATE', 'deleted_at', 'updated_at'] as $marca) {
            $this->assertStringNotContainsString($marca, $cuerpo);
        }
        $respuesta->assertHeader('Cache-Control', 'no-store, private');

        $error = $this->getJson('/api/v1/citas/00000000-0000-4000-8000-000000000999', ['X-Request-Id' => 'id con espacios']);
        $error->assertNotFound()->assertJsonMissingPath('data')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertMatchesRegularExpression('/^trace-[0-9a-f]{12}$/', $error->json('trace_id'));
    }
}
