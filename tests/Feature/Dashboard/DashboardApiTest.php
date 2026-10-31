<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Http\Middleware\EnsureHospitalDatabaseTenant;
use Illuminate\Contracts\Http\Kernel;
use MicroHis\DashboardBase\Application\CitasDelDia;
use MicroHis\DashboardBase\Application\FuenteNoDisponible;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use Tests\TestCase;

/** Resumen del dashboard base (ASII-26, semana 16) con la matriz de config/rbac.php real; citas en memoria. */
final class DashboardApiTest extends TestCase
{
    public ?string $fechaConsultada = null;

    public bool $caida = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', DashboardTestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', DashboardTestIdentity::class);
        $this->app->instance(RoleResolver::class, new DashboardTestIdentity());
        $this->app->instance(DenialRecorder::class, new DashboardTestIdentity());
        $this->app->instance(EnsureHospitalDatabaseTenant::class, new class {
            public function handle($request, $next)
            {
                return $next($request);
            }
        });
        $this->app->instance(CitasDelDia::class, new class ($this) implements CitasDelDia {
            public function __construct(private readonly DashboardApiTest $prueba)
            {
            }

            public function porEstado(string $fecha): array
            {
                if ($this->prueba->caida) {
                    throw new FuenteNoDisponible('caida');
                }
                $this->prueba->fechaConsultada = $fecha;

                return ['scheduled' => 5, 'completed' => 3, 'cancelled' => 2];
            }
        });
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    public function test_recepcion_ve_la_tarjeta_de_citas_con_su_indicador(): void
    {
        DashboardTestIdentity::como('recepcion');

        $this->getJson('/api/v1/dashboard/resumen?fecha=2026-11-02')
            ->assertOk()
            ->assertJsonPath('data.tarjetas.0.clave', 'citas_hoy')
            ->assertJsonPath('data.tarjetas.0.total', 10)
            ->assertJsonPath('data.tarjetas.0.indicador.sentido', 'menor_mejor')
            ->assertJsonPath('data.tarjetas.0.indicador.cumple_meta', false); // 20 % > meta 10 %
        $this->assertSame('2026-11-02', $this->fechaConsultada);
    }

    public function test_sin_permiso_de_citas_no_hay_tarjeta_ni_consulta(): void
    {
        DashboardTestIdentity::como('tecnico_laboratorio');

        $this->getJson('/api/v1/dashboard/resumen')->assertOk()->assertJsonPath('data.tarjetas', []);
        $this->assertNull($this->fechaConsultada, 'falla cerrada: no se consulta lo que no se puede ver');
    }

    public function test_errores_del_resumen(): void
    {
        DashboardTestIdentity::como(null);
        $this->getJson('/api/v1/dashboard/resumen')->assertStatus(401)->assertJsonPath('code', 'unauthenticated');

        DashboardTestIdentity::como('recepcion');
        $this->getJson('/api/v1/dashboard/resumen?fecha=2026-13-40')->assertStatus(422)->assertJsonPath('code', 'validation_failed');
        $this->getJson('/api/v1/dashboard/resumen?fecha[]=2026-11-02')->assertStatus(422)->assertJsonPath('code', 'validation_failed');

        $this->caida = true;
        $this->getJson('/api/v1/dashboard/resumen')->assertStatus(503)->assertJsonPath('code', 'dashboard_source_unavailable');
    }
}
