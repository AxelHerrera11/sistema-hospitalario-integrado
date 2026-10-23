<?php

namespace Tests\Database\Hospital;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('database-integrity')]
class PrescriptionIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
    }

    public function test_prescription_schema_is_local_and_uses_required_types_and_checks(): void
    {
        $db = DB::connection('hospital');

        $this->assertTrue($this->tableExists('prescriptions'));
        $this->assertSame('uuid', $this->columnType('prescription_id'));
        $this->assertSame('numeric', $this->columnType('dose_amount'));
        $this->assertSame(10, (int) $db->scalar("SELECT numeric_precision FROM information_schema.columns WHERE table_name = 'prescriptions' AND column_name = 'dose_amount'"));
        $this->assertSame(3, (int) $db->scalar("SELECT numeric_scale FROM information_schema.columns WHERE table_name = 'prescriptions' AND column_name = 'dose_amount'"));
        $this->assertSame(0, (int) $db->scalar("SELECT count(*) FROM information_schema.columns WHERE table_name = 'prescriptions' AND column_name = 'tenant_id'"));
        $this->assertSame(1, (int) $db->scalar("SELECT count(*) FROM pg_constraint WHERE conrelid = 'prescriptions'::regclass AND contype = 'c' AND pg_get_constraintdef(oid) ILIKE '%status%'"));
        $this->assertSame(1, (int) $db->scalar("SELECT count(*) FROM pg_constraint WHERE conrelid = 'prescriptions'::regclass AND conname = 'prescriptions_allergy_check_result_check' AND contype = 'c'"));
    }

    private function tableExists(string $table): bool
    {
        return (bool) DB::connection('hospital')->scalar('SELECT to_regclass(?) IS NOT NULL', ['public.'.$table]);
    }

    private function columnType(string $column): string
    {
        return (string) DB::connection('hospital')->scalar('SELECT data_type FROM information_schema.columns WHERE table_name = ? AND column_name = ?', ['prescriptions', $column]);
    }
}
