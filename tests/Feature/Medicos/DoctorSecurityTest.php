<?php

declare(strict_types=1);

namespace Tests\Feature\Medicos;

use App\Medicos\HospitalActors;
use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use MicroHis\Medicos\Persistence\PdoDoctorStore;
use MicroHis\Medicos\Persistence\PdoScheduleProvider;
use MicroHis\Medicos\Presentation\Router;
use MicroHis\Rbac\Domain\{DenialRecorder, PermissionProvider, RoleResolver};
use MicroHis\Rbac\Persistence\InMemoryRbacStore;
use PDO;
use Tests\TestCase;

/**
 * Semana 17: seguridad de Médicos y especialidades. Usa el adaptador PDO real (SQLite en memoria) para que la inyección SQL,
 * la asignación masiva y las filtraciones se prueben contra sentencias de verdad, no contra un doble.
 */
final class DoctorSecurityTest extends TestCase
{
    private const ALTA = ['local_user_ref_id' => 'u-ana', 'license_number' => 'COL-1', 'specialty_code' => 'CARD', 'valid_from' => '2026-01-01'];

    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $store = new InMemoryRbacStore(['Admin' => ['medicos.registrar', 'medicos.asociar_especialidad', 'medicos.ver_disponibilidad']]);
        $store->grant('admin', 'hospital-a', 'Admin');
        foreach ([RoleResolver::class, PermissionProvider::class, DenialRecorder::class] as $contract) {
            $this->app->instance($contract, $store);
        }

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->exec(file_get_contents(base_path('micro-his/medicos-especialidades/database/schema.sql')));
        $this->pdo->exec("INSERT INTO specialties VALUES ('sp-card', 'CARD', 'active'), ('sp-neuro', 'NEURO', 'active')");
        $this->app->bind(Router::class, function (): Router {
            $doctors = new PdoDoctorStore($this->pdo);

            return medicos_router($doctors, $doctors, new PdoScheduleProvider($this->pdo));
        });
        $this->app->instance(HospitalActors::class, new class extends HospitalActors {
            public function localUserRef(string $centralUserId): ?string
            {
                return "ref-$centralUserId";
            }
        });

        $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', SecurityIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', SecurityIdentity::class);
        SecurityIdentity::$user = new GenericUser(['id' => 'admin']);
        SecurityIdentity::$hospital = 'hospital-a';

        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    public function test_t1_la_inyeccion_sql_se_guarda_como_texto_y_no_se_ejecuta(): void
    {
        $license = "X'); DROP TABLE doctor_profiles;--";

        $this->postJson('/api/v1/doctors', ['license_number' => $license] + self::ALTA)->assertCreated();

        $this->assertSame(1, (int) $this->pdo->query('SELECT count(*) FROM doctor_profiles')->fetchColumn(), 'la tabla sigue existiendo');
        $this->assertSame(mb_strtoupper($license), $this->pdo->query('SELECT license_number FROM doctor_profiles')->fetchColumn());

        $this->getJson('/api/v1/availability?' . http_build_query(['specialty' => "NEURO' OR '1'='1", 'date' => '2026-10-19']))
            ->assertStatus(422)->assertJsonPath('code', 'SPECIALTY_INACTIVE');
        $this->postJson("/api/v1/doctors/x'%20OR%20'1'='1/specialties", ['specialty_code' => 'CARD', 'valid_from' => '2026-02-01'])
            ->assertNotFound()->assertJsonPath('code', 'ROUTE_NOT_FOUND');
    }

    public function test_t2_la_asignacion_masiva_no_fija_campos_del_sistema(): void
    {
        $id = $this->postJson('/api/v1/doctors', [
            'doctor_profile_id' => 'elegido-por-el-cliente', 'status' => 'suspended', 'valid_to' => '2026-01-02', 'hospital_id' => 'otro',
        ] + self::ALTA)->assertCreated()->json('doctor_profile_id');

        $this->assertNotSame('elegido-por-el-cliente', $id);
        $profile = $this->pdo->query('SELECT status, valid_to FROM doctor_profiles')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(['status' => 'active', 'valid_to' => null], $profile);
    }

    public function test_t3_el_actor_sale_del_servidor_y_no_de_una_cabecera_del_cliente(): void
    {
        $this->postJson('/api/v1/doctors', self::ALTA, ['X-User-ID' => 'atacante', 'X-Actor' => 'atacante'])->assertCreated();

        $this->assertSame('ref-admin', $this->pdo->query('SELECT actor_user_ref_id FROM local_access_audits')->fetchColumn());
    }

    public function test_t4_los_errores_no_filtran_sql_rutas_ni_el_dato_enviado(): void
    {
        $this->pdo->exec('DROP TABLE local_access_audits');
        $failure = $this->postJson('/api/v1/doctors', ['license_number' => 'SECRETO-123'] + self::ALTA)->assertStatus(500);

        $this->assertSame(['code' => 'DOCTOR_PERSISTENCE_ERROR'], $failure->json());
        foreach (['SQLSTATE', 'PDOException', 'local_access_audits', '.php', 'SECRETO-123', 'vendor'] as $leak) {
            $this->assertStringNotContainsString($leak, $failure->getContent());
        }
    }

    public function test_t5_ninguna_respuesta_se_puede_guardar_en_cache_compartida(): void
    {
        foreach ([
            $this->postJson('/api/v1/doctors', self::ALTA),
            $this->postJson('/api/v1/doctors', self::ALTA),
            $this->getJson('/api/v1/availability?specialty=CARD&date=2026-10-19'),
            $this->getJson('/api/v1/availability?specialty=CARD&date=2026-02-30'),
        ] as $response) {
            $this->assertSame('no-store, private', $response->headers->get('Cache-Control'), "estado {$response->status()}");
        }
    }

    public function test_t6_la_disponibilidad_no_expone_pacientes_licencias_ni_usuarios(): void
    {
        $id = $this->postJson('/api/v1/doctors', self::ALTA)->json('doctor_profile_id');
        $this->pdo->exec("INSERT INTO doctor_schedules VALUES ('$id', 'sp-card', 1, 480, 600, 30, '2026-01-01', NULL, 'active')");
        $this->pdo->exec("INSERT INTO appointments VALUES ('$id', '2026-10-19 08:00:00', '2026-10-19 08:30:00', 'confirmed')");

        $response = $this->getJson('/api/v1/availability?specialty=CARD&date=2026-10-19')->assertOk();

        $this->assertSame(['doctor_id', 'slots', 'status'], $this->sorted($response->json('doctors.0')));
        foreach (['COL-1', 'u-ana', 'local_user_ref_id', 'license', 'patient', 'paciente', 'reason'] as $private) {
            $this->assertStringNotContainsString($private, $response->getContent());
        }
    }

    public function test_t7_sin_identidad_o_con_un_hospital_ajeno_no_se_lee_ni_se_escribe(): void
    {
        SecurityIdentity::$user = null;
        $this->getJson('/api/v1/availability?specialty=CARD&date=2026-10-19')->assertStatus(401);
        $this->postJson('/api/v1/doctors', self::ALTA)->assertStatus(401);

        SecurityIdentity::$user = new GenericUser(['id' => 'admin']);
        $this->postJson('/api/v1/doctors', self::ALTA, ['X-Tenant-ID' => 'hospital-ajeno'])->assertStatus(403);

        $this->assertSame(0, (int) $this->pdo->query('SELECT count(*) FROM doctor_profiles')->fetchColumn());
    }

    /** @return list<string> */
    private function sorted(array $row): array
    {
        $keys = array_keys($row);
        sort($keys);

        return $keys;
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
