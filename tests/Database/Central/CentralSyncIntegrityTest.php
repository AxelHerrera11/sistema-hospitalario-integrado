<?php

namespace Tests\Database\Central;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('database-integrity')]
class CentralSyncIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'central', '--path' => database_path('migrations/central'), '--realpath' => true, '--force' => true]);
    }

    public function test_sync_inventory_uses_canonical_uuid_timestamptz_and_local_references(): void
    {
        $db = DB::connection('central');
        foreach (['sync_nodes', 'sync_checkpoints', 'central_inbox_messages'] as $table) {
            $this->assertTrue($this->tableExists($table));
        }
        $this->assertSame('uuid', $this->columnType('sync_nodes', 'node_id'));
        $this->assertSame('timestamp with time zone', $this->columnType('sync_nodes', 'last_seen_at'));
        $this->assertSame('timestamp with time zone', $this->columnType('sync_checkpoints', 'last_occurred_at'));
        $this->assertSame('jsonb', $this->columnType('central_inbox_messages', 'payload_minimal'));
        $this->assertSame(0, $db->scalar("SELECT count(*) FROM information_schema.table_constraints WHERE table_name IN ('sync_nodes', 'sync_checkpoints', 'central_inbox_messages') AND constraint_type = 'FOREIGN KEY' AND constraint_name LIKE '%local_patient%'"));
    }

    public function test_sync_idempotency_payload_counters_and_state_times_are_enforced(): void
    {
        $db = DB::connection('central');
        $db->insert("INSERT INTO networks (network_id, name, status, timezone, metadata) VALUES ('00000000-0000-0000-0000-000000000001', 'Network', 'active', 'America/Guatemala', '{}')");
        $db->insert("INSERT INTO hospitals (hospital_id, network_id, code, name, status, timezone, metadata) VALUES ('00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000001', 'GT-01', 'Hospital', 'active', 'America/Guatemala', '{}')");
        $db->insert("INSERT INTO sync_nodes (node_id, hospital_id, name, status, schema_version, key_version, metadata) VALUES ('00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000002', 'gt-01', 'active', '1.0.0', '1', '{}')");
        $db->insert("INSERT INTO sync_checkpoints (checkpoint_id, node_id, channel, last_event_id, last_occurred_at, lag_seconds, status) VALUES ('00000000-0000-0000-0000-000000000004', '00000000-0000-0000-0000-000000000003', 'mpi', '00000000-0000-0000-0000-000000000005', now(), 0, 'healthy')");
        $db->insert("INSERT INTO central_inbox_messages (message_id, source_node_id, event_id, event_type, payload_minimal, metadata, received_at, available_at, status, attempts) VALUES ('00000000-0000-0000-0000-000000000006', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000007', 'patient.registered', '{}', '{}', now(), now(), 'pending', 0)");

        $this->assertRejected(fn () => $db->insert("INSERT INTO sync_nodes (node_id, hospital_id, name, status, schema_version, key_version, metadata) VALUES ('00000000-0000-0000-0000-000000000008', '00000000-0000-0000-0000-000000000002', 'other', 'active', '1.0.0', '1', '[]')"));
        $this->assertRejected(fn () => $db->insert("INSERT INTO sync_checkpoints (checkpoint_id, node_id, channel, lag_seconds, status) VALUES ('00000000-0000-0000-0000-000000000009', '00000000-0000-0000-0000-000000000003', 'mpi', -1, 'healthy')"));
        $this->assertRejected(fn () => $db->insert("INSERT INTO central_inbox_messages (message_id, source_node_id, event_id, event_type, payload_minimal, metadata, received_at, available_at, status, attempts) VALUES ('00000000-0000-0000-0000-000000000010', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000007', 'patient.registered', '{}', '{}', now(), now(), 'pending', 0)"));
        $this->assertRejected(fn () => $db->insert("INSERT INTO central_inbox_messages (message_id, source_node_id, event_id, event_type, payload_minimal, metadata, received_at, available_at, status, attempts) VALUES ('00000000-0000-0000-0000-000000000011', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000012', 'patient.registered', '[]', '{}', now(), now(), 'pending', -1)"));
        $this->assertRejected(fn () => $db->insert("INSERT INTO central_inbox_messages (message_id, source_node_id, event_id, event_type, payload_minimal, metadata, received_at, available_at, status, attempts) VALUES ('00000000-0000-0000-0000-000000000013', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000014', 'patient.registered', '{}', '{}', now(), now(), 'processed', 1)"));
        $this->assertRejected(fn () => $db->insert("INSERT INTO central_inbox_messages (message_id, source_node_id, event_id, event_type, payload_minimal, metadata, received_at, available_at, failed_at, status, attempts, error_code) VALUES ('00000000-0000-0000-0000-000000000015', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000016', 'patient.registered', '{}', '{}', now(), now(), now(), 'pending', 1, 'ERR')"));
    }

    public function test_processed_inbox_message_cannot_precede_available_at(): void
    {
        $db = DB::connection('central');
        $this->insertInboxPrerequisites($db);

        $this->assertRejected(fn () => $db->insert("INSERT INTO central_inbox_messages (message_id, source_node_id, event_id, event_type, payload_minimal, metadata, received_at, available_at, processed_at, status, attempts) VALUES ('00000000-0000-0000-0000-000000000021', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000022', 'patient.registered', '{}', '{}', now(), now() + interval '1 minute', now(), 'processed', 1)"));
    }

    public function test_failed_inbox_message_cannot_precede_available_at(): void
    {
        $db = DB::connection('central');
        $this->insertInboxPrerequisites($db);

        $this->assertRejected(fn () => $db->insert("INSERT INTO central_inbox_messages (message_id, source_node_id, event_id, event_type, payload_minimal, metadata, received_at, available_at, failed_at, status, attempts, error_code) VALUES ('00000000-0000-0000-0000-000000000023', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000024', 'patient.registered', '{}', '{}', now(), now() + interval '1 minute', now(), 'failed', 1, 'ERR')"));
    }

    public function test_failed_inbox_message_requires_a_non_null_error_code(): void
    {
        $db = DB::connection('central');
        $this->insertInboxPrerequisites($db);

        $this->assertRejected(fn () => $db->insert("INSERT INTO central_inbox_messages (message_id, source_node_id, event_id, event_type, payload_minimal, metadata, received_at, available_at, failed_at, status, attempts) VALUES ('00000000-0000-0000-0000-000000000025', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000026', 'patient.registered', '{}', '{}', now(), now(), now(), 'failed', 1)"));
        $this->assertSame(0, (int) $db->scalar('SELECT count(*) FROM central_inbox_messages'));

        $db->insert("INSERT INTO central_inbox_messages (message_id, source_node_id, event_id, event_type, payload_minimal, metadata, received_at, available_at, failed_at, status, attempts, error_code) VALUES ('00000000-0000-0000-0000-000000000027', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000028', 'patient.registered', '{}', '{}', now(), now(), now(), 'failed', 1, 'ERR')");
        $this->assertSame(1, (int) $db->scalar('SELECT count(*) FROM central_inbox_messages'));
    }

    public function test_sync_migration_rolls_back_and_reinstalls_without_removing_c1_to_c3(): void
    {
        $migration = '2026_08_11_000400_create_central_sync_tables';
        $repository = app('migrator')->getRepository();
        $repository->setSource('central');
        $otherMigrations = array_values(array_diff($repository->getRan(), [$migration]));
        // Scan all applied migrations, but allow rollback only for the sync file.
        $this->assertSame(0, Artisan::call('migrate:rollback', [
            '--database' => 'central',
            '--path' => database_path('migrations/central/'.$migration.'.php'),
            '--realpath' => true, '--step' => count($repository->getRan()), '--force' => true,
        ]));
        $this->assertSame($otherMigrations, $repository->getRan());
        $this->assertFalse($this->tableExists('sync_nodes'));
        $this->assertTrue($this->tableExists('patient_consents'));
        Artisan::call('migrate', ['--database' => 'central', '--path' => database_path('migrations/central'), '--realpath' => true, '--force' => true]);
        $this->assertTrue($this->tableExists('central_inbox_messages'));
    }

    private function columnType(string $table, string $column): string
    {
        return DB::connection('central')->scalar('SELECT data_type FROM information_schema.columns WHERE table_name = ? AND column_name = ?', [$table, $column]);
    }

    private function tableExists(string $table): bool
    {
        return (bool) DB::connection('central')->scalar('SELECT to_regclass(?) IS NOT NULL', ['public.'.$table]);
    }

    private function insertInboxPrerequisites($db): void
    {
        $db->insert("INSERT INTO networks (network_id, name, status, timezone, metadata) VALUES ('00000000-0000-0000-0000-000000000001', 'Network', 'active', 'America/Guatemala', '{}')");
        $db->insert("INSERT INTO hospitals (hospital_id, network_id, code, name, status, timezone, metadata) VALUES ('00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000001', 'GT-01', 'Hospital', 'active', 'America/Guatemala', '{}')");
        $db->insert("INSERT INTO sync_nodes (node_id, hospital_id, name, status, schema_version, key_version, metadata) VALUES ('00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000002', 'gt-01', 'active', '1.0.0', '1', '{}')");
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
