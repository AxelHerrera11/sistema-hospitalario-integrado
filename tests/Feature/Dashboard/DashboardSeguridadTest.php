<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Modules\Tenants\Infrastructure\Persistence\Hospital;
use Closure;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use MicroHis\DashboardBase\Application\CitasDelDia;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use Tests\TestCase;

/** Seguridad del dashboard (ASII-26, semana 17): sesion, tenant real y datos internos. */
final class DashboardSeguridadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', DashboardSeguridadTenant::class);
        $this->app['router']->aliasMiddleware('jwt.auth', DashboardSeguridadTenant::class);
        $this->app->instance(RoleResolver::class, new DashboardTestIdentity());
        $this->app->instance(DenialRecorder::class, new DashboardTestIdentity());
        $this->app->instance(CitasDelDia::class, new class implements CitasDelDia {
            public function porEstado(string $fecha): array
            {
                return ['scheduled' => 5, 'completed' => 3, 'cancelled' => 2];
            }
        });
        config(['tenants.hospital_database_tenant_id' => 'hospital-a']);
        DashboardSeguridadTenant::$hospital = 'hospital-a';
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    public function test_todos_los_roles_con_sesion_ven_el_resumen(): void
    {
        foreach (['superadmin', 'admin_hospital', 'medico', 'enfermeria', 'recepcion', 'tecnico_laboratorio', 'bioquimico'] as $rol) {
            DashboardTestIdentity::como($rol);
            $this->getJson('/api/v1/dashboard/resumen')
                ->assertOk()
                ->assertHeader('Cache-Control', 'no-store, private');
        }

        DashboardTestIdentity::como('tecnico_laboratorio');
        $this->getJson('/api/v1/dashboard/resumen')->assertOk()->assertJsonPath('data.tarjetas', []);
    }

    public function test_sin_sesion_responde_401(): void
    {
        DashboardTestIdentity::como(null);
        $this->getJson('/api/v1/dashboard/resumen')
            ->assertStatus(401)
            ->assertJsonPath('code', 'unauthenticated')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_tenant_ajeno_o_no_configurado_no_ve_el_resumen(): void
    {
        DashboardTestIdentity::como('recepcion');
        DashboardSeguridadTenant::$hospital = 'hospital-b';
        $this->withHeaders(['X-Tenant-ID' => 'hospital-b'])
            ->getJson('/api/v1/dashboard/resumen')
            ->assertStatus(403)
            ->assertJsonPath('error', 'HOSPITAL_DATABASE_TENANT_MISMATCH');

        config(['tenants.hospital_database_tenant_id' => null]);
        DashboardSeguridadTenant::$hospital = 'hospital-a';
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a'])
            ->getJson('/api/v1/dashboard/resumen')
            ->assertStatus(503)
            ->assertJsonPath('error', 'HOSPITAL_DATABASE_TENANT_UNCONFIGURED');
    }

    public function test_no_expone_datos_internos_ni_secretos(): void
    {
        DashboardTestIdentity::como('recepcion');
        $respuesta = $this->getJson('/api/v1/dashboard/resumen')->assertOk();
        $cuerpo = $respuesta->getContent();
        foreach (['password', 'token', 'secret', 'SQLSTATE', 'updated_at', 'deleted_at'] as $marca) {
            $this->assertStringNotContainsString($marca, $cuerpo);
        }
        $tarjeta = $respuesta->json('data.tarjetas.0');
        $this->assertSame([], array_diff(array_keys($tarjeta), ['clave', 'titulo', 'ruta', 'total', 'por_estado', 'indicador']));
        $this->assertSame([], array_diff(array_keys($tarjeta['indicador']), ['clave', 'titulo', 'valor', 'meta', 'sentido', 'cumple_meta']));
    }
}

final class DashboardSeguridadTenant
{
    public static string $hospital = 'hospital-a';

    public function handle(Request $request, Closure $next)
    {
        $request->setUserResolver(static fn () => DashboardTestIdentity::$user);
        $request->attributes->set('tenant', new Hospital(['hospital_id' => self::$hospital]));

        return $next($request);
    }
}
