<?php

namespace Tests\Database\Central;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('database-integrity')]
class CrossAccessAuditImmutabilityTest extends TestCase
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

    public function test_cross_hospital_audit_is_append_only_and_break_glass_reviews_are_local(): void
    {
        $db = DB::connection('central');
        $this->assertTrue((bool) $db->scalar("SELECT to_regclass('public.cross_hospital_access_audits') IS NOT NULL"));
        $this->assertTrue((bool) $db->scalar("SELECT to_regclass('public.break_glass_reviews') IS NOT NULL"));
        $this->assertSame('uuid', $this->columnType('cross_hospital_access_audits', 'correlation_id'));
        $this->assertSame('timestamp with time zone', $this->columnType('cross_hospital_access_audits', 'occurred_at'));
        $this->assertSame(0, $db->scalar("SELECT count(*) FROM information_schema.table_constraints WHERE table_name = 'cross_hospital_access_audits' AND constraint_type = 'FOREIGN KEY' AND constraint_name LIKE '%local_patient%'"));
        $this->seedReferences($db);
        $db->insert("INSERT INTO cross_hospital_access_audits (access_event_id, correlation_id, actor_user_id, mpi_patient_id, requester_hospital_id, source_hospital_id, action, resource_scope, decision, occurred_at, network_metadata) VALUES ('00000000-0000-0000-0000-000000000009', '00000000-0000-0000-0000-000000000008', '00000000-0000-0000-0000-000000000004', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000006', 'read', '{}', 'allowed', now(), '{}')");
        $this->assertRejected(fn () => $db->update("UPDATE cross_hospital_access_audits SET decision = 'denied' WHERE access_event_id = '00000000-0000-0000-0000-000000000009'"));
        $this->assertRejected(fn () => $db->delete("DELETE FROM cross_hospital_access_audits WHERE access_event_id = '00000000-0000-0000-0000-000000000009'"));
    }

    public function test_break_glass_reviews_are_emergency_only_immutable_and_corrected_by_append(): void
    {
        $db = DB::connection('central');
        $this->seedReferences($db);
        $this->insertGrant($db, '007', 'break_glass');
        $this->assertSame(0, $db->scalar("SELECT count(*) FROM break_glass_reviews WHERE grant_id = '00000000-0000-0000-0000-000000000007'"));
        $this->insertGrant($db, '008', 'normal');
        $this->assertRejected(fn () => $this->insertReview($db, '009', '008'));
        $this->insertReview($db, '009', '007');
        $this->assertRejected(fn () => $this->insertReview($db, '010', '007'));
        $this->insertReview($db, '010', '007', '009');
        $this->assertSame(1, $db->scalar("SELECT count(*) FROM break_glass_reviews parent WHERE grant_id = '00000000-0000-0000-0000-000000000007' AND NOT EXISTS (SELECT 1 FROM break_glass_reviews child WHERE child.supersedes_id = parent.review_id)"));
        $this->assertRejected(fn () => $db->update("UPDATE break_glass_reviews SET finding = 'changed' WHERE review_id = '00000000-0000-0000-0000-000000000010'"));
        $this->assertRejected(fn () => $db->delete("DELETE FROM break_glass_reviews WHERE review_id = '00000000-0000-0000-0000-000000000010'"));
    }

    public function test_all_c3_foreign_key_targets_remain_in_the_central_database(): void
    {
        $targets = DB::connection('central')->select("SELECT target_namespace.nspname AS target_schema, target.relname AS target_table FROM pg_constraint fk JOIN pg_class source ON source.oid = fk.conrelid JOIN pg_namespace source_namespace ON source_namespace.oid = source.relnamespace JOIN pg_class target ON target.oid = fk.confrelid JOIN pg_namespace target_namespace ON target_namespace.oid = target.relnamespace WHERE fk.contype = 'f' AND source_namespace.nspname = 'public' AND source.relname IN ('patient_consents', 'cross_hospital_access_grants', 'cross_hospital_access_audits', 'break_glass_reviews')");

        $this->assertNotEmpty($targets);
        foreach ($targets as $target) {
            $this->assertSame('public', $target->target_schema);
            $this->assertNotContains($target->target_table, ['local_patients', 'care_episodes', 'medical_records', 'lab_orders']);
        }
    }

    public function test_reviewed_break_glass_review_requires_a_non_null_finding(): void
    {
        $db = DB::connection('central');
        $this->seedReferences($db);
        $this->insertGrant($db, '007', 'break_glass');

        $this->assertRejected(fn () => $db->insert("INSERT INTO break_glass_reviews (review_id, grant_id, reviewer_id, status, reviewed_at, notification_status) VALUES ('00000000-0000-0000-0000-000000000011', '00000000-0000-0000-0000-000000000007', '00000000-0000-0000-0000-000000000004', 'justified', now(), 'sent')"));
        $this->assertSame(0, (int) $db->scalar('SELECT count(*) FROM break_glass_reviews'));

        $this->insertReview($db, '012', '007');
        $this->assertSame(1, (int) $db->scalar('SELECT count(*) FROM break_glass_reviews'));
    }

    private function seedReferences($db): void
    {
        $db->insert("INSERT INTO networks (network_id, name, status, timezone, metadata) VALUES ('00000000-0000-0000-0000-000000000001', 'Network', 'active', 'America/Guatemala', '{}')");
        foreach ([['002', 'GT-01'], ['006', 'GT-02']] as [$id, $code]) {
            $db->insert("INSERT INTO hospitals (hospital_id, network_id, code, name, status, timezone, metadata) VALUES ('00000000-0000-0000-0000-000000000$id', '00000000-0000-0000-0000-000000000001', '$code', 'Hospital', 'active', 'America/Guatemala', '{}')");
        }
        $db->insert("INSERT INTO central_users (user_id, subject, display_name, status, metadata) VALUES ('00000000-0000-0000-0000-000000000004', 'oidc|one', 'One', 'active', '{}')");
        $db->insert("INSERT INTO mpi_patients (mpi_patient_id, normalized_name, status) VALUES ('00000000-0000-0000-0000-000000000003', 'ana lopez', 'active')");
    }

    private function insertGrant($db, string $id, string $type): void
    {
        $consent = $type === 'normal' ? "'00000000-0000-0000-0000-000000000009'" : 'NULL';
        $justification = $type === 'break_glass' ? "'urgent'" : 'NULL';
        if ($type === 'normal') {
            $db->insert("INSERT INTO patient_consents (consent_id, mpi_patient_id, purpose, data_scope, allowed_hospitals, status, valid_from, captured_by, evidence_ref) VALUES ('00000000-0000-0000-0000-000000000009', '00000000-0000-0000-0000-000000000003', 'treatment', '{\"summary\": true}', '[\"00000000-0000-0000-0000-000000000002\"]', 'active', now(), '00000000-0000-0000-0000-000000000004', 'evidence://normal')");
        }
        $db->insert("INSERT INTO cross_hospital_access_grants (grant_id, mpi_patient_id, requester_hospital_id, source_hospital_id, requester_user_id, consent_id, grant_type, purpose, data_scope, status, valid_from, valid_to, justification) VALUES ('00000000-0000-0000-0000-000000000$id', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000006', '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000004', $consent, '$type', 'treatment', '{\"summary\": true}', 'active', now(), now() + interval '30 minutes', $justification)");
    }

    private function insertReview($db, string $id, string $grant, ?string $supersedes = null): void
    {
        $supersedesValue = $supersedes === null ? 'NULL' : "'00000000-0000-0000-0000-000000000$supersedes'";
        $db->insert("INSERT INTO break_glass_reviews (review_id, grant_id, reviewer_id, status, finding, reviewed_at, notification_status, supersedes_id) VALUES ('00000000-0000-0000-0000-000000000$id', '00000000-0000-0000-0000-000000000$grant', '00000000-0000-0000-0000-000000000004', 'justified', 'review finding', now(), 'sent', $supersedesValue)");
    }

    private function columnType(string $table, string $column): string
    {
        return DB::connection('central')->scalar('SELECT data_type FROM information_schema.columns WHERE table_name = ? AND column_name = ?', [$table, $column]);
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
