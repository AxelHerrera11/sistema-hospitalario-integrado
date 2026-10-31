<?php

declare(strict_types=1);

namespace Tests\Feature\ReportesOperativos;

use App\Modules\Tenants\Infrastructure\Persistence\Hospital;
use Closure;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use MicroHis\ReportesOperativos\Application\ReportSource;
use MicroHis\ReportesOperativos\Domain\ReportFilters;
use Tests\TestCase;

/** Seguridad de reportes (ASII-27, semana 17): permisos, tenant real y datos internos. */
final class ReportesSeguridadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', ReportesSeguridadTenant::class);
        $this->app['router']->aliasMiddleware('jwt.auth', ReportesSeguridadTenant::class);
        $this->app->instance(RoleResolver::class, new ReportesTestIdentity());
        $this->app->instance(DenialRecorder::class, new ReportesTestIdentity());
        $this->app->instance(ReportSource::class, new class implements ReportSource {
            public function citas(ReportFilters $filters): array
            {
                return [
                    ['fecha' => '2026-11-02', 'servicio' => 'Pediatria', 'estado' => 'completed', 'total' => '14'],
                    ['fecha' => '2026-11-03', 'servicio' => '=Medicina', 'estado' => 'completed', 'total' => '9'],
                ];
            }
        });
        config(['tenants.hospital_database_tenant_id' => 'hospital-a']);
        ReportesSeguridadTenant::$hospital = 'hospital-a';
        ReportesTestIdentity::como('admin_hospital');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    public function test_matriz_de_permisos_del_catalogo(): void
    {
        foreach (['superadmin', 'admin_hospital', 'medico', 'tecnico_laboratorio', 'bioquimico'] as $rol) {
            ReportesTestIdentity::como($rol);
            $this->getJson('/api/v1/reportes')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        }

        foreach (['enfermeria', 'recepcion', 'jefatura_medica'] as $rol) {
            ReportesTestIdentity::como($rol);
            $this->getJson('/api/v1/reportes')->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
        }
    }

    public function test_exportar_requiere_permiso_de_exportacion(): void
    {
        ReportesTestIdentity::como('medico');
        $this->get('/api/v1/reportes/citas-por-servicio/export?date_from=2026-11-01')
            ->assertStatus(403)
            ->assertJsonPath('code', 'RBAC_DENIED');

        ReportesTestIdentity::como('admin_hospital');
        $respuesta = $this->get('/api/v1/reportes/citas-por-servicio/export?date_from=2026-11-01');
        $respuesta->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringContainsString("'=Medicina", $respuesta->getContent());
    }

    public function test_tenant_ajeno_o_sin_membresia(): void
    {
        ReportesTestIdentity::como('admin_hospital');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-b'])
            ->getJson('/api/v1/reportes')
            ->assertStatus(403)
            ->assertJsonPath('code', 'TENANT_MISMATCH');

        $this->app->instance(RoleResolver::class, new class implements RoleResolver {
            public function rolesIn(string $userId, string $hospitalId): array
            {
                return [];
            }
        });
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a'])
            ->getJson('/api/v1/reportes')
            ->assertStatus(403)
            ->assertJsonPath('code', 'RBAC_DENIED');
    }

    public function test_base_hospitalaria_no_configurada_responde_503(): void
    {
        ReportesTestIdentity::como('admin_hospital');
        config(['tenants.hospital_database_tenant_id' => null]);
        $this->getJson('/api/v1/reportes/citas-por-servicio?date_from=2026-11-01')
            ->assertStatus(503)
            ->assertJsonPath('error', 'HOSPITAL_DATABASE_TENANT_UNCONFIGURED');
    }

    public function test_no_expone_datos_internos_ni_trazas_utiles(): void
    {
        ReportesTestIdentity::como('admin_hospital');
        $respuesta = $this->getJson('/api/v1/reportes/citas-por-servicio?date_from=2026-11-01')->assertOk();
        $respuesta->assertHeader('Cache-Control', 'no-store, private');
        $cuerpo = $respuesta->getContent();
        foreach (['password', 'token', 'secret', 'SQLSTATE', 'updated_at', 'deleted_at'] as $marca) {
            $this->assertStringNotContainsString($marca, $cuerpo);
        }
        $this->assertSame([], array_diff(array_keys($respuesta->json('data.0')), ['fecha', 'servicio', 'estado', 'total']));

        $error = $this->getJson('/api/v1/reportes/inexistente?date_from=2026-11-01', ['X-Request-Id' => 'id con espacios']);
        $error->assertNotFound()->assertHeader('Cache-Control', 'no-store, private');
        $this->assertMatchesRegularExpression('/^trace-[0-9a-f]{12}$/', $error->json('trace_id'));
    }

    public function test_sin_sesion_responde_401(): void
    {
        ReportesTestIdentity::como(null);
        $this->getJson('/api/v1/reportes')->assertStatus(401)->assertJsonPath('code', 'AUTH_REQUIRED');
    }
}

final class ReportesSeguridadTenant
{
    public static string $hospital = 'hospital-a';

    public function handle(Request $request, Closure $next)
    {
        $request->setUserResolver(static fn () => ReportesTestIdentity::$user);
        $request->attributes->set('tenant', new Hospital(['hospital_id' => self::$hospital]));

        return $next($request);
    }
}
