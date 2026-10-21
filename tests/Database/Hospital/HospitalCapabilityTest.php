<?php

namespace Tests\Database\Hospital;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('database-integrity')]
class HospitalCapabilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
    }

    public function test_hospital_extension_migration_records_and_preserves_btree_gist_on_rollback(): void
    {
        $db = DB::connection('hospital');

        $this->assertGreaterThanOrEqual(150000, (int) $db->scalar("SELECT current_setting('server_version_num')::integer"));
        $this->assertSame('btree_gist', $db->scalar("SELECT extname FROM pg_extension WHERE extname = 'btree_gist'"));
        $this->assertSame(1, (int) $db->scalar("SELECT count(*) FROM migrations WHERE migration = '2026_08_11_000000_enable_hospital_extensions'"));

        Artisan::call('migrate:rollback', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--step' => 8, '--force' => true]);

        $this->assertSame('btree_gist', $db->scalar("SELECT extname FROM pg_extension WHERE extname = 'btree_gist'"));
        $this->assertFalse($this->tableExists('local_user_refs'));
    }

    private function tableExists(string $table): bool
    {
        return (bool) DB::connection('hospital')->scalar('SELECT to_regclass(?) IS NOT NULL', ['public.'.$table]);
    }
}
