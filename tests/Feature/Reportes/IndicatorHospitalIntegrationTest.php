<?php

declare(strict_types=1);

namespace Tests\Feature\Reportes;

use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MicroHis\Rbac\Domain\{DenialRecorder, PermissionProvider, RoleResolver};
use MicroHis\Rbac\Persistence\InMemoryRbacStore;
use Tests\TestCase;

/**
 * Integración real (semanas 16 y 17): el endpoint del indicador IND-RA-01
 * calcula contra la base HOSPITAL PostgreSQL de pruebas (puerto 55433) y
 * nunca expone datos de pacientes ni detalles internos.
 */
final class IndicatorHospitalIntegrationTest extends TestCase
{
    private InMemoryRbacStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('testing', app()->environment());
        $this->assertSame('his_hospital_test', DB::connection('hospital')->getDatabaseName());
        $this->assertSame('55433', (string) config('database.connections.hospital.port'));

        $this->store = new InMemoryRbacStore([
            'CoordinadorCamas' => ['reportes.ver'],
            'Recepcion' => [],
        ]);
        $this->store->grant('user-1', 'hospital-a', 'CoordinadorCamas');

        foreach ([RoleResolver::class, PermissionProvider::class, DenialRecorder::class] as $contract) {
            $this->app->instance($contract, $this->store);
        }

        $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', IntegrationIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', IntegrationIdentity::class);

        IntegrationIdentity::$user = new GenericUser(['id' => 'user-1']);
        IntegrationIdentity::$hospital = 'hospital-a';

        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    public function test_calcula_ind_ra_01_contra_la_base_hospital_real(): void
    {
        $pdo = DB::connection('hospital')->getPdo();
        $pdo->beginTransaction();

        $service = 'RP-'.Str::uuid();
        $request = [
            'from' => '2026-01-01T00:00:00-06:00',
            'until' => '2026-02-01T00:00:00-06:00',
            'service_code' => $service,
        ];

        try {
            $patientId = $this->seedSyntheticPatient($pdo);

            $this->seedEpisode($pdo, $patientId, $service, 'inpatient', 'cancelled', '2026-01-06 10:00:00-06');
            $this->seedEpisode($pdo, $patientId, $service, 'inpatient', 'closed', '2026-01-12 10:00:00-06');
            $this->seedEpisode($pdo, $patientId, $service, 'inpatient', 'closed', '2026-01-20 10:00:00-06');

            $this->postJson('/api/v1/reports/indicators/IND-RA-01/calculate', $request)
                ->assertOk()
                ->assertJsonPath('data.indicator', 'IND-RA-01')
                ->assertJsonPath('data.formula_version', 'TCI-1.0')
                ->assertJsonPath('data.state', null)
                ->assertJsonPath('data.percentage', 33.33)
                ->assertJsonPath('meta.filters.service_code', $service);
        } finally {
            $pdo->rollBack();
        }

        $rand = $pdo->prepare('SELECT count(*) FROM care_episodes WHERE service_code = :s');
        $rand->execute([':s' => $service]);
        $this->assertSame(0, (int) $rand->fetchColumn());
    }

    public function test_un_periodo_sin_episodios_publica_el_estado_sin_datos(): void
    {
        $service = 'RP-'.Str::uuid();

        $this->postJson('/api/v1/reports/indicators/IND-RA-01/calculate', [
            'from' => '2020-01-01T00:00:00-06:00',
            'until' => '2020-02-01T00:00:00-06:00',
            'service_code' => $service,
        ])
            ->assertOk()
            ->assertJsonPath('data.state', 'SIN_DATOS_EN_PERIODO')
            ->assertJsonPath('data.percentage', null);
    }

    public function test_una_base_hospital_inexistente_responde_503_y_nunca_ceros(): void
    {
        config(['database.connections.hospital.database' => 'base_inexistente_asii23']);
        DB::purge('hospital');

        $this->postJson('/api/v1/reports/indicators/IND-RA-01/calculate', [
            'from' => '2026-01-01T00:00:00-06:00',
            'until' => '2026-02-01T00:00:00-06:00',
        ])
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'REPORTES_UNAVAILABLE')
            ->assertJsonMissingPath('data.percentage');
    }

    public function test_la_respuesta_y_los_errores_no_exponen_datos_de_pacientes(): void
    {
        $this->postJson('/api/v1/reports/indicators/IND-RA-01/calculate', [
            'from' => '2020-01-01T00:00:00-06:00',
            'until' => '2020-02-01T00:00:00-06:00',
        ])->assertOk();

        $payload = $this->postJson('/api/v1/reports/indicators/IND-RA-01/calculate', [
            'from' => '2020-01-01T00:00:00-06:00',
            'until' => '2020-02-01T00:00:00-06:00',
        ])->json();

        $this->assertSame(['data', 'meta'], array_keys($payload), 'La respuesta solo trae data y meta.');
        $this->assertSame(
            ['indicator', 'formula_version', 'percentage', 'state', 'computed_at'],
            array_keys($payload['data']),
            'El cuerpo tra solo trazabilidad del calculo, sin datos clinicos.',
        );

        $encoded = json_encode($payload, JSON_THROW_ON_ERROR);
        foreach (['patient', 'paciente', 'care_episode', 'local_patient_id', 'nombre', 'apellido', 'mpi'] as $prohibido) {
            $this->assertStringNotContainsStringIgnoringCase($prohibido, $encoded, $prohibido);
        }
    }

    private function seedSyntheticPatient(\PDO $pdo): string
    {
        $patientCode = 'ASII23-'.bin2hex(random_bytes(4));

        $insert = $pdo->prepare(
            'INSERT INTO local_patients (global_id, code, given_name, family_name, birth_date, sex)
             VALUES (:global, :code, :given, :family, :birth, :sex) RETURNING local_patient_id',
        );
        $insert->execute([
            ':global' => (string) Str::uuid(),
            ':code' => $patientCode,
            ':given' => 'Episodio',
            ':family' => 'Sintetico '.$patientCode,
            ':birth' => '1990-01-01',
            ':sex' => 'other',
        ]);

        return (string) $insert->fetchColumn();
    }

    private function seedEpisode(\PDO $pdo, string $patientId, string $service, string $type, string $status, string $endedAt): void
    {
        $pdo->prepare(
            'INSERT INTO care_episodes (local_patient_id, type, status, started_at, ended_at, reason, service_code)
             VALUES (:patient, :type, :status, :started, :ended, :reason, :service)',
        )->execute([
            ':patient' => $patientId,
            ':type' => $type,
            ':status' => $status,
            ':started' => str_replace('10:00:00', '09:00:00', $endedAt),
            ':ended' => $endedAt,
            ':reason' => 'raza de prueba del calculo del indicador',
            ':service' => $service,
        ]);
    }
}

/** Identidad de prueba; no se registra en providers ni rutas de producción. */
class IntegrationIdentity
{
    public static ?GenericUser $user;

    public static ?string $hospital;

    public function handle(Request $request, Closure $next)
    {
        $request->setUserResolver(fn () => self::$user);
        $request->attributes->set('tenant', new class {
            public function getKey(): ?string
            {
                return IntegrationIdentity::$hospital;
            }
        });

        return $next($request);
    }
}
