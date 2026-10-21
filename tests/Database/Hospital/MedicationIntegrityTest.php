<?php

namespace Tests\Database\Hospital;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/** Slice H5: tabla `medications` del catálogo (ASII-14) sobre PostgreSQL real. */
#[Group('database-integrity')]
class MedicationIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
    }

    public function test_valid_medication_gets_uuid_and_starts_active(): void
    {
        $id = $this->insertMedication('AMX-500');

        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $id);
        $row = DB::connection('hospital')->selectOne('SELECT validity, created_at IS NOT NULL AS dated FROM medications WHERE medication_id = ?', [$id]);
        $this->assertSame('active', $row->validity);
        $this->assertTrue($row->dated);
    }

    public function test_duplicate_code_is_rejected(): void
    {
        $this->insertMedication('AMX-500');

        $this->assertRejected('medications_code_unique', fn () => $this->insertMedication('AMX-500'));
    }

    public function test_code_must_be_normalized_like_the_domain(): void
    {
        foreach (['amx-500', ' AMX-500', 'A', '-AMX', 'AMX_500'] as $code) {
            $this->assertRejected('medications_code_format_check', fn () => $this->insertMedication($code));
        }
    }

    public function test_texts_must_be_trimmed_and_within_length(): void
    {
        $this->assertRejected('medications_name_check', fn () => $this->insertMedication('AMX-500', name: ' Amoxicilina'));
        $this->assertRejected('medications_name_check', fn () => $this->insertMedication('AMX-501', name: 'A'));
        $this->assertRejected('medications_active_ingredient_check', fn () => $this->insertMedication('AMX-502', ingredient: str_repeat('x', 121)));
        $this->assertRejected('medications_pharmacological_group_check', fn () => $this->insertMedication('AMX-503', group: ''));
    }

    public function test_validity_only_accepts_active_or_inactive(): void
    {
        $id = $this->insertMedication('AMX-500');
        DB::connection('hospital')->update("UPDATE medications SET validity = 'inactive' WHERE medication_id = ?", [$id]);

        $this->assertSame('inactive', DB::connection('hospital')->scalar('SELECT validity FROM medications WHERE medication_id = ?', [$id]));
        $this->assertRejected('medications_validity_check', fn () => DB::connection('hospital')->update("UPDATE medications SET validity = 'expired' WHERE medication_id = ?", [$id]));
    }

    public function test_rollback_removes_only_the_medication_slice(): void
    {
        $this->insertMedication('AMX-500');
        // En el orden descendente de un mismo lote, 000500 es la tercera migración tras 000900 y 000700.
        Artisan::call('migrate:rollback', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--step' => 3, '--force' => true]);

        $tables = array_column(DB::connection('hospital')->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'"), 'tablename');
        $this->assertNotContains('medications', $tables);
        $this->assertContains('allergies', $tables);
        $this->assertContains('vital_signs', $tables);

        Artisan::call('migrate', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
        $this->assertSame(0, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM medications'));
    }

    private function insertMedication(string $code, string $name = 'Amoxicilina 500 mg', string $ingredient = 'Amoxicilina', string $group = 'Antibiótico'): string
    {
        return (string) DB::connection('hospital')->scalar(
            'INSERT INTO medications (code, name, active_ingredient, pharmacological_group) VALUES (?, ?, ?, ?) RETURNING medication_id',
            [$code, $name, $ingredient, $group],
        );
    }

    private function assertRejected(string $constraint, callable $work): void
    {
        try {
            $work();
            $this->fail('Se esperaba el rechazo por '.$constraint);
        } catch (QueryException $error) {
            $this->assertStringContainsString($constraint, $error->getMessage());
        }
    }
}
