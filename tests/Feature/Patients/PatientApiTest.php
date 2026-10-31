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
            local_patient_id TEXT PRIMARY KEY DEFAULT "33333333-3333-4333-8333-333333333333",
            global_id TEXT NOT NULL, code TEXT NOT NULL,
            given_name TEXT NOT NULL, family_name TEXT NOT NULL, birth_date TEXT NOT NULL,
            sex TEXT NOT NULL, contact TEXT, status TEXT NOT NULL, updated_at TEXT,
            UNIQUE (global_id), UNIQUE (code)
        )');
        $this->pdo->sqliteCreateFunction('now', static fn (): string => date('Y-m-d H:i:s'));
        $this->pdo->exec("INSERT INTO local_patients VALUES (
            '11111111-1111-4111-8111-111111111111', '22222222-2222-4222-8222-222222222222',
            'DEMO-001', 'Paciente', 'Demostración', '2000-01-01', 'unknown', NULL, 'active', NULL
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

    public function test_register_creates_patient_and_returns_contract_id(): void
    {
        PatientTestIdentity::as('recepcion');
        $this->postJson('/api/v1/patients', $this->draft(['status' => 'inactive']))->assertCreated()
            ->assertJsonPath('patient.patient_id', '33333333-3333-4333-8333-333333333333')
            ->assertJsonPath('patient.code', 'DEMO-002')
            ->assertJsonPath('patient.status', 'inactive')
            ->assertJsonMissingPath('patient.id');
    }

    public function test_register_maps_code_and_global_id_duplicates_to_conflict(): void
    {
        PatientTestIdentity::as('recepcion');
        $this->postJson('/api/v1/patients', $this->draft(['code' => 'DEMO-001']))
            ->assertStatus(409)->assertJsonPath('code', 'PATIENT_CODE_EXISTS');
        $this->postJson('/api/v1/patients', $this->draft(['global_id' => '22222222-2222-4222-8222-222222222222']))
            ->assertStatus(409)->assertJsonPath('code', 'PATIENT_GLOBAL_ID_EXISTS');
    }

    public function test_register_rejects_invalid_uuid_and_future_birth_date(): void
    {
        PatientTestIdentity::as('recepcion');
        $this->postJson('/api/v1/patients', $this->draft(['global_id' => 'not-a-uuid']))
            ->assertStatus(422)->assertJsonPath('field', 'global_id');
        $this->postJson('/api/v1/patients', $this->draft(['birth_date' => '2999-01-01']))
            ->assertStatus(422)->assertJsonPath('field', 'birth_date');
    }

    public function test_update_changes_editable_fields_and_preserves_identity(): void
    {
        PatientTestIdentity::as('recepcion');
        $this->patchJson('/api/v1/patients/DEMO-001', ['given_name' => 'Nombre actualizado'])
            ->assertOk()->assertJsonPath('patient.given_name', 'Nombre actualizado')
            ->assertJsonPath('patient.patient_id', '11111111-1111-4111-8111-111111111111')
            ->assertJsonPath('patient.code', 'DEMO-001')
            ->assertJsonMissingPath('patient.id');
    }

    public function test_update_returns_not_found_and_rejects_identity_fields(): void
    {
        PatientTestIdentity::as('recepcion');
        $this->patchJson('/api/v1/patients/MISSING-001', ['given_name' => 'Cambio'])
            ->assertNotFound()->assertJsonPath('code', 'PATIENT_NOT_FOUND');
        $this->patchJson('/api/v1/patients/DEMO-001', ['global_id' => '33333333-3333-4333-8333-333333333333'])
            ->assertStatus(422)->assertJsonPath('code', 'VALIDATION_FAILED');
    }

    public function test_patient_write_permissions_are_enforced_per_operation(): void
    {
        PatientTestIdentity::as('medico');
        $this->postJson('/api/v1/patients', $this->draft())->assertForbidden()->assertJsonPath('code', 'RBAC_DENIED');
        $this->patchJson('/api/v1/patients/DEMO-001', ['given_name' => 'No autorizado'])
            ->assertForbidden()->assertJsonPath('code', 'RBAC_DENIED');
    }

    private function draft(array $overrides = []): array
    {
        return array_replace([
            'global_id' => '33333333-3333-4333-8333-333333333333',
            'code' => 'DEMO-002',
            'given_name' => 'Paciente',
            'family_name' => 'Nuevo',
            'birth_date' => '2001-02-03',
            'sex' => 'unknown',
        ], $overrides);
    }
}
