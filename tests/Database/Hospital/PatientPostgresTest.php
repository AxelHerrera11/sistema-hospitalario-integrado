<?php

declare(strict_types=1);

namespace Tests\Database\Hospital;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MicroHis\Pacientes\Application\PersistenceFailure;
use MicroHis\Pacientes\Domain\Patient;
use MicroHis\Pacientes\Persistence\PdoPatientRepository;
use PDO;
use PDOException;
use Tests\TestCase;

final class PatientPostgresTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('testing', app()->environment());
        $this->assertSame('his_hospital_test', DB::connection('hospital')->getDatabaseName());
        $this->assertSame('55433', (string) config('database.connections.hospital.port'));

        Artisan::call('migrate:fresh', [
            '--database' => 'hospital',
            '--path' => database_path('migrations/hospital'),
            '--realpath' => true,
            '--force' => true,
        ]);
        Artisan::call('migrate:fresh', [
            '--database' => 'central',
            '--path' => database_path('migrations/central'),
            '--realpath' => true,
            '--force' => true,
        ]);

        $this->pdo = DB::connection('hospital')->getPdo();
        $this->assertSame('local_patients', $this->pdo->query("SELECT to_regclass('public.local_patients')")->fetchColumn());
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo) && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }

        parent::tearDown();
    }

    public function test_insert_select_and_update_use_postgresql(): void
    {
        $repository = new PdoPatientRepository($this->pdo);
        $patient = $this->patient();
        $inserted = $repository->insert($patient);

        $this->assertNotNull($inserted->id);
        $this->assertSame($patient->code, $inserted->code);

        $selected = $repository->findByCode($patient->code);
        $this->assertNotNull($selected);
        $this->assertSame($inserted->id, $selected->id);
        $this->assertSame($patient->globalId, $selected->globalId);

        $repository->update($selected->update([
            'given_name' => 'PruebaActualizada',
            'family_name' => 'PacienteFicticio',
            'status' => 'inactive',
        ]));

        $updated = $repository->findByCode($patient->code);
        $this->assertNotNull($updated);
        $this->assertSame('PruebaActualizada', $updated->givenName);
        $this->assertSame('inactive', $updated->status);
        $this->assertSame($patient->globalId, $updated->globalId);
    }

    public function test_duplicate_code_is_rejected_by_postgresql(): void
    {
        $repository = new PdoPatientRepository($this->pdo);
        $patient = $this->patient();
        $repository->insert($patient);

        try {
            $repository->insert($this->patient(code: $patient->code));
            $this->fail('PostgreSQL accepted a duplicate patient code.');
        } catch (PersistenceFailure $failure) {
            $previous = $failure->getPrevious();
            $this->assertInstanceOf(PDOException::class, $previous);
            $this->assertSame('23505', $previous->getCode());
        }
    }

    public function test_duplicate_global_id_is_rejected_by_postgresql(): void
    {
        $repository = new PdoPatientRepository($this->pdo);
        $patient = $this->patient();
        $repository->insert($patient);

        try {
            $repository->insert($this->patient(globalId: $patient->globalId));
            $this->fail('PostgreSQL accepted a duplicate patient global ID.');
        } catch (PersistenceFailure $failure) {
            $previous = $failure->getPrevious();
            $this->assertInstanceOf(PDOException::class, $previous);
            $this->assertSame('23505', $previous->getCode());
        }
    }

    public function test_real_tenant_middleware_rejects_an_unknown_hospital(): void
    {
        $central = DB::connection('central');
        $this->assertSame('his_central_test', $central->getDatabaseName());
        $this->assertSame('55431', (string) config('database.connections.central.port'));
        $centralPdo = $central->getPdo();
        $tableCheck = $centralPdo->prepare("SELECT to_regclass(?)");

        foreach (['networks', 'hospitals', 'central_users', 'hospital_memberships', 'auth_credentials', 'auth_sessions'] as $table) {
            $tableCheck->execute(['public.'.$table]);
            $this->assertSame($table, $tableCheck->fetchColumn());
        }

        $this->getJson('/api/v1/patients/NO-DATA')
            ->assertBadRequest()->assertJsonPath('error', 'TENANT_REQUIRED');

        $unknownHospital = (string) Str::uuid();
        $this->assertFalse($central->table('hospitals')->where('hospital_id', $unknownHospital)->exists());

        $this->withHeaders(['X-Tenant-ID' => $unknownHospital])
            ->getJson('/api/v1/patients/NO-DATA')->assertNotFound()
            ->assertJsonPath('error', 'HOSPITAL_NOT_FOUND');
    }

    private function patient(?string $code = null, ?string $globalId = null): Patient
    {
        return Patient::register([
            'global_id' => $globalId ?? (string) Str::uuid(),
            'code' => $code ?? 'TEST-'.Str::uuid(),
            'given_name' => 'PacientePrueba',
            'family_name' => 'Ficticio',
            'birth_date' => '2000-01-01',
            'sex' => 'unknown',
            'contact' => null,
            'status' => 'active',
        ]);
    }
}
