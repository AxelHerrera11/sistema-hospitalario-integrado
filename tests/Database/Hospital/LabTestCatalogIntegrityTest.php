<?php

namespace Tests\Database\Hospital;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/** Slice H6: tabla `lab_tests` del catálogo de pruebas (ASII-17) sobre PostgreSQL real. */
#[Group('database-integrity')]
class LabTestCatalogIntegrityTest extends TestCase
{
    private const SIN_RANGOS = ['reference_low' => null, 'reference_high' => null, 'critical_low' => null, 'critical_high' => null];

    private string $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
        $this->actor = DB::connection('hospital')->scalar("INSERT INTO local_user_refs (central_user_id, membership_id, display_name, role_code, synced_at)
            VALUES (gen_random_uuid(), gen_random_uuid(), 'Administración de prueba', 'admin_hospital', now()) RETURNING local_user_ref_id");
    }

    public function test_valid_numeric_test_gets_uuid_and_starts_active(): void
    {
        $id = $this->insertTest('GLU');

        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $id);
        $this->assertSame('active', DB::connection('hospital')->scalar('SELECT status FROM lab_tests WHERE lab_test_id = ?', [$id]));
        $this->insertTest('HIV', ['result_type' => 'qualitative', 'unit' => null, ...self::SIN_RANGOS]);
    }

    public function test_code_and_texts_follow_the_domain(): void
    {
        foreach (['glu', ' GLU', 'G', 'G LU'] as $code) {
            $this->assertRejected('lab_tests_code_check', fn () => $this->insertTest($code));
        }
        $this->assertRejected('lab_tests_name_check', fn () => $this->insertTest('NA', ['name' => ' Sodio']));
        $this->assertRejected('lab_tests_min_volume_check', fn () => $this->insertTest('NA', ['min_volume_ml' => 0]));
        $this->assertRejected('lab_tests_result_type_check', fn () => $this->insertTest('NA', ['result_type' => 'ordinal', 'unit' => null, ...self::SIN_RANGOS]));
    }

    public function test_numeric_ranges_must_be_complete_and_ordered(): void
    {
        $this->assertRejected('lab_tests_numeric_definition_check', fn () => $this->insertTest('NA', ['unit' => null]));
        $this->assertRejected('lab_tests_numeric_definition_check', fn () => $this->insertTest('NA', ['critical_low' => null]));
        $this->assertRejected('lab_tests_numeric_definition_check', fn () => $this->insertTest('NA', ['critical_low' => 80]));
        $this->assertRejected('lab_tests_non_numeric_ranges_check', fn () => $this->insertTest('HIV', ['result_type' => 'qualitative', 'unit' => null]));
    }

    public function test_only_one_active_version_per_code_and_date(): void
    {
        $this->insertTest('GLU', ['valid_to' => '2026-12-31']);
        $this->assertRejected('lab_tests_code_version_unique', fn () => $this->insertTest('GLU', ['valid_from' => '2027-01-01']));
        $this->assertRejected('lab_tests_active_validity_exclusion', fn () => $this->insertTest('GLU', ['version_no' => 2, 'valid_from' => '2026-12-01']));
        $this->insertTest('GLU', ['version_no' => 2, 'valid_from' => '2026-12-31']);
        $this->assertSame(2, (int) DB::connection('hospital')->scalar("SELECT count(*) FROM lab_tests WHERE code = 'GLU'"));
    }

    public function test_versions_are_retained_and_need_an_existing_author(): void
    {
        $id = $this->insertTest('GLU');
        $this->assertRejected('lab_tests_delete_protection', fn () => DB::connection('hospital')->delete('DELETE FROM lab_tests WHERE lab_test_id = ?', [$id]));
        $this->assertRejected('lab_tests_created_by_user_ref_id_fkey', fn () => $this->insertTest('NA', ['created_by_user_ref_id' => '00000000-0000-4000-8000-0000000000ff']));
    }

    public function test_rollback_removes_only_the_catalog(): void
    {
        $this->insertTest('GLU');
        Artisan::call('migrate:rollback', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--step' => $this->hospitalRollbackSteps('2026_08_11_000600'), '--force' => true]);

        $tables = array_column(DB::connection('hospital')->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'"), 'tablename');
        $this->assertNotContains('lab_tests', $tables);
        $this->assertContains('medications', $tables);
        $this->assertContains('local_user_refs', $tables);
    }

    private function insertTest(string $code, array $overrides = []): string
    {
        $row = $overrides + ['name' => 'Glucosa', 'specimen_type' => 'serum', 'min_volume_ml' => 0.5, 'result_type' => 'numeric', 'unit' => 'mg/dL',
            'reference_low' => 70, 'reference_high' => 100, 'critical_low' => 40, 'critical_high' => 400, 'version_no' => 1,
            'valid_from' => '2026-01-01', 'valid_to' => null, 'created_by_user_ref_id' => $this->actor];

        return DB::connection('hospital')->scalar('INSERT INTO lab_tests (code, '.implode(', ', array_keys($row)).') VALUES (?'.str_repeat(', ?', count($row)).')
            RETURNING lab_test_id', [$code, ...array_values($row)]);
    }

    private function assertRejected(string $constraint, callable $statement): void
    {
        try {
            DB::connection('hospital')->transaction(fn () => $statement());
            $this->fail("Se esperaba el rechazo de {$constraint}.");
        } catch (QueryException $error) {
            $this->assertStringContainsString($constraint, $error->getMessage());
        }
    }
}
