<?php

namespace Tests\Database\Hospital;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('database-integrity')]
class CareIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
    }

    public function test_patient_and_care_inventory_uses_local_foreign_keys_and_logical_central_references(): void
    {
        foreach (['local_patients', 'care_episodes', 'care_assignments'] as $table) {
            $this->assertTrue($this->tableExists($table));
        }

        $this->assertSame('uuid', $this->columnType('local_patients', 'local_patient_id'));
        $this->assertSame('uuid', $this->columnType('local_patients', 'global_id'));
        $this->assertSame('timestamp with time zone', $this->columnType('care_episodes', 'started_at'));
        $this->assertSame('jsonb', $this->columnType('care_assignments', 'scope'));
        $this->assertNull($this->columnType('care_assignments', 'local_patient_id'));
        $this->assertSame('timestamp with time zone', $this->columnType('care_assignments', 'valid_from'));
        $this->assertSame('Logical CENTRAL mpi_patients UUID reference; no physical cross-database foreign key.', $this->columnComment('local_patients', 'global_id'));
        $this->assertSame('Logical CENTRAL cross_hospital_grants UUID reference; no physical cross-database foreign key.', $this->columnComment('care_assignments', 'grant_id'));
        $this->assertSame(0, (int) DB::connection('hospital')->scalar("SELECT count(*) FROM information_schema.table_constraints WHERE table_name IN ('local_patients', 'care_episodes', 'care_assignments') AND constraint_type = 'FOREIGN KEY' AND constraint_name ILIKE '%central%'"));

        foreach ([
            ['care_episodes', 'local_patient_id'],
            ['care_assignments', 'care_episode_id'],
            ['care_assignments', 'doctor_profile_id'],
            ['care_assignments', 'specialty_id'],
            ['care_assignments', 'assigned_by_user_ref_id'],
        ] as [$table, $column]) {
            $this->assertSame(1, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM pg_constraint fk JOIN pg_class relation ON relation.oid = fk.conrelid JOIN pg_attribute attribute ON attribute.attrelid = relation.oid AND attribute.attnum = ANY(fk.conkey) WHERE fk.contype = \'f\' AND relation.relname = ? AND attribute.attname = ? AND EXISTS (SELECT 1 FROM pg_index indexed WHERE indexed.indrelid = relation.oid AND attribute.attnum = ANY(indexed.indkey))', [$table, $column]));
        }
    }

    public function test_care_assignment_rejects_named_status_scope_period_specialty_and_source_violations(): void
    {
        $db = DB::connection('hospital');
        $this->insertValidCareGraph();

        $this->assertRejected('local_patients_status_check', fn () => $db->insert("INSERT INTO local_patients (global_id, code, given_name, family_name, birth_date, sex, status) VALUES (gen_random_uuid(), 'P-021', 'Invalid', 'Patient', '2000-01-01', 'female', 'invalid')"));
        $this->assertRejected('care_episodes_status_check', fn () => $db->insert("INSERT INTO care_episodes (local_patient_id, type, status, started_at, reason) VALUES ('00000000-0000-0000-0000-000000000010', 'outpatient', 'invalid', now(), 'Invalid')"));
        $this->assertRejected('care_assignments_status_check', fn () => $db->insert($this->assignmentSql('023', "'invalid'", "'{\"notes\":true}'", "'manual'", 'NULL')));
        $this->assertRejected('care_assignments_scope_object_check', fn () => $db->insert($this->assignmentSql('024', "'active'", "'{}'", "'manual'", 'NULL')));
        $this->assertRejected('care_assignments_period_check', fn () => $db->insert($this->assignmentSql('025', "'closed'", "'{\"notes\":true}'", "'manual'", 'NULL', 'now()', "'00000000-0000-0000-0000-000000000003'", "now() - interval '1 hour'")));
        $this->assertRejected('care_assignments_source_grant_check', fn () => $db->insert($this->assignmentSql('026', "'active'", "'{\"notes\":true}'", "'cross_hospital_grant'", 'NULL')));
        $this->assertRejected('care_assignments_source_grant_check', fn () => $db->insert($this->assignmentSql('027', "'active'", "'{\"notes\":true}'", "'manual'", "'00000000-0000-0000-0000-000000000901'")));
        $this->assertRejected('care_assignments_specialty_trigger', fn () => $db->insert($this->assignmentSql('028', "'active'", "'{\"notes\":true}'", "'manual'", 'NULL', 'now()', "'00000000-0000-0000-0000-000000000099'")));
        $db->update("UPDATE doctor_specialties SET valid_to = current_date WHERE doctor_specialty_id = '00000000-0000-0000-0000-000000000004'");
        $this->assertRejected('care_assignments_specialty_trigger', fn () => $db->insert($this->assignmentSql('029', "'active'", "'{\"notes\":true}'", "'manual'", 'NULL')));
    }

    public function test_assignment_retention_and_episode_patient_consistency_are_enforced(): void
    {
        $this->insertValidCareGraph();
        $db = DB::connection('hospital');
        $this->assertRejected('care_assignments_delete_protection', fn () => $db->delete("DELETE FROM care_assignments WHERE care_assignment_id = '00000000-0000-0000-0000-000000000012'"));
        $db->insert("INSERT INTO local_patients (local_patient_id, global_id, code, given_name, family_name, birth_date, sex) VALUES ('00000000-0000-0000-0000-000000000013', '00000000-0000-0000-0000-000000000113', 'P-013', 'Other', 'Patient', '2000-01-01', 'female')");
        $this->assertRejected('care_episodes_patient_immutable', fn () => $db->update("UPDATE care_episodes SET local_patient_id = '00000000-0000-0000-0000-000000000013' WHERE care_episode_id = '00000000-0000-0000-0000-000000000011'"));
        $this->assertRejected('care_assignments_evidence_immutable', fn () => $db->update("UPDATE care_assignments SET reason = 'changed' WHERE care_assignment_id = '00000000-0000-0000-0000-000000000012'"));
        $db->update("UPDATE care_assignments SET status = 'closed', valid_to = now() WHERE care_assignment_id = '00000000-0000-0000-0000-000000000012'");

        $this->assertRejected('care_assignments_delete_protection', fn () => $db->delete("DELETE FROM care_assignments WHERE care_assignment_id = '00000000-0000-0000-0000-000000000012'"));
        $this->assertRejected('care_assignments_evidence_immutable', fn () => $db->update("UPDATE care_assignments SET scope = '{\"results\":true}' WHERE care_assignment_id = '00000000-0000-0000-0000-000000000012'"));
    }

    private function insertValidCareGraph(): void
    {
        $db = DB::connection('hospital');
        $db->insert("INSERT INTO local_user_refs (local_user_ref_id, central_user_id, membership_id, display_name, role_code, status, synced_at) VALUES ('00000000-0000-0000-0000-000000000001', '00000000-0000-0000-0000-000000000101', '00000000-0000-0000-0000-000000000201', 'Dr. Ada', 'physician', 'active', now())");
        $db->insert("INSERT INTO doctor_profiles (doctor_profile_id, local_user_ref_id, license_number, status, valid_from) VALUES ('00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000001', 'MED-001', 'active', now())");
        $db->insert("INSERT INTO specialties (specialty_id, code, name, status) VALUES ('00000000-0000-0000-0000-000000000003', 'CARD', 'Cardiology', 'active')");
        $db->insert("INSERT INTO specialties (specialty_id, code, name, status) VALUES ('00000000-0000-0000-0000-000000000099', 'NEUR', 'Neurology', 'active')");
        $db->insert("INSERT INTO doctor_specialties (doctor_specialty_id, doctor_profile_id, specialty_id, is_primary, valid_from, status) VALUES ('00000000-0000-0000-0000-000000000004', '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000003', true, current_date - 1, 'active')");
        $db->insert("INSERT INTO local_patients (local_patient_id, global_id, code, given_name, family_name, birth_date, sex, status, mpi_link_status) VALUES ('00000000-0000-0000-0000-000000000010', '00000000-0000-0000-0000-000000000110', 'P-010', 'Pat', 'Patient', '2000-01-01', 'female', 'active', 'pending')");
        $db->insert("INSERT INTO care_episodes (care_episode_id, local_patient_id, type, status, started_at, reason) VALUES ('00000000-0000-0000-0000-000000000011', '00000000-0000-0000-0000-000000000010', 'outpatient', 'open', now(), 'Treatment')");
        $db->insert($this->assignmentSql('012', "'active'", "'{\"notes\":true}'", "'manual'", 'NULL'));
    }

    private function assignmentSql(string $id, string $status, string $scope, string $source, string $grant, string $from = 'now()', string $specialty = "'00000000-0000-0000-0000-000000000003'", ?string $to = null): string
    {
        return "INSERT INTO care_assignments (care_assignment_id, care_episode_id, doctor_profile_id, specialty_id, assigned_by_user_ref_id, scope, reason, source, grant_id, status, valid_from, valid_to) VALUES ('00000000-0000-0000-0000-000000000{$id}', '00000000-0000-0000-0000-000000000011', '00000000-0000-0000-0000-000000000002', {$specialty}, '00000000-0000-0000-0000-000000000001', {$scope}::jsonb, 'treatment', {$source}, {$grant}, {$status}, {$from}, ".($to ?? ($status === "'closed'" ? 'now()' : 'NULL')).')';
    }

    private function columnType(string $table, string $column): ?string
    {
        return DB::connection('hospital')->scalar('SELECT data_type FROM information_schema.columns WHERE table_name = ? AND column_name = ?', [$table, $column]);
    }

    private function columnComment(string $table, string $column): ?string
    {
        return DB::connection('hospital')->scalar('SELECT col_description(?::regclass, ordinal_position) FROM information_schema.columns WHERE table_name = ? AND column_name = ?', ['public.'.$table, $table, $column]);
    }

    private function tableExists(string $table): bool
    {
        return (bool) DB::connection('hospital')->scalar('SELECT to_regclass(?) IS NOT NULL', ['public.'.$table]);
    }

    private function assertRejected(string $expected, Closure $statement): void
    {
        try {
            $statement();
            $this->fail('Expected PostgreSQL to reject the invalid write.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString($expected, $exception->getMessage());
        }
    }
}
