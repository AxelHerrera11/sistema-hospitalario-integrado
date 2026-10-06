<?php

namespace Tests\Database\Central;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('database-integrity')]
class NetworkIdentityIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', [
            '--database' => 'central',
            '--path' => database_path('migrations/central'),
            '--realpath' => true,
            '--force' => true,
        ]);
    }

    public function test_network_identity_inventory_uses_uuid_and_timestamptz_columns(): void
    {
        $tables = DB::connection('central')->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename");
        $names = array_map(fn (object $table) => $table->tablename, $tables);

        $this->assertContains('networks', $names);
        $this->assertContains('hospitals', $names);
        $this->assertContains('central_users', $names);
        $this->assertContains('hospital_memberships', $names);
        $this->assertSame('uuid', $this->columnType('networks', 'network_id'));
        $this->assertSame('uuid', $this->columnType('hospitals', 'network_id'));
        $this->assertSame('timestamp with time zone', $this->columnType('hospital_memberships', 'valid_from'));
    }

    public function test_local_fk_indexes_checks_and_active_uniques_are_enforced(): void
    {
        $db = DB::connection('central');
        $db->insert("INSERT INTO networks (network_id, name, status, timezone, metadata) VALUES ('00000000-0000-0000-0000-000000000001', 'Network', 'active', 'America/Guatemala', '{}')");
        $db->insert("INSERT INTO hospitals (hospital_id, network_id, code, name, status, timezone, metadata) VALUES ('00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000001', 'GT-01', 'Hospital', 'active', 'America/Guatemala', '{}')");
        $db->insert("INSERT INTO central_users (user_id, subject, email, display_name, status, metadata) VALUES ('00000000-0000-0000-0000-000000000003', 'oidc|one', 'one@example.test', 'One', 'active', '{}')");
        $db->insert("INSERT INTO hospital_memberships (membership_id, user_id, hospital_id, role_code, status, valid_from, metadata) VALUES ('00000000-0000-0000-0000-000000000004', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000002', 'clinician', 'active', now(), '{}')");

        $this->assertRejected(fn () => $db->insert("INSERT INTO hospitals (hospital_id, network_id, code, name, status, timezone, metadata) VALUES ('00000000-0000-0000-0000-000000000005', '00000000-0000-0000-0000-000000000001', 'GT-01', 'Duplicate', 'active', 'America/Guatemala', '{}')"));
        $this->assertRejected(fn () => $db->insert("INSERT INTO central_users (user_id, subject, email, display_name, status, metadata) VALUES ('00000000-0000-0000-0000-000000000006', 'oidc|two', 'ONE@example.test', 'Two', 'active', '{}')"));
        $db->insert("INSERT INTO central_users (user_id, subject, email, display_name, status, metadata) VALUES ('00000000-0000-0000-0000-000000000009', 'oidc|null-one', NULL, 'Null One', 'active', '{}')");
        $db->insert("INSERT INTO central_users (user_id, subject, email, display_name, status, metadata) VALUES ('00000000-0000-0000-0000-000000000010', 'oidc|null-two', NULL, 'Null Two', 'active', '{}')");
        $this->assertRejected(fn () => $db->insert("INSERT INTO hospital_memberships (membership_id, user_id, hospital_id, role_code, status, valid_from, metadata) VALUES ('00000000-0000-0000-0000-000000000007', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000002', 'clinician', 'active', now(), '{}')"));
        $this->assertRejected(fn () => $db->insert("INSERT INTO hospital_memberships (membership_id, user_id, hospital_id, role_code, status, valid_from, valid_to, metadata) VALUES ('00000000-0000-0000-0000-000000000008', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000002', 'nurse', 'active', now(), now() - interval '1 minute', '{}')"));
        $this->assertRejected(fn () => $db->delete("DELETE FROM networks WHERE network_id = '00000000-0000-0000-0000-000000000001'"));
        $indexes = array_map(
            fn (object $index) => $index->indexname,
            $db->select("SELECT indexname FROM pg_indexes WHERE schemaname = 'public' ORDER BY indexname"),
        );

        $this->assertContains('hospitals_network_id_index', $indexes);
        $this->assertContains('hospital_memberships_user_id_index', $indexes);
        $this->assertContains('hospital_memberships_hospital_id_index', $indexes);
    }

    public function test_central_network_identity_migrations_rollback_and_reinstall(): void
    {
        Artisan::call('migrate:reset', ['--database' => 'central', '--path' => database_path('migrations/central'), '--realpath' => true, '--force' => true]);
        foreach (['networks', 'hospitals', 'central_users', 'hospital_memberships'] as $table) {
            $this->assertFalse($this->tableExists($table));
        }

        Artisan::call('migrate', ['--database' => 'central', '--path' => database_path('migrations/central'), '--realpath' => true, '--force' => true]);
        foreach (['networks', 'hospitals', 'central_users', 'hospital_memberships'] as $table) {
            $this->assertTrue($this->tableExists($table));
        }
    }

    private function columnType(string $table, string $column): string
    {
        return DB::connection('central')->scalar('SELECT data_type FROM information_schema.columns WHERE table_name = ? AND column_name = ?', [$table, $column]);
    }

    private function tableExists(string $table): bool
    {
        return (bool) DB::connection('central')->scalar('SELECT to_regclass(?) IS NOT NULL', ['public.'.$table]);
    }

    private function assertRejected(Closure $statement): void
    {
        try {
            $statement();
            $this->fail('Expected PostgreSQL to reject the invalid write.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }
    }
}
