<?php

namespace Tests\Database\Central;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Correctivo de consulta de `audit_log` (CENTRAL): indices compuestos y
 * restriccion de valores de `result`.
 */
#[Group('database-integrity')]
class AuditLogQueryIndexTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'central', '--path' => database_path('migrations/central'), '--realpath' => true, '--force' => true]);
    }

    public function test_the_query_indexes_of_the_governance_consults_exist(): void
    {
        $expected = [
            'audit_log_hospital_occurred_index',
            'audit_log_hospital_actor_occurred_index',
            'audit_log_hospital_event_occurred_index',
            'audit_log_hospital_result_occurred_index',
        ];

        $indexes = DB::connection('central')->select(
            "SELECT indexname FROM pg_indexes WHERE schemaname = 'public' AND tablename = 'audit_log'",
        );
        $names = array_column($indexes, 'indexname');

        foreach ($expected as $index) {
            $this->assertContains($index, $names, $index);
        }
        $this->assertContains('audit_log_trace_index', $names, 'El filtro por traza se conserva.');
    }

    public function test_the_tenant_index_serves_the_paginated_consult(): void
    {
        $columns = DB::connection('central')->select(
            "SELECT a.attname AS column_name
             FROM pg_index i
             CROSS JOIN LATERAL unnest(i.indkey) WITH ORDINALITY AS k(attnum, position)
             JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = k.attnum
             WHERE i.indexrelid = 'audit_log_hospital_occurred_index'::regclass
             ORDER BY k.position",
        );

        $this->assertSame(['hospital_id', 'occurred_at', 'event_id'], array_column($columns, 'column_name'));
    }

    public function test_the_result_constraint_rejects_values_outside_the_catalogue(): void
    {
        $this->expectException(QueryException::class);
        $this->insertAudit('suceso', 'ok');
    }

    public function test_the_result_constraint_accepts_the_catalogue_and_null(): void
    {
        foreach (['success', 'failure', 'denied', 'already_revoked', null] as $result) {
            $this->insertAudit('evento.'.$result, $result);
        }

        $this->assertSame(5, (int) DB::connection('central')->scalar('SELECT count(*) FROM audit_log'));
    }

    public function test_the_result_constraint_accepts_the_value_written_by_the_logout(): void
    {
        // AuthController::logout() escribe 'already_revoked' cuando la sesion
        // ya estaba revocada; si la restriccion lo rechaza, el cierre de
        // sesion responde 500 con SQLSTATE 23514.
        $this->insertAudit('auth.logout', 'already_revoked');

        $this->assertSame(
            'already_revoked',
            DB::connection('central')->scalar("SELECT result FROM audit_log WHERE event = 'auth.logout'"),
        );
    }

    public function test_the_result_constraint_is_validated_when_the_data_allows_it(): void
    {
        $validated = DB::connection('central')->scalar(
            "SELECT convalidated FROM pg_constraint WHERE conrelid = 'audit_log'::regclass AND conname = 'audit_log_result_check'",
        );

        $this->assertTrue((bool) $validated, 'La restriccion debe quedar validada con datos conformes.');
    }

    public function test_the_rollback_removes_the_indexes_and_the_constraint(): void
    {
        Artisan::call('migrate:rollback', [
            '--database' => 'central', '--path' => database_path('migrations/central'),
            '--realpath' => true, '--step' => 1, '--force' => true,
        ]);

        $db = DB::connection('central');
        $indexes = array_column($db->select(
            "SELECT indexname FROM pg_indexes WHERE schemaname = 'public' AND tablename = 'audit_log'",
        ), 'indexname');

        $this->assertNotContains('audit_log_hospital_occurred_index', $indexes);
        $this->assertContains('audit_log_trace_index', $indexes);
        $this->assertSame(0, (int) $db->scalar(
            "SELECT count(*) FROM pg_constraint WHERE conrelid = 'audit_log'::regclass AND conname = 'audit_log_result_check'",
        ));
    }

    private function insertAudit(string $event, ?string $result): void
    {
        DB::connection('central')->insert(
            "INSERT INTO audit_log (event, actor_id, hospital_id, detail, result)
             VALUES (?, 'actor-1', 'hospital-1', '{}'::jsonb, ?)",
            [$event, $result],
        );
    }
}
