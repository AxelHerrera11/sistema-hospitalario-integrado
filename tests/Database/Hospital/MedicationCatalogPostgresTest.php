<?php

namespace Tests\Database\Hospital;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\MedicationCatalog\CatalogTestIdentity;
use Tests\TestCase;

/**
 * API v1 del catálogo sobre la tabla real `medications` (H5) en PostgreSQL. El repositorio es el del provider sobre la
 * conexión `hospital`; solo se sustituye la identidad (tenant y jwt.auth), igual que en las pruebas Feature.
 */
#[Group('database-integrity')]
class MedicationCatalogPostgresTest extends TestCase
{
    private const AMOX = ['code' => 'amx-500', 'name' => 'Amoxicilina 500 mg', 'active_ingredient' => 'Amoxicilina', 'pharmacological_group' => 'Antibiótico'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
        CatalogTestIdentity::install($this->app);
        CatalogTestIdentity::as('admin_hospital');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    public function test_registration_is_persisted_and_read_back_by_uuid(): void
    {
        $id = $this->postJson('/api/v1/medications', self::AMOX)->assertStatus(201)
            ->assertJsonPath('medication.code', 'AMX-500')->assertJsonPath('medication.validity', 'active')->json('medication.medication_id');

        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $id);
        $this->assertSame('AMX-500', DB::connection('hospital')->scalar('SELECT code FROM medications WHERE medication_id = ?', [$id]));
        CatalogTestIdentity::as('enfermeria');
        $this->getJson("/api/v1/medications/{$id}")->assertOk()->assertJsonPath('medication.name', 'Amoxicilina 500 mg');
    }

    public function test_search_is_case_insensitive_for_non_ascii_letters(): void
    {
        $this->postJson('/api/v1/medications', ['code' => 'asa-100', 'name' => 'Ácido acetilsalicílico 100 mg', 'active_ingredient' => 'Ácido acetilsalicílico', 'pharmacological_group' => 'Analgésico'])->assertStatus(201);
        CatalogTestIdentity::as('medico');

        foreach (['ácido', 'ÁCIDO', 'ACETILSALICÍLICO', 'asa-1'] as $term) {
            $this->getJson('/api/v1/medications?q='.rawurlencode($term))->assertOk()->assertJsonPath('count', 1)->assertJsonPath('results.0.code', 'ASA-100');
        }
        $this->getJson('/api/v1/medications?q=acido')->assertOk()->assertJsonPath('count', 0);
    }

    public function test_validity_change_is_persisted_and_repeating_it_is_rejected(): void
    {
        $id = $this->postJson('/api/v1/medications', self::AMOX)->json('medication.medication_id');

        $this->patchJson("/api/v1/medications/{$id}/validity", ['validity' => 'inactive'])->assertOk()->assertJsonPath('medication.validity', 'inactive');
        $this->assertSame('inactive', DB::connection('hospital')->scalar('SELECT validity FROM medications WHERE medication_id = ?', [$id]));
        $this->assertTrue((bool) DB::connection('hospital')->scalar('SELECT updated_at >= created_at FROM medications WHERE medication_id = ?', [$id]));
        $this->patchJson("/api/v1/medications/{$id}/validity", ['validity' => 'inactive'])->assertStatus(422);
    }

    public function test_validation_duplicates_and_unknown_ids(): void
    {
        $this->postJson('/api/v1/medications', self::AMOX)->assertStatus(201);

        $this->postJson('/api/v1/medications', ['code' => 'AMX-500'] + self::AMOX)->assertStatus(409)->assertJsonPath('error', 'MEDICATION_CODE_DUPLICATED');
        $this->postJson('/api/v1/medications', ['name' => 'A', 'code' => 'ibu-400'] + self::AMOX)->assertStatus(422)->assertJsonPath('field', 'name');
        $this->getJson('/api/v1/medications/00000000-0000-4000-8000-000000000000')->assertStatus(404)->assertJsonPath('error', 'MEDICATION_NOT_FOUND');
        $this->getJson('/api/v1/medications/999')->assertStatus(404)->assertJsonPath('error', 'MEDICATION_NOT_FOUND');
        $this->patchJson('/api/v1/medications/999/validity', ['validity' => 'inactive'])->assertStatus(404);
        $this->assertSame(1, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM medications'));
    }

    public function test_identity_roles_and_hospital_are_enforced_before_postgres(): void
    {
        CatalogTestIdentity::as(null);
        $this->postJson('/api/v1/medications', self::AMOX)->assertStatus(401);
        CatalogTestIdentity::as('medico');
        $this->postJson('/api/v1/medications', self::AMOX)->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
        CatalogTestIdentity::as('recepcion');
        $this->getJson('/api/v1/medications?q=amox')->assertStatus(403);
        CatalogTestIdentity::as('admin_hospital', 'hospital-a', 'hospital-b');
        $this->postJson('/api/v1/medications', self::AMOX)->assertStatus(403);
        CatalogTestIdentity::as('admin_hospital');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-b'])->postJson('/api/v1/medications', self::AMOX)->assertStatus(403)->assertJsonPath('code', 'TENANT_MISMATCH');

        $this->assertSame(0, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM medications'));
    }

    public function test_audit_failure_rolls_back_the_hospital_write(): void
    {
        $id = $this->postJson('/api/v1/medications', self::AMOX)->assertStatus(201)->json('medication.medication_id');
        $this->assertSame('medicamentos.registrado', CatalogTestIdentity::$audited[0]['event']);

        // M6 falla cerrado: sin evento en CENTRAL, PostgreSQL revierte el alta y el cambio de vigencia.
        CatalogTestIdentity::$auditDown = true;
        $this->postJson('/api/v1/medications', ['code' => 'ibu-400'] + self::AMOX)->assertStatus(503)->assertJsonPath('error', 'AUDIT_UNAVAILABLE');
        $this->patchJson("/api/v1/medications/{$id}/validity", ['validity' => 'inactive'])->assertStatus(503);
        $this->assertSame(1, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM medications'));
        $this->assertSame('active', DB::connection('hospital')->scalar('SELECT validity FROM medications WHERE medication_id = ?', [$id]));
        $this->assertFalse(DB::connection('hospital')->getPdo()->inTransaction());
    }

    public function test_storage_failure_answers_503_and_writes_nothing(): void
    {
        DB::connection('hospital')->statement('ALTER TABLE medications RENAME TO medications_unavailable');
        try {
            $this->postJson('/api/v1/medications', self::AMOX)->assertStatus(503)->assertJsonPath('error', 'CATALOG_UNAVAILABLE')->assertJsonMissingPath('medication');
            $this->getJson('/api/v1/medications?q=amox')->assertStatus(503);
        } finally {
            DB::connection('hospital')->statement('ALTER TABLE medications_unavailable RENAME TO medications');
        }
        $this->assertSame(0, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM medications'));
    }
}
