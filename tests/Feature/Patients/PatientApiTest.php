<?php

declare(strict_types=1);

namespace Tests\Feature\Patients;

use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use MicroHis\Pacientes\Application\PatientRepository;
use MicroHis\Pacientes\Application\PersistenceFailure;
use MicroHis\Pacientes\Domain\Patient;
use MicroHis\Pacientes\Persistence\PdoPatientRepository;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use PDO;
use Tests\TestCase;

require_once dirname(__DIR__, 3) . '/micro-his/rbac/bootstrap.php';

final class PatientTestIdentity implements RoleResolver, DenialRecorder
{
    public static ?GenericUser $user = null;
    public static ?string $hospital = null;
    public static array $roles = [];
    public static array $denials = [];

    public function handle(Request $request, Closure $next)
    {
        $request->setUserResolver(static fn () => self::$user);
        $request->attributes->set('tenant', new class {
            public function getKey(): ?string
            {
                return PatientTestIdentity::$hospital;
            }
        });

        return $next($request);
    }

    public function rolesIn(string $userId, string $hospitalId): array
    {
        return self::$user !== null && $userId === self::$user->getAuthIdentifier() && $hospitalId === self::$hospital
            ? self::$roles
            : [];
    }

    public function record(array $denial): void
    {
        self::$denials[] = $denial;
    }

    public static function as(?string $role, string $hospital = 'hospital-a'): void
    {
        self::$user = $role === null ? null : new GenericUser(['id' => "user-{$role}"]);
        self::$hospital = $hospital;
        self::$roles = $role === null ? [] : [$role];
    }
}

final class PatientApiTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', PatientTestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', PatientTestIdentity::class);
        $this->app->instance(RoleResolver::class, new PatientTestIdentity());
        $this->app->instance(DenialRecorder::class, new PatientTestIdentity());
        PatientTestIdentity::as('medico');
        PatientTestIdentity::$denials = [];

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->exec('CREATE TABLE local_patients (
            local_patient_id TEXT PRIMARY KEY, global_id TEXT NOT NULL, code TEXT NOT NULL,
            given_name TEXT NOT NULL, family_name TEXT NOT NULL, birth_date TEXT NOT NULL,
            sex TEXT NOT NULL, contact TEXT, status TEXT NOT NULL
        )');
        $this->pdo->exec("INSERT INTO local_patients VALUES (
            '11111111-1111-4111-8111-111111111111', '22222222-2222-4222-8222-222222222222',
            'DEMO-001', 'Paciente', 'Demostración', '2000-01-01', 'unknown', NULL, 'active'
        )");
        $this->app->instance(PatientRepository::class, new PdoPatientRepository($this->pdo));
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    public function test_authorized_read_uses_pdo_and_maps_local_id_to_contract(): void
    {
        $this->getJson('/api/v1/patients/DEMO-001')->assertOk()
            ->assertJsonPath('patient.patient_id', '11111111-1111-4111-8111-111111111111')
            ->assertJsonPath('patient.global_id', '22222222-2222-4222-8222-222222222222')
            ->assertJsonMissingPath('patient.id')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_unknown_patient_returns_contract_not_found(): void
    {
        $this->getJson('/api/v1/patients/MISSING-001')->assertNotFound()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('code', 'PATIENT_NOT_FOUND')
            ->assertJsonPath('status', 404);
    }

    public function test_missing_identity_permission_and_tenant_mismatch_are_denied(): void
    {
        PatientTestIdentity::as(null);
        $this->getJson('/api/v1/patients/DEMO-001')->assertUnauthorized();

        PatientTestIdentity::as('tecnico_laboratorio');
        $this->getJson('/api/v1/patients/DEMO-001')->assertForbidden()->assertJsonPath('code', 'RBAC_DENIED');

        PatientTestIdentity::as('medico');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-b'])
            ->getJson('/api/v1/patients/DEMO-001')->assertForbidden()->assertJsonPath('code', 'TENANT_MISMATCH');
    }

    public function test_persistence_failure_does_not_expose_database_details(): void
    {
        $this->pdo->exec('DROP TABLE local_patients');
        $response = $this->getJson('/api/v1/patients/DEMO-001')->assertStatus(500)
            ->assertJsonPath('code', 'PERSISTENCE_FAILURE')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertDoesNotMatchRegularExpression('/SQLSTATE|no such table|PDO|\.php/i', (string) $response->getContent());
    }
}
