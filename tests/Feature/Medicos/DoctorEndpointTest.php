<?php

declare(strict_types=1);

namespace Tests\Feature\Medicos;

use App\Medicos\HospitalActors;
use App\Modules\Tenants\Infrastructure\Persistence\Hospital;
use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use MicroHis\Medicos\Domain\Period;
use MicroHis\Medicos\Domain\SpecialtyLink;
use MicroHis\Medicos\Persistence\ConfigScheduleProvider;
use MicroHis\Medicos\Persistence\InMemoryAppointmentReader;
use MicroHis\Medicos\Persistence\InMemoryDoctorRepository;
use MicroHis\Medicos\Presentation\Router;
use MicroHis\Rbac\Domain\{DenialRecorder, PermissionProvider, RoleResolver};
use MicroHis\Rbac\Persistence\InMemoryRbacStore;
use Tests\TestCase;

/**
 * Contrato HTTP de Médicos y especialidades (ASII-04, semana 16): ruta, tenant, JWT, permiso por operación, actor local y
 * errores de persistencia. El dominio y los adaptadores PDO se prueban en `micro-his/medicos-especialidades/tests/run.php`.
 */
final class DoctorEndpointTest extends TestCase
{
    private const ALTA = ['local_user_ref_id' => 'u-ana', 'license_number' => 'COL-1', 'specialty_code' => 'CARD', 'valid_from' => '2026-01-01'];

    private InMemoryDoctorRepository $doctors;

    protected function setUp(): void
    {
        parent::setUp();

        $store = new InMemoryRbacStore([
            'Admin' => ['medicos.registrar', 'medicos.asociar_especialidad', 'medicos.ver_disponibilidad'],
            'Enfermeria' => [],
            'SoloDisponibilidad' => ['medicos.ver_disponibilidad'],
        ]);
        $store->grant('admin', 'hospital-a', 'Admin');
        $store->grant('enfermera', 'hospital-a', 'Enfermeria');
        $store->grant('recepcion', 'hospital-a', 'SoloDisponibilidad');
        foreach ([RoleResolver::class, PermissionProvider::class, DenialRecorder::class] as $contract) {
            $this->app->instance($contract, $store);
        }

        $this->doctors = new InMemoryDoctorRepository(
            ['CARD' => ['sp-card', 'active'], 'NEURO' => ['sp-neuro', 'active']],
            ['doc-ana' => ['user' => 'u-a', 'license' => 'COL-9', 'status' => 'active', 'period' => new Period('2026-01-01')]],
            ['doc-ana' => [new SpecialtyLink('sp-neuro', true, new Period('2026-01-01'))]],
        );
        $this->app->bind(Router::class, fn () => medicos_router(
            $this->doctors,
            new InMemoryAppointmentReader(['doc-ana' => ['2026-10-19' => [['08:30', '09:00']]]]),
            new ConfigScheduleProvider(['doc-ana' => [1 => [['08:00', '09:30']]]]),
        ));
        $this->app->instance(HospitalActors::class, new class extends HospitalActors {
            public function localUserRef(string $centralUserId): ?string
            {
                return $centralUserId === 'sin-perfil' ? null : "ref-$centralUserId";
            }
        });

        $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', TestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', TestIdentity::class);
        TestIdentity::$user = new GenericUser(['id' => 'admin']);
        TestIdentity::$hospital = 'hospital-a';
        TestIdentity::$timezone = null;
        config()->set('tenants.hospital_database_tenant_id', 'hospital-a');

        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    public function test_el_alta_responde_201_y_audita_con_la_referencia_local_del_actor(): void
    {
        $this->postJson('/api/v1/doctors', self::ALTA)
            ->assertCreated()
            ->assertJsonStructure(['doctor_profile_id'])
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->assertSame('ref-admin', $this->doctors->audits[0]['actor_id']);
    }

    public function test_asociar_una_especialidad_usa_el_id_de_la_ruta(): void
    {
        $this->postJson('/api/v1/doctors/doc-ana/specialties', ['specialty_code' => 'CARD', 'valid_from' => '2026-02-01', 'is_primary' => false])
            ->assertCreated()
            ->assertJsonPath('doctor_profile_id', 'doc-ana')
            ->assertJsonPath('is_primary', false);
    }

    public function test_la_disponibilidad_descuenta_la_cita_confirmada(): void
    {
        $this->getJson('/api/v1/availability?specialty=NEURO&date=2026-10-19')
            ->assertOk()
            ->assertJsonPath('doctors.0.slots', ['08:00', '09:00']);
    }

    public function test_toda_respuesta_del_modulo_lleva_trace_id_en_el_cuerpo_y_en_la_cabecera(): void
    {
        $responses = [
            $this->postJson('/api/v1/doctors', self::ALTA),
            $this->postJson('/api/v1/doctors', ['license_number' => ['x']] + self::ALTA),
            $this->getJson('/api/v1/availability?specialty=NEURO&date=2026-10-19'),
            $this->getJson('/api/v1/availability?specialty=NEURO&date=2026-02-30'),
        ];
        $seen = [];
        foreach ($responses as $response) {
            $trace = $response->json('trace_id');
            $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $trace, "estado {$response->status()}");
            $this->assertSame($trace, $response->headers->get('X-Trace-Id'));
            $seen[$trace] = true;
        }
        $this->assertCount(4, $seen, 'cada respuesta tiene su propio identificador');

        $this->app->bind(Router::class, static fn () => throw new \PDOException('simulated'));
        $down = $this->postJson('/api/v1/doctors', self::ALTA)->assertStatus(503);
        $this->assertSame($down->json('trace_id'), $down->headers->get('X-Trace-Id'));
    }

    public function test_las_reglas_del_modulo_conservan_su_codigo_y_estado(): void
    {
        $this->postJson('/api/v1/doctors', ['specialty_code' => 'NO-EXISTE'] + self::ALTA)->assertStatus(422)->assertJsonPath('code', 'SPECIALTY_INACTIVE');
        $this->postJson('/api/v1/doctors', ['license_number' => ['COL-2']] + self::ALTA)->assertStatus(422)->assertJsonPath('code', 'PAYLOAD_INVALID');
        $this->getJson('/api/v1/availability?specialty=NEURO&date=2026-02-30')->assertStatus(422)->assertJsonPath('code', 'INVALID_DATE_RANGE');
        $this->postJson('/api/v1/doctors/doc-nadie/specialties', ['specialty_code' => 'CARD', 'valid_from' => '2026-02-01'])->assertNotFound()->assertJsonPath('code', 'DOCTOR_NOT_FOUND');
    }

    public function test_sin_token_responde_401_en_las_tres_rutas(): void
    {
        TestIdentity::$user = null;

        $this->postJson('/api/v1/doctors', self::ALTA)->assertStatus(401);
        $this->postJson('/api/v1/doctors/doc-ana/specialties', [])->assertStatus(401);
        $this->getJson('/api/v1/availability?specialty=NEURO&date=2026-10-19')->assertStatus(401);
        $this->assertSame([], $this->doctors->audits);
    }

    public function test_cada_operacion_exige_su_propio_permiso(): void
    {
        TestIdentity::$user = new GenericUser(['id' => 'recepcion']);

        $this->getJson('/api/v1/availability?specialty=NEURO&date=2026-10-19')->assertOk();
        $this->postJson('/api/v1/doctors', self::ALTA)->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
        $this->postJson('/api/v1/doctors/doc-ana/specialties', ['specialty_code' => 'CARD', 'valid_from' => '2026-02-01'])->assertStatus(403);

        TestIdentity::$user = new GenericUser(['id' => 'enfermera']);
        $this->getJson('/api/v1/availability?specialty=NEURO&date=2026-10-19')->assertStatus(403);
        $this->assertSame([], $this->doctors->audits);
    }

    public function test_un_hospital_distinto_al_declarado_responde_403_y_no_guarda(): void
    {
        $this->postJson('/api/v1/doctors', self::ALTA, ['X-Tenant-ID' => 'hospital-ajeno'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'TENANT_MISMATCH');
        $this->assertFalse($this->doctors->licenseExists('COL-1'));
    }

    public function test_la_base_hospital_de_otro_hospital_o_sin_asignar_no_se_toca(): void
    {
        TestIdentity::$hospital = 'hospital-b';
        $this->postJson('/api/v1/doctors', self::ALTA, ['X-Tenant-ID' => 'hospital-b'])
            ->assertStatus(403)->assertJsonPath('error', 'HOSPITAL_DATABASE_TENANT_MISMATCH');

        TestIdentity::$hospital = 'hospital-a';
        config()->set('tenants.hospital_database_tenant_id', null);
        $this->getJson('/api/v1/availability?specialty=NEURO&date=2026-10-19')
            ->assertStatus(503)->assertJsonPath('error', 'HOSPITAL_DATABASE_TENANT_UNCONFIGURED');
        $this->assertFalse($this->doctors->licenseExists('COL-1'));
    }

    public function test_un_usuario_sin_referencia_local_responde_403_y_no_guarda(): void
    {
        $store = new InMemoryRbacStore(['Admin' => ['medicos.registrar']]);
        $store->grant('sin-perfil', 'hospital-a', 'Admin');
        foreach ([RoleResolver::class, PermissionProvider::class, DenialRecorder::class] as $contract) {
            $this->app->instance($contract, $store);
        }
        TestIdentity::$user = new GenericUser(['id' => 'sin-perfil']);

        $this->postJson('/api/v1/doctors', self::ALTA)->assertStatus(403)->assertJsonPath('code', 'ACTOR_NOT_PROVISIONED');
        $this->assertFalse($this->doctors->licenseExists('COL-1'));
    }

    public function test_las_citas_se_leen_en_la_zona_del_hospital_del_tenant_y_una_zona_invalida_cae_a_la_de_la_aplicacion(): void
    {
        $zones = [];
        $this->app->bind(Router::class, function ($app, array $parameters) use (&$zones) {
            $zones[] = $parameters['timezone'];

            return medicos_router($this->doctors, new InMemoryAppointmentReader(), new ConfigScheduleProvider([]));
        });
        $url = '/api/v1/availability?specialty=NEURO&date=2026-10-19';

        TestIdentity::$timezone = 'America/Guatemala';
        $this->getJson($url)->assertOk();
        TestIdentity::$timezone = 'Marte/Olimpo';
        $this->getJson($url)->assertOk();
        TestIdentity::$timezone = null;
        $this->getJson($url)->assertOk();

        $this->assertSame(['America/Guatemala', 'UTC', 'UTC'], $zones);
    }

    public function test_si_la_base_falla_responde_503_y_no_un_500(): void
    {
        $this->app->bind(Router::class, static fn () => throw new \PDOException('simulated'));

        $this->postJson('/api/v1/doctors', self::ALTA)->assertStatus(503)->assertJsonPath('code', 'DOCTOR_STORAGE_UNAVAILABLE');
    }

    public function test_una_base_hospital_inexistente_responde_503_con_el_actor_real(): void
    {
        config(['database.connections.hospital.database' => 'base_inexistente_asii04']);
        DB::purge('hospital');
        $this->app->forgetInstance(HospitalActors::class);
        $this->app->bind(HospitalActors::class);

        $this->postJson('/api/v1/doctors', self::ALTA)->assertStatus(503)->assertJsonPath('code', 'DOCTOR_STORAGE_UNAVAILABLE');
    }
}

/** Identidad de prueba; no se registra en providers ni rutas de producción. */
class TestIdentity
{
    public static ?GenericUser $user;

    public static ?string $hospital;

    public static ?string $timezone = null;

    public function handle(Request $request, Closure $next)
    {
        $request->setUserResolver(fn () => self::$user);
        // Modelo Hospital real: EnsureHospitalDatabaseTenant lo compara con la base HOSPITAL de la instancia.
        $request->attributes->set('tenant', new Hospital(['hospital_id' => self::$hospital, 'timezone' => self::$timezone]));

        return $next($request);
    }
}
