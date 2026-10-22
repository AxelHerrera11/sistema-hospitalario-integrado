<?php

declare(strict_types=1);

namespace Tests\Feature\Ocupacion;

use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use MicroHis\Occupancy\Application\BedSnapshotReader;
use MicroHis\Occupancy\Application\PersistenceFailure;
use MicroHis\Occupancy\Domain\BedSnapshot;
use MicroHis\Rbac\Domain\{DenialRecorder, PermissionProvider, RoleResolver};
use MicroHis\Rbac\Persistence\InMemoryRbacStore;
use Tests\TestCase;

/**
 * Contrato HTTP de la fotografía de ocupación (ASII-09, semana 16). La capa
 * HTTP, la autorización y el formato de error se prueban con dobles; una
 * prueba usa la conexión HOSPITAL real apuntando a una base inexistente.
 */
final class OccupancyEndpointTest extends TestCase
{
    private InMemoryRbacStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = new InMemoryRbacStore([
            'CoordinadorCamas' => ['ocupacion.ver'],
            'Recepcion' => [],
        ]);
        $this->store->grant('user-1', 'hospital-a', 'CoordinadorCamas');
        $this->store->grant('user-2', 'hospital-a', 'Recepcion');

        foreach ([RoleResolver::class, PermissionProvider::class, DenialRecorder::class] as $contract) {
            $this->app->instance($contract, $this->store);
        }

        $this->app->instance(BedSnapshotReader::class, $this->reader());

        $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', TestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', TestIdentity::class);

        TestIdentity::$user = new GenericUser(['id' => 'user-1']);
        TestIdentity::$hospital = 'hospital-a';

        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    public function test_devuelve_la_fotografia_de_ocupacion_con_el_corte_y_el_request_id(): void
    {
        $response = $this->getJson('/api/v1/occupancy?cutoff_at=2026-10-20T10:00:00-06:00', [
            'X-Request-ID' => 'req-prueba-1',
        ]);

        $response->assertOk()
            ->assertJsonPath('meta.request_id', 'req-prueba-1')
            ->assertJsonPath('data.cutoff_at', '2026-10-20T10:00:00-06:00')
            ->assertJsonPath('data.global.total_beds', 3)
            ->assertJsonPath('data.global.occupied', 1)
            ->assertJsonPath('data.global.available', 1)
            ->assertJsonPath('data.global.unverified_occupied', 1)
            ->assertJsonPath('data.global.inconsistencies', 1)
            ->assertJsonPath('data.global.occupancy_rate', 33.33)
            ->assertJsonCount(1, 'data.wards')
            ->assertJsonPath('data.wards.0.ward_code', 'UCI');
    }

    public function test_el_corte_por_defecto_es_la_hora_del_servidor(): void
    {
        $response = $this->getJson('/api/v1/occupancy');

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.cutoff_at'));
    }

    public function test_rechaza_un_corte_invalido(): void
    {
        $this->getJson('/api/v1/occupancy?cutoff_at=2026-10-20%2010:00:00')
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'INVALID_CUTOFF');

        $this->getJson('/api/v1/occupancy?cutoff_at=2026-10-20T10:00:00')
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'INVALID_CUTOFF');
    }

    public function test_sin_token_responde_401(): void
    {
        TestIdentity::$user = null;

        $this->getJson('/api/v1/occupancy')
            ->assertStatus(401)
            ->assertJsonPath('code', 'AUTH_REQUIRED');
    }

    public function test_sin_el_permiso_ocupacion_ver_responde_403(): void
    {
        TestIdentity::$user = new GenericUser(['id' => 'user-2']);

        $this->getJson('/api/v1/occupancy')
            ->assertStatus(403)
            ->assertJsonPath('code', 'RBAC_DENIED');
    }

    public function test_un_hospital_distinto_al_declarado_responde_403(): void
    {
        $this->getJson('/api/v1/occupancy', ['X-Tenant-ID' => 'hospital-b'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'TENANT_MISMATCH');
    }

    public function test_la_falla_de_persistencia_responde_503_y_nunca_ocupacion_cero(): void
    {
        $this->app->instance(BedSnapshotReader::class, new class implements BedSnapshotReader {
            public function readAt(\DateTimeImmutable $cutoff): array
            {
                throw new PersistenceFailure('simulated');
            }
        });

        $this->getJson('/api/v1/occupancy')
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'OCCUPANCY_UNAVAILABLE')
            ->assertJsonMissingPath('data.global.total_beds');
    }

    public function test_una_base_hospital_inexistente_responde_503_y_no_500(): void
    {
        config(['database.connections.hospital.database' => 'base_inexistente_asii09']);
        DB::purge('hospital');
        $this->app->forgetInstance(BedSnapshotReader::class);

        $this->getJson('/api/v1/occupancy')
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'OCCUPANCY_UNAVAILABLE');
    }

    public function test_otro_metodo_responde_405(): void
    {
        $this->postJson('/api/v1/occupancy')->assertStatus(405);
    }

    private function reader(): BedSnapshotReader
    {
        return new class implements BedSnapshotReader {
            public function readAt(\DateTimeImmutable $cutoff): array
            {
                return [
                    new BedSnapshot('bed-1', 'ward-1', 'UCI', 'occupied', true),
                    new BedSnapshot('bed-2', 'ward-1', 'UCI', 'available', false),
                    new BedSnapshot('bed-3', 'ward-1', 'UCI', 'occupied', false),
                ];
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
        $request->attributes->set('tenant', new class {
            public function getKey(): ?string
            {
                return TestIdentity::$hospital;
            }
        });

        return $next($request);
    }
}
