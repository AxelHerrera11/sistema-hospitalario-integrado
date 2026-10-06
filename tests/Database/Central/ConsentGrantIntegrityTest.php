<?php

namespace Tests\Database\Central;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('database-integrity')]
class ConsentGrantIntegrityTest extends TestCase
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

    public function test_consents_and_grants_enforce_shapes_dates_and_authorization_evidence(): void
    {
        $db = DB::connection('central');
        $this->assertTrue((bool) $db->scalar("SELECT to_regclass('public.patient_consents') IS NOT NULL"));
        $this->assertTrue((bool) $db->scalar("SELECT to_regclass('public.cross_hospital_access_grants') IS NOT NULL"));
        $this->assertSame('jsonb', $this->columnType('patient_consents', 'data_scope'));
        $this->assertSame('timestamp with time zone', $this->columnType('cross_hospital_access_grants', 'valid_to'));
        $this->seedReferences($db);
        $db->insert("INSERT INTO patient_consents (consent_id, mpi_patient_id, purpose, data_scope, allowed_hospitals, status, valid_from, captured_by, evidence_ref) VALUES ('00000000-0000-0000-0000-000000000007', '00000000-0000-0000-0000-000000000003', 'treatment', '{\"summary\": true}', '[\"00000000-0000-0000-0000-000000000002\"]', 'active', now(), '00000000-0000-0000-0000-000000000004', 'evidence://one')");
        $db->insert("INSERT INTO cross_hospital_access_grants (grant_id, mpi_patient_id, requester_hospital_id, source_hospital_id, requester_user_id, consent_id, grant_type, purpose, data_scope, status, valid_from, valid_to) VALUES ('00000000-0000-0000-0000-000000000008', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000006', '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000004', '00000000-0000-0000-0000-000000000007', 'normal', 'treatment', '{\"summary\": true}', 'active', now(), now() + interval '1 hour')");
        $this->assertRejected(fn () => $db->insert("INSERT INTO patient_consents (consent_id, mpi_patient_id, purpose, data_scope, allowed_hospitals, status, valid_from, captured_by, evidence_ref) VALUES ('00000000-0000-0000-0000-000000000009', '00000000-0000-0000-0000-000000000003', 'treatment', '[]', '[]', 'active', now(), '00000000-0000-0000-0000-000000000004', 'evidence://two')"));
        $this->assertRejected(fn () => $db->insert("INSERT INTO cross_hospital_access_grants (grant_id, mpi_patient_id, requester_hospital_id, source_hospital_id, requester_user_id, grant_type, purpose, data_scope, status, valid_from, valid_to) VALUES ('00000000-0000-0000-0000-000000000010', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000006', '00000000-0000-0000-0000-000000000004', 'normal', 'treatment', '{}', 'active', now(), now() + interval '1 hour')"));
        $this->assertRejected(fn () => $db->insert("INSERT INTO cross_hospital_access_grants (grant_id, mpi_patient_id, requester_hospital_id, source_hospital_id, requester_user_id, grant_type, purpose, data_scope, status, valid_from, valid_to, justification) VALUES ('00000000-0000-0000-0000-000000000011', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000006', '00000000-0000-0000-0000-000000000004', 'break_glass', 'emergency_treatment', '{}', 'active', now(), now() + interval '2 hours', 'urgent')"));
    }

    public function test_normal_grants_require_a_current_matching_and_covering_consent(): void
    {
        $db = DB::connection('central');
        $this->seedReferences($db);
        $db->insert("INSERT INTO mpi_patients (mpi_patient_id, normalized_name, status) VALUES ('00000000-0000-0000-0000-000000000012', 'other patient', 'active')");
        $this->insertConsent($db, '007', '003', 'active', 'now()', 'NULL', "'treatment'", "'{\"summary\": true}'", "'[\"00000000-0000-0000-0000-000000000002\"]'");
        $this->insertNormalGrant($db, '008', '007');

        $this->insertConsent($db, '009', '003', 'active', "now() - interval '2 hours'", "now() - interval '1 hour'", "'treatment'", "'{\"summary\": true}'", "'[\"00000000-0000-0000-0000-000000000002\"]'");
        $this->assertRejected(fn () => $this->insertNormalGrant($db, '010', '009'));
        $this->insertConsent($db, '011', '003', 'revoked', 'now()', 'NULL', "'treatment'", "'{\"summary\": true}'", "'[\"00000000-0000-0000-0000-000000000002\"]'");
        $this->assertRejected(fn () => $this->insertNormalGrant($db, '013', '011'));
        $this->insertConsent($db, '014', '012', 'active', 'now()', 'NULL', "'treatment'", "'{\"summary\": true}'", "'[\"00000000-0000-0000-0000-000000000002\"]'");
        $this->assertRejected(fn () => $this->insertNormalGrant($db, '015', '014'));
        $this->insertConsent($db, '016', '003', 'active', 'now()', 'NULL', "'treatment'", "'{\"summary\": true}'", "'[\"00000000-0000-0000-0000-000000000006\"]'");
        $this->assertRejected(fn () => $this->insertNormalGrant($db, '017', '016'));
        $this->insertConsent($db, '018', '003', 'active', 'now()', 'NULL', "'research'", "'{\"summary\": true}'", "'[\"00000000-0000-0000-0000-000000000002\"]'");
        $this->assertRejected(fn () => $this->insertNormalGrant($db, '019', '018'));
        $this->assertRejected(fn () => $db->insert("INSERT INTO cross_hospital_access_grants (grant_id, mpi_patient_id, requester_hospital_id, source_hospital_id, requester_user_id, consent_id, grant_type, purpose, data_scope, status, valid_from, valid_to) VALUES ('00000000-0000-0000-0000-000000000020', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000006', '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000004', '00000000-0000-0000-0000-000000000007', 'normal', 'treatment', '{\"summary\": true, \"sensitive\": true}', 'active', now(), now() + interval '1 hour')"));
    }

    public function test_break_glass_grant_requires_a_non_null_justification(): void
    {
        $db = DB::connection('central');
        $this->seedReferences($db);

        $this->assertRejected(fn () => $db->insert("INSERT INTO cross_hospital_access_grants (grant_id, mpi_patient_id, requester_hospital_id, source_hospital_id, requester_user_id, grant_type, purpose, data_scope, status, valid_from, valid_to) VALUES ('00000000-0000-0000-0000-000000000031', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000006', '00000000-0000-0000-0000-000000000004', 'break_glass', 'emergency_treatment', '{}', 'active', now(), now() + interval '30 minutes')"));
        $this->assertSame(0, (int) $db->scalar('SELECT count(*) FROM cross_hospital_access_grants'));

        $db->insert("INSERT INTO cross_hospital_access_grants (grant_id, mpi_patient_id, requester_hospital_id, source_hospital_id, requester_user_id, grant_type, purpose, data_scope, status, valid_from, valid_to, justification) VALUES ('00000000-0000-0000-0000-000000000032', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000006', '00000000-0000-0000-0000-000000000004', 'break_glass', 'emergency_treatment', '{}', 'active', now(), now() + interval '30 minutes', 'urgent')");
        $this->assertSame(1, (int) $db->scalar('SELECT count(*) FROM cross_hospital_access_grants'));
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

    private function insertConsent($db, string $id, string $patient, string $status, string $from, string $to, string $purpose, string $scope, string $hospitals): void
    {
        $db->insert("INSERT INTO patient_consents (consent_id, mpi_patient_id, purpose, data_scope, allowed_hospitals, status, valid_from, valid_to, captured_by, evidence_ref) VALUES ('00000000-0000-0000-0000-000000000$id', '00000000-0000-0000-0000-000000000$patient', $purpose, $scope, $hospitals, '$status', $from, $to, '00000000-0000-0000-0000-000000000004', 'evidence://$id')");
    }

    private function insertNormalGrant($db, string $id, string $consent): void
    {
        $db->insert("INSERT INTO cross_hospital_access_grants (grant_id, mpi_patient_id, requester_hospital_id, source_hospital_id, requester_user_id, consent_id, grant_type, purpose, data_scope, status, valid_from, valid_to) VALUES ('00000000-0000-0000-0000-000000000$id', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000006', '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000004', '00000000-0000-0000-0000-000000000$consent', 'normal', 'treatment', '{\"summary\": true}', 'active', now(), now() + interval '1 hour')");
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
