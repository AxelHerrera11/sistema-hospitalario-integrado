<?php

declare(strict_types=1);

namespace Tests\Feature\Ocupacion;

use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use MicroHis\Occupancy\Application\BedSnapshotReader;
use MicroHis\Occupancy\Application\PersistenceFailure;
use MicroHis\Occupancy\Domain\BedSnapshot;
use MicroHis\Rbac\Domain\{DenialRecorder, PermissionProvider, RoleResolver};
use MicroHis\Rbac\Persistence\InMemoryRbacStore;
use Tests\TestCase;

/**
 * Semana 17: pruebas de permisos y de datos sensibles del endpoint de ocupación.
 *
 * Verifica que la respuesta y los errores no filtran información de pacientes, detalles internos
 * de la base ni identificadores ajenos al hospital, y que la respuesta no se puede almacenar en
 * caché compartida.
 */
final class OccupancySecurityTest extends TestCase
{
    private InMemoryRbacStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = new InMemoryRbacStore(['CoordinadorCamas' => ['ocupacion.ver']]);
        $this->store->grant('user-1', 'hospital-a', 'CoordinadorCamas');

        foreach ([RoleResolver::class, PermissionProvider::class, DenialRecorder::class] as $contract) {
            $this->app->instance($contract, $this->store);
        }

        $this->app->instance(BedSnapshotReader::class, new class implements BedSnapshotReader {
            public function readAt(\DateTimeImmutable $cutoff): array
            {
                return [
                    new BedSnapshot('bed-1', 'ward-1', 'UCI', 'occupied', true),
                    new BedSnapshot('bed-2', 'ward-1', 'UCI', 'available', false),
                ];
            }
        });

        $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', SecurityIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', SecurityIdentity::class);

        SecurityIdentity::$user = new GenericUser(['id' => 'user-1']);
        SecurityIdentity::$hospital = 'hospital-a';

        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    public function test_la_respuesta_no_expone_datos_de_pacientes(): void
    {
        $payload = $this->getJson('/api/v1/occupancy')->assertOk()->json();

        $this->assertSame(['data', 'meta'], array_keys($payload));
        $this->assertSame(
            ['cutoff_at', 'global', 'wards'],
            array_keys($payload['data']),
            'La respuesta solo puede traer el corte, el resumen global y las salas.',
        );

        $encoded = json_encode($payload, JSON_THROW_ON_ERROR);
        foreach (['patient', 'paciente', 'care_episode', 'nombre', 'apellido', 'mpi'] as $prohibido) {
            $this->assertStringNotContainsStringIgnoringCase($prohibido, $encoded, $prohibido);
        }
    }

    public function test_los_errores_no_exponen_detalles_internos(): void
    {
        $this->app->instance(BedSnapshotReader::class, new class implements BedSnapshotReader {
            public function readAt(\DateTimeImmutable $cutoff): array
            {
                throw new PersistenceFailure('SQLSTATE[42P01] relation "bed_stays" does not exist');
            }
        });

        $payload = $this->getJson('/api/v1/occupancy')->assertStatus(503)->json();

        $this->assertSame(['error', 'meta'], array_keys($payload));
        $this->assertSame(['code', 'message'], array_keys($payload['error']));
        $this->assertSame('OCCUPANCY_UNAVAILABLE', $payload['error']['code']);
        $this->assertStringNotContainsStringIgnoringCase('sqlstate', json_encode($payload, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsStringIgnoringCase('bed_stays', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public function test_la_respuesta_no_se_puede_guardar_en_cache_compartida(): void
    {
        $response = $this->getJson('/api/v1/occupancy')->assertOk();

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_el_identificador_de_solicitud_se_sanitiza(): void
    {
        $lento = $this->getJson('/api/v1/occupancy', ['X-Request-ID' => str_repeat('a', 200)])->assertOk();
        $this->assertMatchesRegularExpression('/^req-[a-f0-9]{16}$/', $lento->json('meta.request_id'));

        $inyectado = $this->getJson('/api/v1/occupancy', ['X-Request-ID' => "abc\nInjected: 1"])->assertOk();
        $this->assertMatchesRegularExpression('/^req-[a-f0-9]{16}$/', $inyectado->json('meta.request_id'));

        $valido = $this->getJson('/api/v1/occupancy', ['X-Request-ID' => 'req-abc_123'])->assertOk();
        $this->assertSame('req-abc_123', $valido->json('meta.request_id'));
    }

    public function test_un_corte_con_valores_invalidos_no_se_refleja(): void
    {
        foreach (['2026-10-20T10:00:00', 'ayer', '2026-13-45T99:00:00+00:00', '2026-10-20'] as $corte) {
            $this->getJson('/api/v1/occupancy?cutoff_at=' . urlencode($corte))
                ->assertStatus(400)
                ->assertJsonPath('error.code', 'INVALID_CUTOFF');
        }
    }

    public function test_el_endpoint_es_de_solo_lectura(): void
    {
        foreach (['post', 'put', 'patch', 'delete'] as $metodo) {
            $this->{$metodo . 'Json'}('/api/v1/occupancy')->assertStatus(405);
        }
    }

    public function test_un_hospital_distinto_al_declarado_no_ve_ocupacion(): void
    {
        $this->getJson('/api/v1/occupancy', ['X-Tenant-ID' => 'hospital-b'])
            ->assertStatus(403)
            ->assertJsonMissingPath('data');
    }
}

/** Identidad de prueba; no se registra en providers ni rutas de producción. */
class SecurityIdentity
{
    public static ?GenericUser $user;

    public static ?string $hospital;

    public function handle(Request $request, Closure $next)
    {
        $request->setUserResolver(fn () => self::$user);
        $request->attributes->set('tenant', new class {
            public function getKey(): ?string
            {
                return SecurityIdentity::$hospital;
            }
        });

        return $next($request);
    }
}
