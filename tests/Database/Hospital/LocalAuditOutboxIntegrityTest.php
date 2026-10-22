<?php

namespace Tests\Database\Hospital;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Slice H8: auditoría local, outbox e inbox.
 *
 * Verifica el diseño del Anexo A de auditoría local (publicado en el PR #376 de
 * ASII-11) y las garantias que necesitan los modulos que publican eventos.
 */
#[Group('database-integrity')]
class LocalAuditOutboxIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
        $this->insertGraph();
    }

    public function test_the_slice_creates_the_three_tables_of_the_h8_design(): void
    {
        foreach (['local_access_audits', 'outbox_events', 'inbox_messages'] as $table) {
            $this->assertTrue($this->tableExists($table), $table);
        }
    }

    public function test_audit_event_is_stored_with_actor_module_and_action(): void
    {
        $this->insertAudit();

        $row = DB::connection('hospital')->selectOne(
            "SELECT module, action, resource_type, decision, metadata->>'trace_id' AS trace_id
             FROM local_access_audits WHERE audit_event_id = '00000000-0000-0000-0000-0000000000a1'",
        );

        $this->assertSame('signos_vitales', $row->module);
        $this->assertSame('signo_vital.registrado', $row->action);
        $this->assertSame('vital_sign', $row->resource_type);
        $this->assertSame('allowed', $row->decision);
        $this->assertSame('trace-a1b2c3', $row->trace_id);
    }

    public function test_audit_log_is_append_only(): void
    {
        $this->insertAudit();
        $db = DB::connection('hospital');

        try {
            $db->update("UPDATE local_access_audits SET action = 'signo_vital.editado'");
            $this->fail('La auditoria local no admite UPDATE.');
        } catch (QueryException $error) {
            $this->assertStringContainsString('local_access_audits_append_only_trigger', $error->getMessage());
        }

        try {
            $db->delete('DELETE FROM local_access_audits');
            $this->fail('La auditoria local no admite DELETE.');
        } catch (QueryException $error) {
            $this->assertStringContainsString('local_access_audits_append_only_trigger', $error->getMessage());
        }
    }

    public function test_audit_decision_is_limited_to_allowed_or_denied(): void
    {
        $this->expectException(QueryException::class);
        DB::connection('hospital')->insert(
            "INSERT INTO local_access_audits (audit_event_id, correlation_id, actor_user_ref_id, module, action, resource_type, decision)
             VALUES ('00000000-0000-0000-0000-0000000000a9', '00000000-0000-0000-0000-0000000000b1', '00000000-0000-0000-0000-000000000001', 'signos_vitales', 'signo_vital.registrado', 'vital_sign', 'tal vez')",
        );
    }

    public function test_audit_metadata_must_be_an_object(): void
    {
        $this->expectException(QueryException::class);
        DB::connection('hospital')->insert(
            "INSERT INTO local_access_audits (audit_event_id, correlation_id, actor_user_ref_id, module, action, resource_type, metadata)
             VALUES ('00000000-0000-0000-0000-0000000000a8', '00000000-0000-0000-0000-0000000000b1', '00000000-0000-0000-0000-000000000001', 'signos_vitales', 'signo_vital.registrado', 'vital_sign', '[]')",
        );
    }

    public function test_audit_requires_an_existing_actor(): void
    {
        $this->expectException(QueryException::class);
        DB::connection('hospital')->insert(
            "INSERT INTO local_access_audits (audit_event_id, correlation_id, actor_user_ref_id, module, action, resource_type)
             VALUES ('00000000-0000-0000-0000-0000000000a7', '00000000-0000-0000-0000-0000000000b1', '00000000-0000-0000-0000-0000000000ff', 'signos_vitales', 'signo_vital.registrado', 'vital_sign')",
        );
    }

    public function test_audit_and_outbox_share_the_same_correlation_id_in_one_transaction(): void
    {
        $db = DB::connection('hospital');
        $db->transaction(function (): void {
            $this->insertAudit();
            $this->insertOutbox();
        });

        $correlation = '00000000-0000-0000-0000-0000000000b1';
        $this->assertSame(1, (int) $db->scalar('SELECT count(*) FROM local_access_audits WHERE correlation_id = ?', [$correlation]));
        $this->assertSame(1, (int) $db->scalar('SELECT count(*) FROM outbox_events WHERE correlation_id = ?', [$correlation]));
    }

    public function test_a_failed_transaction_leaves_no_audit_trail(): void
    {
        $db = DB::connection('hospital');

        try {
            $db->transaction(function (): void {
                $this->insertAudit();
                $this->insertOutbox();
                throw new \RuntimeException('fallo simulado');
            });
        } catch (\RuntimeException) {
            // Se espera: la transaccion se revierte completa.
        }

        $this->assertSame(0, (int) $db->scalar('SELECT count(*) FROM local_access_audits'));
        $this->assertSame(0, (int) $db->scalar('SELECT count(*) FROM outbox_events'));
    }

    public function test_outbox_is_idempotent_by_event_id(): void
    {
        $this->insertOutbox();

        $this->expectException(QueryException::class);
        DB::connection('hospital')->insert(
            "INSERT INTO outbox_events (event_id, correlation_id, aggregate_type, event_type)
             VALUES ('00000000-0000-0000-0000-0000000000c1', '00000000-0000-0000-0000-0000000000b1', 'vital_sign', 'signo_vital.registrado')",
        );
    }

    public function test_outbox_payload_cannot_be_changed_after_the_event_is_stored(): void
    {
        $this->insertOutbox();
        $db = DB::connection('hospital');

        $db->update("UPDATE outbox_events SET status = 'published', published_at = now() WHERE event_id = '00000000-0000-0000-0000-0000000000c1'");

        try {
            $db->update("UPDATE outbox_events SET payload_minimal = '{\"cambiado\": true}' WHERE event_id = '00000000-0000-0000-0000-0000000000c1'");
            $this->fail('El payload de un evento publicado no puede cambiar.');
        } catch (QueryException $error) {
            $this->assertStringContainsString('outbox_events_payload_immutable_trigger', $error->getMessage());
        }
    }

    public function test_published_event_requires_publication_timestamp(): void
    {
        $this->insertOutbox();

        $this->expectException(QueryException::class);
        DB::connection('hospital')->update("UPDATE outbox_events SET status = 'published' WHERE event_id = '00000000-0000-0000-0000-0000000000c1'");
    }

    public function test_outbox_cannot_be_deleted(): void
    {
        $this->insertOutbox();

        $this->expectException(QueryException::class);
        DB::connection('hospital')->delete('DELETE FROM outbox_events');
    }

    public function test_inbox_is_idempotent_by_event_id(): void
    {
        $db = DB::connection('hospital');
        $db->insert("INSERT INTO inbox_messages (event_id, event_type) VALUES ('00000000-0000-0000-0000-0000000000d1', 'vital_sign.confirmado')");

        $this->expectException(QueryException::class);
        $db->insert("INSERT INTO inbox_messages (event_id, event_type) VALUES ('00000000-0000-0000-0000-0000000000d1', 'vital_sign.confirmado')");
    }

    public function test_inbox_message_requires_timestamps_according_to_its_state(): void
    {
        $db = DB::connection('hospital');
        $db->insert("INSERT INTO inbox_messages (event_id, event_type) VALUES ('00000000-0000-0000-0000-0000000000d2', 'vital_sign.confirmado')");

        try {
            $db->update("UPDATE inbox_messages SET status = 'processed' WHERE event_id = '00000000-0000-0000-0000-0000000000d2'");
            $this->fail('Un mensaje procesado exige processed_at.');
        } catch (QueryException $error) {
            $this->assertStringContainsString('inbox_messages_processing_check', $error->getMessage());
        }

        $db->update("UPDATE inbox_messages SET status = 'failed', failed_at = now(), attempts = 1, error_code = 'TRIGGER_MISMATCH' WHERE event_id = '00000000-0000-0000-0000-0000000000d2'");
        $this->assertSame('failed', $db->scalar("SELECT status FROM inbox_messages WHERE event_id = '00000000-0000-0000-0000-0000000000d2'"));
    }

    private function insertAudit(): void
    {
        DB::connection('hospital')->insert(
            "INSERT INTO local_access_audits (audit_event_id, correlation_id, actor_user_ref_id, module, action, resource_type, resource_id, care_episode_id, metadata)
             VALUES ('00000000-0000-0000-0000-0000000000a1', '00000000-0000-0000-0000-0000000000b1', '00000000-0000-0000-0000-000000000001', 'signos_vitales', 'signo_vital.registrado', 'vital_sign', '00000000-0000-0000-0000-0000000000e1', '00000000-0000-0000-0000-000000000007', '{\"trace_id\": \"trace-a1b2c3\"}')",
        );
    }

    private function insertOutbox(): void
    {
        DB::connection('hospital')->insert(
            "INSERT INTO outbox_events (event_id, correlation_id, aggregate_type, aggregate_id, event_type, payload_minimal)
             VALUES ('00000000-0000-0000-0000-0000000000c1', '00000000-0000-0000-0000-0000000000b1', 'vital_sign', '00000000-0000-0000-0000-0000000000e1', 'signo_vital.registrado', '{\"vital_sign_id\": \"00000000-0000-0000-0000-0000000000e1\"}')",
        );
    }

    private function insertGraph(): void
    {
        $db = DB::connection('hospital');
        $db->insert("INSERT INTO local_user_refs (local_user_ref_id, central_user_id, membership_id, display_name, role_code, status, synced_at) VALUES ('00000000-0000-0000-0000-000000000001', '00000000-0000-0000-0000-0000000000f1', '00000000-0000-0000-0000-0000000000f2', 'Usuario de prueba', 'medico', 'active', now())");
        $db->insert("INSERT INTO local_patients (local_patient_id, global_id, code, given_name, family_name, birth_date, sex) VALUES ('00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-0000000000f3', 'PAC-H8-001', 'Ana', 'Prueba', '1985-04-12', 'female')");
        $db->insert("INSERT INTO care_episodes (care_episode_id, local_patient_id, type, status, started_at, reason) VALUES ('00000000-0000-0000-0000-000000000007', '00000000-0000-0000-0000-000000000002', 'inpatient', 'open', '2026-10-01 00:00:00+00', 'Episodio de prueba')");
    }

    private function tableExists(string $table): bool
    {
        return DB::connection('hospital')->getSchemaBuilder()->hasTable($table);
    }
}
