<?php

declare(strict_types=1);

namespace Tests\Feature\Reportes;

use App\Modules\Tenants\Infrastructure\Persistence\Hospital;
use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use MicroHis\Reportes\Application\EpisodeSummaryReader;
use MicroHis\Rbac\Domain\{DenialRecorder, PermissionProvider, RoleResolver};
use MicroHis\Rbac\Persistence\InMemoryRbacStore;
use Tests\TestCase;

/**
 * Contrato HTTP del indicador IND-RA-01 (ASII-23, semanas 5 y 16). La capa
 * HTTP, la autorización y el formato de error se prueban contra el caso de
 * uso real (Regla de dominio Period) con el puerto del lector sustituido
 * por dobles: servidor bloqueado en este entorno.
 */
final class IndicatorEndpointTest extends TestCase
{
    private InMemoryRbacStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = new InMemoryRbacStore([
            'CoordinadorCamas' => ['reportes.ver'],
            'Recepcion' => [],
        ]);
        $this->store->grant('user-1', 'hospital-a', 'CoordinadorCamas');
        $this->app->instance(DenialRecorder::class, $this->store);
        $this->app->instance(PermissionProvider::class, $this->store);
        $this->app->instance(RoleResolver::class, $this->store);

        $this->app->instance(EpisodeSummaryReader::class, $this->reader(2, 1));

        $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        // Instancia on-premise de prueba: su base HOSPITAL pertenece a hospital-a.
        config(['tenants.hospital_database_tenant_id' => 'hospital-a']);
        $this->app['router']->aliasMiddleware('tenant', TestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', TestIdentity::class);

        TestIdentity::$user = new GenericUser(['id' => 'user-1']);
        TestIdentity::$hospital = 'hospital-a';
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    public function test_calcula_el_porcentaje_con_calculo_completado(): void
    {
        $this->postJson('/api/v1/reports/indicators/IND-RA-01/calculate', [
            'from' => '2026-01-01T00:00:00-06:00',
            'until' => '2026-02-01T00:00:00-06:00',
        ])
            ->assertOk()
            ->assertJsonPath('data.indicator', 'IND-RA-01')
            ->assertJsonPath('data.formula_version', 'TCI-1.0')
            ->assertJsonPath('data.state', null)
            ->assertJsonPath('meta.filters', null)
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/', $this->postJson(
            '/api/v1/reports/indicators/IND-RA-01/calculate',
            ['from' => '2026-01-01T00:00:00-06:00', 'until' => '2026-02-01T00:00:00-06:00'],
        )->json('data.computed_at'));
    }

    public function test_miembro_de_otro_hospital_no_consulta_la_base_de_esta_instancia(): void
    {
        $lecturas = new class implements EpisodeSummaryReader {
            public int $veces = 0;

            public function read(string $fromInclusive, string $untilExclusive, string $serviceOrEmpty): ?array
            {
                $this->veces++;

                return null;
            }
        };
        $this->app->instance(EpisodeSummaryReader::class, $lecturas);
        $periodo = ['from' => '2026-01-01T00:00:00-06:00', 'until' => '2026-02-01T00:00:00-06:00'];

        // Membresía válida en hospital-b, instancia con la base de hospital-a.
        $this->store->grant('user-1', 'hospital-b', 'CoordinadorCamas');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-b']);
        TestIdentity::$hospital = 'hospital-b';
        $this->postJson('/api/v1/reports/indicators/IND-RA-01/calculate', $periodo)
            ->assertForbidden()
            ->assertJsonPath('error', 'HOSPITAL_DATABASE_TENANT_MISMATCH')
            ->assertJsonMissingPath('data');
        $this->assertSame(0, $lecturas->veces);

        // El mismo rol en el hospital dueño de la base conserva el acceso.
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
        TestIdentity::$hospital = 'hospital-a';
        $this->postJson('/api/v1/reports/indicators/IND-RA-01/calculate', $periodo)->assertOk();
        $this->assertSame(1, $lecturas->veces);

        // Sin hospital asignado a la base se cierra el paso.
        config(['tenants.hospital_database_tenant_id' => null]);
        $this->postJson('/api/v1/reports/indicators/IND-RA-01/calculate', $periodo)
            ->assertStatus(503)
            ->assertJsonPath('error', 'HOSPITAL_DATABASE_TENANT_UNCONFIGURED');
        $this->assertSame(1, $lecturas->veces);
    }

    public function test_rechaza_fechas_imposibles_con_422_sin_porcentaje(): void
    {
        foreach (['2026-02-31T00:00:00-06:00', '2026-13-01T00:00:00-06:00', '2026-01-01T24:30:00-06:00'] as $desde) {
            $this->postJson('/api/v1/reports/indicators/IND-RA-01/calculate', ['from' => $desde, 'until' => '2027-03-05T00:00:00-06:00'])
                ->assertStatus(422)
                ->assertJsonPath('error.code', 'periodo_invalido')
                ->assertJsonMissingPath('data');
        }
    }

    public function test_rechaza_periodo_invalido_con_422_sin_porcentaje(): void
    {
        $this->postJson('/api/v1/reports/indicators/IND-RA-01/calculate', [
            'from' => '2026-02-01T00:00:00-06:00',
            'until' => '2026-01-01T00:00:00-06:00',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'periodo_invalido')
            ->assertJsonMissingPath('data.percentage');
    }

    public function test_rechaza_peticion_sin_from_con_422_entrada_invalida(): void
    {
        $this->postJson('/api/v1/reports/indicators/IND-RA-01/calculate', [
            'until' => '2026-02-01T00:00:00-06:00',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'entrada_invalida');
    }

    public function test_denominador_cero_publica_sin_datos_y_sin_porcentaje(): void
    {
        $this->app->instance(EpisodeSummaryReader::class, $this->reader(0, 0));

        $response = $this->postJson('/api/v1/reports/indicators/IND-RA-01/calculate', [
            'from' => '2026-01-01T00:00:00-06:00',
            'until' => '2026-02-01T00:00:00-06:00',
        ])
            ->assertOk()
            ->assertJsonPath('data.state', 'SIN_DATOS_EN_PERIODO')
            ->assertJsonPath('data.percentage', null);
    }

    public function test_lectura_incoherente_responde_503_sin_cifras(): void
    {
        $this->app->instance(EpisodeSummaryReader::class, $this->reader(2, 3));

        $this->postJson('/api/v1/reports/indicators/IND-RA-01/calculate', [
            'from' => '2026-01-01T00:00:00-06:00',
            'until' => '2026-02-01T00:00:00-06:00',
        ])
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'REPORTES_UNAVAILABLE')
            ->assertJsonMissingPath('data.percentage');
    }

    public function test_falla_de_persistencia_responde_503_y_sin_cifras(): void
    {
        $this->app->instance(EpisodeSummaryReader::class, new class implements EpisodeSummaryReader {
            public function read(string $fromInclusive, string $untilExclusive, string $serviceOrEmpty): ?array
            {
                throw new \PDOException('simulated');
            }
        });

        $this->postJson('/api/v1/reports/indicators/IND-RA-01/calculate', [
            'from' => '2026-01-01T00:00:00-06:00',
            'until' => '2026-02-01T00:00:00-06:00',
        ])
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'REPORTES_UNAVAILABLE')
            ->assertJsonMissingPath('data.percentage');
    }

    public function test_sin_token_responde_401(): void
    {
        TestIdentity::$user = null;

        $this->postJson('/api/v1/reports/indicators/IND-RA-01/calculate', [
            'from' => '2026-01-01T00:00:00-06:00',
            'until' => '2026-02-01T00:00:00-06:00',
        ])->assertStatus(401)->assertJsonPath('code', 'AUTH_REQUIRED');
    }

    public function test_sin_permiso_globall_responde_403(): void
    {
        $this->store->grant('user-2', 'hospital-a', 'Recepcion');
        TestIdentity::$user = new GenericUser(['id' => 'user-2']);

        $this->postJson('/api/v1/reports/indicators/IND-RA-01/calculate', [
            'from' => '2026-01-01T00:00:00-06:00',
            'until' => '2026-02-01T00:00:00-06:00',
        ])->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
    }

    public function test_un_hospital_distinto_al_declarado_responde_403(): void
    {
        $this->postJson('/api/v1/reports/indicators/IND-RA-01/calculate', [
            'from' => '2026-01-01T00:00:00-06:00',
            'until' => '2026-02-01T00:00:00-06:00',
        ], ['X-Tenant-ID' => 'hospital-b'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'TENANT_MISMATCH');
    }

    public function test_otro_metodo_responde_405(): void
    {
        $this->deleteJson('/api/v1/reports/indicators/IND-RA-01/calculate')->assertStatus(405);
    }

    private function reader(int $finalized, int $cancelled): EpisodeSummaryReader
    {
        return new class($finalized, $cancelled) implements EpisodeSummaryReader {
            public function __construct(
                private readonly int $finalized,
                private readonly int $cancelled,
            ) {
            }

            public function read(string $fromInclusive, string $untilExclusive, string $serviceOrEmpty): ?array
            {
                if ($this->finalized === 0 && $this->cancelled === 0) {
                    return null;
                }

                return ['finalized' => $this->finalized, 'cancelled' => $this->cancelled];
            }
        };
    }
}

/** Identidad de prueba; no se registra en providers ni rutas de producción. */
class TestIdentity
{
    public static ?GenericUser $user;

    public static ?string $hospital;

    public function handle(Request $request, Closure $next)
    {
        $request->setUserResolver(fn () => self::$user);
        $request->attributes->set('tenant', new Hospital(['hospital_id' => self::$hospital]));

        return $next($request);
    }
}
