<?php

namespace Tests\Database\Central;

use App\Modules\Tenants\Infrastructure\Audit\AuditLogRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Tests\TestCase;

#[Group('database-integrity')]
class AuditChainIntegrityTest extends TestCase
{
    private const HASH_MIGRATION = '2026_08_11_001000_require_audit_log_hashes';

    private const ENRICH_MIGRATION = '2026_08_11_000800_enrich_audit_log';

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('pgsql', DB::connection('central')->getDriverName());
        $this->assertSame(0, Artisan::call('migrate:fresh', [
            '--database' => 'central', '--path' => 'database/migrations/central', '--force' => true,
        ]));
    }

    public function test_missing_or_malformed_hashes_are_rejected(): void
    {
        foreach ([null, 'short', str_repeat('g', 64), str_repeat('A', 64)] as $invalid) {
            foreach (['previous_hash', 'event_hash'] as $column) {
                $row = ['event' => 'test.event', 'actor_id' => 'system', 'hospital_id' => 'unscoped',
                    'detail' => '{}', 'previous_hash' => str_repeat('0', 64), 'event_hash' => str_repeat('a', 64)];
                $row[$column] = $invalid;
                try {
                    DB::connection('central')->table('audit_log')->insert($row);
                    $this->fail('Invalid hash accepted');
                } catch (QueryException $exception) {
                    $this->assertSame($invalid === null ? '23502' : '23514', $exception->errorInfo[0]);
                }
            }
        }
        (new AuditLogRepository)->logRaw('test.genesis', []);
        $this->assertSame(str_repeat('0', 64), DB::connection('central')->table('audit_log')->value('previous_hash'));
    }

    public function test_preflight_preserves_invalid_legacy_events_and_aborts(): void
    {
        $this->rollback(self::HASH_MIGRATION);
        DB::connection('central')->table('audit_log')->insert([
            'event' => 'legacy.event', 'actor_id' => 'system', 'hospital_id' => 'unscoped', 'detail' => '{}',
        ]);
        try {
            DB::connection('central')->transaction(fn () => $this->migration(self::HASH_MIGRATION)->up());
            $this->fail('Preflight must stop');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('without rewriting', $exception->getMessage());
        }
        $this->assertNull(DB::connection('central')->table('audit_log')->value('event_hash'));
        $this->assertSame(1, DB::connection('central')->table('audit_log')->count());
        $this->assertImmutable();
    }

    public function test_partial_rollback_keeps_events_immutable_and_blocks_destructive_enrichment_rollback(): void
    {
        (new AuditLogRepository)->logRaw('test.persist', []);
        $this->rollback(self::HASH_MIGRATION);
        $this->assertImmutable();
        try {
            $this->rollback(self::ENRICH_MIGRATION);
            $this->fail('Rollback must preserve existing audit events');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('existing events', $exception->getMessage());
        }
        $this->assertSame(1, DB::connection('central')->table('audit_log')->count());
        $this->assertImmutable();
        $this->assertSame(0, Artisan::call('migrate', [
            '--database' => 'central', '--path' => 'database/migrations/central', '--force' => true,
        ]));
    }

    public function test_empty_enrichment_rollback_removes_table_and_can_be_reinstalled(): void
    {
        $this->rollback(self::HASH_MIGRATION);
        $this->rollback(self::ENRICH_MIGRATION);
        $this->assertFalse(DB::connection('central')->getSchemaBuilder()->hasTable('audit_log'));
        $this->assertSame(0, Artisan::call('migrate', [
            '--database' => 'central', '--path' => 'database/migrations/central', '--force' => true,
        ]));
        (new AuditLogRepository)->logRaw('test.reinstalled', []);
        $this->assertImmutable();
    }

    public function test_two_postgresql_writers_wait_and_extend_the_same_chain(): void
    {
        $db = DB::connection('central');
        $db->beginTransaction();
        $process = null;
        try {
            (new AuditLogRepository)->logRaw('test.first', ['hospital_id' => 'hospital-a']);
            $first = $db->table('audit_log')->first();
            $name = 'audit-test-'.bin2hex(random_bytes(6));
            $process = proc_open([PHP_BINARY, base_path('tests/Fixtures/audit-chain-writer.php'),
                json_encode(config('database.connections.central')), $name],
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
            $this->assertIsResource($process);
            fclose($pipes[0]);
            $waiting = false;
            $deadline = microtime(true) + 10;
            do {
                $db->select('SELECT pg_stat_clear_snapshot()');
                $waiting = (bool) $db->scalar("SELECT EXISTS (SELECT 1 FROM pg_stat_activity WHERE application_name = ? AND wait_event_type = 'Lock')", [$name]);
                if (! $waiting) {
                    usleep(20000);
                }
            } while (! $waiting && microtime(true) < $deadline);
            $this->assertTrue($waiting, 'Second repository writer must wait for the first transaction');
            $db->commit();
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exit = proc_close($process);
            $process = null;
            $this->assertSame(0, $exit, $error);
            $this->assertSame('written', $output);
            $second = $db->table('audit_log')->where('event', 'test.second')->first();
            $this->assertSame($first->event_hash, $second->previous_hash);
            $this->assertSame($second->event_hash, $db->table('audit_chain_state')->value('head_hash'));
            $this->assertSame(2, $db->table('audit_log')->count());
        } finally {
            if ($db->transactionLevel() > 0) {
                $db->rollBack();
            }
            if (is_resource($process)) {
                proc_terminate($process);
                proc_close($process);
            }
        }
    }

    private function migration(string $name): object
    {
        return require database_path('migrations/central/'.$name.'.php');
    }

    private function rollback(string $name): void
    {
        $repository = app('migrator')->getRepository();
        $repository->setSource('central');
        $this->assertSame(0, Artisan::call('migrate:rollback', [
            '--database' => 'central', '--path' => 'database/migrations/central/'.$name.'.php',
            '--step' => count($repository->getRan()), '--force' => true,
        ]));
    }

    private function assertImmutable(): void
    {
        foreach (["UPDATE audit_log SET event = 'tampered'", 'DELETE FROM audit_log'] as $sql) {
            try {
                DB::connection('central')->statement($sql);
                $this->fail('Mutation accepted');
            } catch (QueryException $exception) {
                $this->assertStringContainsString('audit_log is append-only', $exception->getMessage());
            }
        }
        $this->assertSame(1, (int) DB::connection('central')->scalar(
            "SELECT count(*) FROM pg_constraint WHERE conrelid = 'audit_log'::regclass AND contype = 'p'"
        ));
    }
}
