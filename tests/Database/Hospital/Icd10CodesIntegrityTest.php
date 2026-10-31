<?php

namespace Tests\Database\Hospital;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/** Catálogo CIE-10 (`icd10_codes`) del esquema HOSPITAL sobre PostgreSQL real (ASII-09). */
#[Group('database-integrity')]
class Icd10CodesIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
    }

    public function test_table_has_the_requested_columns_and_types(): void
    {
        $columns = DB::connection('hospital')->select("SELECT column_name, data_type, character_maximum_length, is_nullable, column_default
            FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'icd10_codes' ORDER BY ordinal_position");

        $this->assertSame(['code', 'description', 'active'], array_column($columns, 'column_name'));
        $this->assertSame('character varying', $columns[0]->data_type);
        $this->assertSame(10, (int) $columns[0]->character_maximum_length);
        $this->assertSame('NO', $columns[0]->is_nullable);
        $this->assertSame('text', $columns[1]->data_type);
        $this->assertSame('NO', $columns[1]->is_nullable);
        $this->assertSame('boolean', $columns[2]->data_type);
        $this->assertSame('NO', $columns[2]->is_nullable);
        $this->assertSame('true', $columns[2]->column_default);
    }

    public function test_valid_codes_are_inserted_and_start_active(): void
    {
        foreach (['J18.9' => 'Neumonía, no especificada', 'I10' => 'Hipertensión esencial', 'E11.9' => 'Diabetes tipo 2 sin complicaciones', 'S72.001A' => 'Fractura desplazada del cuello femoral'] as $code => $description) {
            $this->insertCode($code, $description);
        }

        $this->assertSame(1, (int) DB::connection('hospital')->scalar('SELECT active::int FROM icd10_codes WHERE code = ?', ['J18.9']));
        $this->assertSame(4, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM icd10_codes'));
    }

    public function test_code_is_primary_key_and_must_be_normalized(): void
    {
        $this->insertCode('J18.9');

        $this->assertRejected('icd10_codes_pkey', fn () => $this->insertCode('J18.9'));

        foreach (['j18.9', ' J18.9', 'J18.9 ', 'J18.', '123', 'J18.9-'] as $code) {
            $this->assertRejected('icd10_codes_code_check', fn () => $this->insertCode($code));
        }
    }

    public function test_description_must_be_trimmed_and_not_empty(): void
    {
        $this->assertRejected('icd10_codes_description_check', fn () => $this->insertCode('J18.9', ''));
        $this->assertRejected('icd10_codes_description_check', fn () => $this->insertCode('J18.9', ' Neumonía'));
        $this->assertRejected('icd10_codes_description_check', fn () => $this->insertCode('J18.9', '   '));
    }

    public function test_active_is_boolean_and_can_be_deactivated_without_deleting(): void
    {
        $this->insertCode('J18.9');

        DB::connection('hospital')->update('UPDATE icd10_codes SET active = false WHERE code = ?', ['J18.9']);

        $this->assertSame(0, (int) DB::connection('hospital')->scalar('SELECT active::int FROM icd10_codes WHERE code = ?', ['J18.9']));
        $this->assertSame(1, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM icd10_codes WHERE code = ?', ['J18.9']));
        $this->assertRejected('icd10_codes_active_not_null', fn () => DB::connection('hospital')->update('UPDATE icd10_codes SET active = NULL WHERE code = ?', ['J18.9']));
    }

    public function test_rollback_removes_only_the_catalog(): void
    {
        $this->insertCode('J18.9');
        Artisan::call('migrate:rollback', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--step' => $this->hospitalRollbackSteps('2026_10_31_000001'), '--force' => true]);

        $tables = array_column(DB::connection('hospital')->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'"), 'tablename');
        $this->assertNotContains('icd10_codes', $tables);
        $this->assertContains('clinical_notes', $tables);
        $this->assertContains('diagnoses', $tables);
    }

    private function insertCode(string $code, string $description = 'Diagnóstico de prueba'): void
    {
        DB::connection('hospital')->insert('INSERT INTO icd10_codes (code, description) VALUES (?, ?)', [$code, $description]);
    }

    private function assertRejected(string $constraint, callable $statement): void
    {
        try {
            $statement();
            $this->fail("Se esperaba el rechazo de {$constraint}.");
        } catch (QueryException $error) {
            $this->assertStringContainsString($constraint, $error->getMessage());
        }
    }
}
