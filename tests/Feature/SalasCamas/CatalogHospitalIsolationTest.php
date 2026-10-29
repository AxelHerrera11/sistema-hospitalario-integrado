<?php

declare(strict_types=1);

namespace Tests\Feature\SalasCamas;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Feature\Tenants\TenantAuthTestCase;

final class CatalogHospitalIsolationTest extends TenantAuthTestCase
{
    private \PDO $hospitalPdo;

    private string $schemaA;

    private string $schemaB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('testing', app()->environment());
        $this->assertSame('his_hospital_test', DB::connection('hospital')->getDatabaseName());
        $this->assertSame('55433', (string) config('database.connections.hospital.port'));

        $this->hospitalPdo = DB::connection('hospital')->getPdo();
        $this->hospitalPdo->beginTransaction();
        $this->schemaA = 'hospital_a_'.str_replace('-', '', (string) Str::uuid());
        $this->schemaB = 'hospital_b_'.str_replace('-', '', (string) Str::uuid());

        foreach ([$this->schemaA, $this->schemaB] as $schema) {
            $this->hospitalPdo->exec("CREATE SCHEMA {$schema}");
            $this->hospitalPdo->exec(
                "CREATE TABLE {$schema}.wards (
                    ward_id uuid PRIMARY KEY,
                    code text NOT NULL,
                    name text NOT NULL,
                    location text NOT NULL,
                    status text NOT NULL
                )",
            );
            $this->hospitalPdo->exec(
                "CREATE TABLE {$schema}.beds (
                    bed_id uuid PRIMARY KEY,
                    ward_id uuid NOT NULL,
                    code text NOT NULL,
                    type text NOT NULL,
                    status text NOT NULL
                )",
            );
        }

        $this->addMembership($this->userId, $this->hospitalA, 'superadmin');
        $this->addMembership($this->userId, $this->hospitalB, 'superadmin');
        config(['tenants.hospital_database_tenant_id' => $this->hospitalA]);
    }

    protected function tearDown(): void
    {
        if (isset($this->hospitalPdo) && $this->hospitalPdo->inTransaction()) {
            $this->hospitalPdo->rollBack();
        }

        parent::tearDown();
    }

    public function test_valid_hospitals_cannot_read_each_others_catalog(): void
    {
        $wardA = (string) Str::uuid();
        $bedA = (string) Str::uuid();
        $wardB = (string) Str::uuid();
        $bedB = (string) Str::uuid();
        $this->insertCatalog($this->schemaA, $wardA, $bedA, 'WARD-A', 'BED-A');
        $this->insertCatalog($this->schemaB, $wardB, $bedB, 'WARD-B', 'BED-B');

        $loginA = $this->login(hospitalId: $this->hospitalA)->assertOk();
        $loginB = $this->login(hospitalId: $this->hospitalB)->assertOk();
        $tokenA = $this->tokenFrom($loginA);
        $tokenB = $this->tokenFrom($loginB);

        $this->setSearchPath($this->schemaA);
        $this->getJson('/api/v1/wards', $this->headers($tokenA, $this->hospitalA))
            ->assertOk()
            ->assertJsonPath('data.0.ward_id', $wardA);
        $this->getJson("/api/v1/wards/{$wardA}/beds", $this->headers($tokenA, $this->hospitalA))
            ->assertOk()
            ->assertJsonPath('data.0.bed_id', $bedA);
        $this->getJson('/api/v1/wards', $this->headers($tokenB, $this->hospitalB))
            ->assertForbidden()
            ->assertJsonPath('error', 'HOSPITAL_DATABASE_TENANT_MISMATCH')
            ->assertDontSee($wardA);
        $this->getJson("/api/v1/wards/{$wardA}/beds", $this->headers($tokenB, $this->hospitalB))
            ->assertForbidden()
            ->assertJsonPath('error', 'HOSPITAL_DATABASE_TENANT_MISMATCH')
            ->assertDontSee($bedA);

        config(['tenants.hospital_database_tenant_id' => $this->hospitalB]);
        $this->setSearchPath($this->schemaB);
        $this->getJson('/api/v1/wards', $this->headers($tokenB, $this->hospitalB))
            ->assertOk()
            ->assertJsonPath('data.0.ward_id', $wardB);
        $this->getJson("/api/v1/wards/{$wardB}/beds", $this->headers($tokenB, $this->hospitalB))
            ->assertOk()
            ->assertJsonPath('data.0.bed_id', $bedB);
        $this->getJson('/api/v1/wards', $this->headers($tokenA, $this->hospitalA))
            ->assertForbidden()
            ->assertJsonPath('error', 'HOSPITAL_DATABASE_TENANT_MISMATCH')
            ->assertDontSee($wardB);
        $this->getJson("/api/v1/wards/{$wardB}/beds", $this->headers($tokenA, $this->hospitalA))
            ->assertForbidden()
            ->assertJsonPath('error', 'HOSPITAL_DATABASE_TENANT_MISMATCH')
            ->assertDontSee($bedB);
    }

    public function test_unassigned_hospital_database_fails_closed(): void
    {
        config(['tenants.hospital_database_tenant_id' => null]);

        $login = $this->login(hospitalId: $this->hospitalA)->assertOk();

        $this->getJson('/api/v1/wards', $this->headers($this->tokenFrom($login), $this->hospitalA))
            ->assertStatus(503)
            ->assertJsonPath('error', 'HOSPITAL_DATABASE_TENANT_UNCONFIGURED');
    }

    public function test_rbac_denial_is_preserved_when_database_assignment_is_missing(): void
    {
        $userId = (string) Str::uuid();
        $email = 'without-catalog-permission@example.test';
        $this->createUser($userId, $email);
        $this->createCredential($userId, $email, 'secret-password');
        $this->addMembership($userId, $this->hospitalA, 'medico');
        config(['tenants.hospital_database_tenant_id' => null]);

        $login = $this->login(email: $email, hospitalId: $this->hospitalA)->assertOk();

        $this->getJson('/api/v1/wards', $this->headers($this->tokenFrom($login), $this->hospitalA))
            ->assertForbidden()
            ->assertJsonPath('code', 'RBAC_DENIED');
    }

    private function insertCatalog(string $schema, string $wardId, string $bedId, string $wardCode, string $bedCode): void
    {
        $ward = $this->hospitalPdo->prepare(
            "INSERT INTO {$schema}.wards (ward_id, code, name, location, status)
             VALUES (:id, :code, :name, :location, 'active')",
        );
        $ward->execute([
            ':id' => $wardId,
            ':code' => $wardCode,
            ':name' => $wardCode,
            ':location' => 'Test schema',
        ]);

        $bed = $this->hospitalPdo->prepare(
            "INSERT INTO {$schema}.beds (bed_id, ward_id, code, type, status)
             VALUES (:id, :ward, :code, 'standard', 'available')",
        );
        $bed->execute([
            ':id' => $bedId,
            ':ward' => $wardId,
            ':code' => $bedCode,
        ]);
    }

    private function setSearchPath(string $schema): void
    {
        $this->hospitalPdo->exec("SET LOCAL search_path TO {$schema}");
    }

    private function headers(string $token, string $hospitalId): array
    {
        return [
            'Authorization' => "Bearer {$token}",
            'X-Tenant-ID' => $hospitalId,
        ];
    }
}
