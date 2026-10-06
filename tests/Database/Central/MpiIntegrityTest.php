<?php

namespace Tests\Database\Central;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('database-integrity')]
class MpiIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'central', '--path' => database_path('migrations/central'), '--realpath' => true, '--force' => true]);
    }

    public function test_mpi_inventory_uses_uuid_timestamptz_and_logical_hospital_patient_references(): void
    {
        $tables = DB::connection('central')->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename");
        $names = array_map(fn (object $table) => $table->tablename, $tables);

        $this->assertContains('mpi_patients', $names);
        $this->assertContains('mpi_identifiers', $names);
        $this->assertContains('patient_hospital_links', $names);
        $this->assertContains('identity_match_candidates', $names);
        $this->assertSame('uuid', $this->columnType('mpi_patients', 'mpi_patient_id'));
        $this->assertSame('uuid', $this->columnType('mpi_identifiers', 'mpi_identifier_id'));
        $this->assertSame('text', $this->columnType('mpi_identifiers', 'type'));
        $this->assertSame('uuid', $this->columnType('identity_match_candidates', 'candidate_id'));
        $this->assertSame('timestamp with time zone', $this->columnType('patient_hospital_links', 'linked_at'));
        $this->assertSame('uuid', $this->columnType('patient_hospital_links', 'local_patient_global_id'));
        $this->assertSame(0, DB::connection('central')->scalar("SELECT count(*) FROM information_schema.table_constraints WHERE table_name = 'patient_hospital_links' AND constraint_type = 'FOREIGN KEY' AND constraint_name LIKE '%local_patient%'"));
    }

    public function test_mpi_identifier_link_match_and_merge_integrity_are_enforced(): void
    {
        $db = DB::connection('central');
        $db->insert("INSERT INTO networks (network_id, name, status, timezone, metadata) VALUES ('00000000-0000-0000-0000-000000000001', 'Network', 'active', 'America/Guatemala', '{}')");
        $db->insert("INSERT INTO hospitals (hospital_id, network_id, code, name, status, timezone, metadata) VALUES ('00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000001', 'GT-01', 'Hospital', 'active', 'America/Guatemala', '{}')");
        $db->insert("INSERT INTO mpi_patients (mpi_patient_id, normalized_name, birth_date, sex, status) VALUES ('00000000-0000-0000-0000-000000000003', 'ana lopez', '1990-01-01', 'female', 'active')");
        $db->insert("INSERT INTO mpi_identifiers (mpi_identifier_id, mpi_patient_id, type, issuer, value_encrypted, search_hash, verified_at, status) VALUES ('00000000-0000-0000-0000-000000000004', '00000000-0000-0000-0000-000000000003', 'national_id', 'renap', 'ciphertext', 'hash', now(), 'active')");
        $db->insert("INSERT INTO patient_hospital_links (link_id, mpi_patient_id, hospital_id, local_patient_global_id, status, linked_at, confidence) VALUES ('00000000-0000-0000-0000-000000000005', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000006', 'linked', now(), 0.95)");
        $db->insert("INSERT INTO identity_match_candidates (candidate_id, mpi_patient_id, link_id, score, rules, status) VALUES ('00000000-0000-0000-0000-000000000007', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000005', 0.95, '{}', 'pending')");

        $this->assertRejected(fn () => $db->insert("INSERT INTO mpi_identifiers (mpi_identifier_id, mpi_patient_id, type, issuer, value_encrypted, search_hash, status) VALUES ('00000000-0000-0000-0000-000000000008', '00000000-0000-0000-0000-000000000003', 'national_id', 'renap', 'other', 'hash', 'active')"));
        $this->assertRejected(fn () => $db->insert("INSERT INTO patient_hospital_links (link_id, mpi_patient_id, hospital_id, local_patient_global_id, status, linked_at, confidence) VALUES ('00000000-0000-0000-0000-000000000009', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000006', 'linked', now(), 0.5)"));
        $this->assertRejected(fn () => $db->insert("INSERT INTO patient_hospital_links (link_id, mpi_patient_id, hospital_id, local_patient_global_id, status, linked_at, confidence) VALUES ('00000000-0000-0000-0000-000000000010', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000011', 'linked', now(), 1.01)"));
        $this->assertRejected(fn () => $db->insert("INSERT INTO mpi_patients (mpi_patient_id, normalized_name, status, merged_into_id) VALUES ('00000000-0000-0000-0000-000000000012', 'self merge', 'merged', '00000000-0000-0000-0000-000000000012')"));
        $this->assertRejected(fn () => $db->insert("INSERT INTO mpi_patients (mpi_patient_id, normalized_name, status, merged_into_id) VALUES ('00000000-0000-0000-0000-000000000013', 'not merged', 'active', '00000000-0000-0000-0000-000000000003')"));
        $this->assertRejected(fn () => $db->insert("INSERT INTO mpi_patients (mpi_patient_id, normalized_name, status) VALUES ('00000000-0000-0000-0000-000000000014', 'missing merge target', 'merged')"));
        $this->assertRejected(fn () => $db->insert("INSERT INTO patient_hospital_links (link_id, mpi_patient_id, hospital_id, local_patient_global_id, status, linked_at, unlinked_at, confidence) VALUES ('00000000-0000-0000-0000-000000000015', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000016', 'linked', now(), now() + interval '1 minute', 0.5)"));
        $this->assertRejected(fn () => $db->insert("INSERT INTO patient_hospital_links (link_id, mpi_patient_id, hospital_id, local_patient_global_id, status, linked_at, confidence) VALUES ('00000000-0000-0000-0000-000000000017', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000018', 'unlinked', now(), 0.5)"));
        $this->assertRejected(fn () => $db->insert("INSERT INTO identity_match_candidates (candidate_id, mpi_patient_id, link_id, score, rules, status) VALUES ('00000000-0000-0000-0000-000000000019', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000005', -0.01, '{}', 'pending')"));
    }

    public function test_mpi_migration_rolls_back_and_reinstalls_independently(): void
    {
        Artisan::call('migrate:rollback', ['--database' => 'central', '--path' => database_path('migrations/central'), '--realpath' => true, '--force' => true]);
        $this->assertFalse($this->tableExists('mpi_patients'));

        Artisan::call('migrate', ['--database' => 'central', '--path' => database_path('migrations/central'), '--realpath' => true, '--force' => true]);
        $this->assertTrue($this->tableExists('mpi_patients'));
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
