<?php

namespace Tests\Database\Hospital;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('database-integrity')]
class MedicalRecordIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
    }

    public function test_pr9a_inventory_uses_uuid_timestamptz_and_indexed_local_foreign_keys(): void
    {
        foreach (['medical_records', 'clinical_notes', 'diagnoses'] as $table) {
            $this->assertTrue($this->tableExists($table));
        }
        $this->assertSame('uuid', $this->columnType('medical_records', 'medical_record_id'));
        $this->assertSame('timestamp with time zone', $this->columnType('medical_records', 'opened_at'));
        $this->assertSame('timestamp with time zone', $this->columnType('clinical_notes', 'signed_at'));
        $this->assertSame(0, (int) DB::connection('hospital')->scalar("SELECT count(*) FROM information_schema.table_constraints WHERE table_name IN ('medical_records', 'clinical_notes', 'diagnoses') AND constraint_type = 'FOREIGN KEY' AND constraint_name ILIKE '%central%'"));

        foreach ([['medical_records', 'local_patient_id'], ['clinical_notes', 'medical_record_id'], ['clinical_notes', 'care_episode_id'], ['clinical_notes', 'author_user_ref_id'], ['clinical_notes', 'supersedes_id'], ['diagnoses', 'care_episode_id'], ['diagnoses', 'clinical_note_id'], ['diagnoses', 'author_user_ref_id']] as [$table, $column]) {
            $this->assertSame(1, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM pg_constraint fk JOIN pg_class relation ON relation.oid = fk.conrelid JOIN pg_attribute attribute ON attribute.attrelid = relation.oid AND attribute.attnum = ANY(fk.conkey) WHERE fk.contype = \'f\' AND relation.relname = ? AND attribute.attname = ? AND EXISTS (SELECT 1 FROM pg_index indexed WHERE indexed.indrelid = relation.oid AND attribute.attnum = ANY(indexed.indkey))', [$table, $column]));
        }
    }

    public function test_records_notes_and_diagnoses_enforce_local_consistency_and_coherence(): void
    {
        $db = DB::connection('hospital');
        $this->insertGraph();
        $db->insert($this->noteSql('020', 1, 'draft'));
        $this->assertRejected('medical_records_local_patient_id_unique', fn () => $db->insert("INSERT INTO medical_records (local_patient_id, record_number, opened_at) VALUES ('00000000-0000-0000-0000-000000000010', 'MR-OTHER', now())"));
        $this->assertRejected('clinical_notes_signature_check', fn () => $db->insert($this->noteSql('021', 1, 'signed')));
        $this->assertRejected('clinical_notes_signature_check', fn () => $db->insert($this->noteSql('022', 1, 'draft', 'now()')));
        $this->assertRejected('clinical_notes_record_episode_patient_trigger', fn () => $db->insert($this->noteSql('023', 2, 'draft', 'NULL', '00000000-0000-0000-0000-000000000019')));
        $this->assertRejected('clinical_notes_author_assignment_trigger', fn () => $db->insert($this->noteSql('024', 2, 'draft', 'NULL', '00000000-0000-0000-0000-000000000011', '00000000-0000-0000-0000-000000000009')));
        $this->assertRejected('diagnoses_note_episode_author_trigger', fn () => $db->insert($this->diagnosisSql('030', '00000000-0000-0000-0000-000000000019')));
        $this->assertRejected('diagnoses_code_check', fn () => $db->insert($this->diagnosisSql('031', '00000000-0000-0000-0000-000000000011', "'   '")));
        $this->assertRejected('diagnoses_resolution_date_check', fn () => $db->insert($this->diagnosisSql('032', '00000000-0000-0000-0000-000000000011', "'A00'", "'resolved'", 'current_date', 'current_date - 1')));
    }

    public function test_signed_notes_are_append_only_and_corrections_create_a_new_linked_version(): void
    {
        $db = DB::connection('hospital');
        $this->insertGraph();
        $db->insert($this->noteSql('020', 1, 'signed', 'now()'));
        $this->assertRejected('clinical_notes_signed_immutable', fn () => $db->update("UPDATE clinical_notes SET content = 'changed' WHERE clinical_note_id = '00000000-0000-0000-0000-000000000020'"));
        $this->assertRejected('clinical_notes_delete_protection', fn () => $db->delete("DELETE FROM clinical_notes WHERE clinical_note_id = '00000000-0000-0000-0000-000000000020'"));
        $db->insert($this->noteSql('021', 2, 'draft', 'NULL', '00000000-0000-0000-0000-000000000011', '00000000-0000-0000-0000-000000000001', "'clarification'", '00000000-0000-0000-0000-000000000020'));
        $this->assertRejected('clinical_notes_supersedes_version_trigger', fn () => $db->insert($this->noteSql('022', 3, 'draft', 'NULL', '00000000-0000-0000-0000-000000000011', '00000000-0000-0000-0000-000000000001', "'duplicate correction'", '00000000-0000-0000-0000-000000000020')));
    }

    public function test_closed_and_terminal_states_require_timestamps_and_nonterminal_states_forbid_them(): void
    {
        $db = DB::connection('hospital');
        $this->insertGraph();
        $db->insert($this->noteSql('020', 1, 'draft'));

        $this->assertRejected('medical_records_closure_check', fn () => $db->insert("INSERT INTO medical_records (local_patient_id, record_number, opened_at, status, closed_at) VALUES ('00000000-0000-0000-0000-000000000018', 'MR-CLOSED', now(), 'closed', NULL)"));
        $this->assertRejected('medical_records_closure_check', fn () => $db->insert("INSERT INTO medical_records (local_patient_id, record_number, opened_at, status, closed_at) VALUES ('00000000-0000-0000-0000-000000000018', 'MR-OPEN', now(), 'open', now())"));
        $this->assertRejected('diagnoses_resolution_date_check', fn () => $db->insert($this->diagnosisSql('033', '00000000-0000-0000-0000-000000000011', "'A00'", "'resolved'", 'current_date', 'NULL')));
        $this->assertRejected('diagnoses_resolution_date_check', fn () => $db->insert($this->diagnosisSql('034', '00000000-0000-0000-0000-000000000011', "'A01'", "'active'", 'current_date', 'current_date')));
    }

    public function test_corrections_require_nonblank_reason_and_noncorrections_keep_null_reason(): void
    {
        $db = DB::connection('hospital');
        $this->insertGraph();
        $db->insert($this->noteSql('020', 1, 'signed', 'now()'));

        $this->assertRejected('clinical_notes_correction_check', fn () => $db->insert($this->noteSql('021', 2, 'draft', 'NULL', '00000000-0000-0000-0000-000000000011', '00000000-0000-0000-0000-000000000001', 'NULL', '00000000-0000-0000-0000-000000000020')));
        $this->assertRejected('clinical_notes_correction_check', fn () => $db->insert($this->noteSql('022', 1, 'draft', 'NULL', '00000000-0000-0000-0000-000000000011', '00000000-0000-0000-0000-000000000001', "'not a correction'")));
    }

    public function test_notes_require_root_version_one_and_linked_incremental_corrections(): void
    {
        $db = DB::connection('hospital');
        $this->insertGraph();

        $this->assertRejected('clinical_notes_version_coherence_trigger', fn () => $db->insert($this->noteSql('020', 2, 'draft')));
        $db->insert($this->noteSql('021', 1, 'signed', 'now()'));
        $this->assertRejected('clinical_notes_version_coherence_trigger', fn () => $db->insert($this->noteSql('022', 1, 'draft', 'NULL', '00000000-0000-0000-0000-000000000011', '00000000-0000-0000-0000-000000000001', "'wrong version'", '00000000-0000-0000-0000-000000000021')));
    }

    public function test_notes_reject_self_supersession_on_insert_and_update(): void
    {
        $db = DB::connection('hospital');
        $this->insertGraph();

        $db->insert($this->noteSql('021', 1, 'draft'));
        $this->assertRejected('clinical_notes_self_supersedes_trigger', fn () => $db->update("UPDATE clinical_notes SET version_no = 2, correction_reason = 'self reference', supersedes_id = clinical_note_id WHERE clinical_note_id = '00000000-0000-0000-0000-000000000021'"));
        $this->assertRejected('clinical_notes_self_supersedes_trigger', fn () => $db->insert($this->noteSql('020', 2, 'draft', 'NULL', '00000000-0000-0000-0000-000000000011', '00000000-0000-0000-0000-000000000001', "'self reference'", '00000000-0000-0000-0000-000000000020')));
    }

    public function test_diagnosis_can_be_recorded_without_a_clinical_note(): void
    {
        $db = DB::connection('hospital');
        $this->insertGraph();

        $db->insert("INSERT INTO diagnoses (diagnosis_id, care_episode_id, clinical_note_id, author_user_ref_id, code, description, type, onset_date) VALUES ('00000000-0000-0000-0000-000000000035', '00000000-0000-0000-0000-000000000011', NULL, '00000000-0000-0000-0000-000000000001', 'A00', 'Cholera', 'primary', current_date)");
        $this->assertSame(1, (int) $db->scalar("SELECT count(*) FROM diagnoses WHERE diagnosis_id = '00000000-0000-0000-0000-000000000035' AND clinical_note_id IS NULL"));
    }

    public function test_pr9b_inventory_uses_canonical_allergy_and_vital_sign_columns_with_indexed_local_foreign_keys(): void
    {
        foreach (['allergies', 'vital_signs'] as $table) {
            $this->assertTrue($this->tableExists($table));
        }
        $this->assertSame('uuid', $this->columnType('allergies', 'allergy_id'));
        $this->assertSame('timestamp with time zone', $this->columnType('allergies', 'recorded_at'));
        $this->assertSame('text', $this->columnType('allergies', 'reaction'));
        $this->assertSame('uuid', $this->columnType('vital_signs', 'vital_sign_id'));
        $this->assertSame('timestamp with time zone', $this->columnType('vital_signs', 'measured_at'));
        $this->assertSame('uuid', $this->columnType('vital_signs', 'supersedes_id'));
        $this->assertSame('integer', $this->columnType('vital_signs', 'version_no'));
        $this->assertSame('text', $this->columnType('vital_signs', 'correction_reason'));

        foreach ([['allergies', 'local_patient_id'], ['allergies', 'verified_by_user_ref_id'], ['vital_signs', 'medical_record_id'], ['vital_signs', 'care_episode_id'], ['vital_signs', 'recorded_by_user_ref_id']] as [$table, $column]) {
            $this->assertSame(1, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM pg_constraint fk JOIN pg_class relation ON relation.oid = fk.conrelid JOIN pg_attribute attribute ON attribute.attrelid = relation.oid AND attribute.attnum = ANY(fk.conkey) WHERE fk.contype = \'f\' AND relation.relname = ? AND attribute.attname = ? AND EXISTS (SELECT 1 FROM pg_index indexed WHERE indexed.indrelid = relation.oid AND attribute.attnum = ANY(indexed.indkey))', [$table, $column]));
        }
    }

    public function test_allergies_enforce_active_equivalence_temporal_history_and_retention(): void
    {
        $db = DB::connection('hospital');
        $this->insertGraph();
        $db->insert($this->allergySql('040'));

        $this->assertRejected('allergies_active_equivalent_unique', fn () => $db->insert($this->allergySql('041', "'PENICILLIN'")));
        $this->assertRejected('allergies_severity_check', fn () => $db->insert($this->allergySql('042', "'penicillin'", "'critical'")));
        $this->assertRejected('allergies_reaction_check', fn () => $db->insert($this->allergySql('045', "'ibuprofen'", "'mild'", "'active'", 'current_date - 1', 'NULL', "'   '")));
        $this->assertRejected('allergies_status_date_check', fn () => $db->insert($this->allergySql('043', "'aspirin'", "'mild'", "'resolved'", 'current_date', 'NULL')));
        $this->assertRejected('allergies_status_date_check', fn () => $db->insert($this->allergySql('044', "'latex'", "'moderate'", "'active'", 'current_date', 'current_date')));
        $this->assertRejected('allergies_delete_protection', fn () => $db->delete("DELETE FROM allergies WHERE allergy_id = '00000000-0000-0000-0000-000000000040'"));
    }

    public function test_vital_signs_enforce_measurement_ranges_consistency_immutability_and_retention(): void
    {
        $db = DB::connection('hospital');
        $this->insertGraph();
        $db->insert($this->vitalSql('050'));

        $this->assertRejected('vital_signs_measurement_check', fn () => $db->insert($this->vitalSql('051', 'NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL')));
        $this->assertRejected('vital_signs_oxygen_saturation_check', fn () => $db->insert($this->vitalSql('052', '36.5, NULL, NULL, NULL, NULL, 101, NULL, NULL, NULL')));
        $this->assertRejected('vital_signs_temperature_check', fn () => $db->insert($this->vitalSql('053', '0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL')));
        $this->assertRejected('vital_signs_blood_pressure_check', fn () => $db->insert($this->vitalSql('054', '36.5, NULL, NULL, 70, 90, NULL, NULL, NULL, NULL')));
        $this->assertRejected('vital_signs_recorded_at_check', fn () => $db->insert($this->vitalSql('055', '36.5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL', "'2026-08-11 11:00+00'", "'2026-08-11 10:00+00'")));
        $this->assertRejected('vital_signs_record_episode_patient_trigger', fn () => $db->insert($this->vitalSql('056', '36.5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL', 'now()', 'now()', '00000000-0000-0000-0000-000000000019')));
        $db->update("UPDATE vital_signs SET status = 'validated', validated_at = now() WHERE vital_sign_id = '00000000-0000-0000-0000-000000000050'");
        $this->assertRejected('vital_signs_validated_immutable', fn () => $db->update("UPDATE vital_signs SET temperature = 37 WHERE vital_sign_id = '00000000-0000-0000-0000-000000000050'"));
        $this->assertRejected('vital_signs_delete_protection', fn () => $db->delete("DELETE FROM vital_signs WHERE vital_sign_id = '00000000-0000-0000-0000-000000000050'"));
    }

    public function test_vital_sign_corrections_append_linked_versions_and_preserve_validated_originals(): void
    {
        $db = DB::connection('hospital');
        $this->insertGraph();
        $db->insert($this->vitalSql('060'));
        $db->update("UPDATE vital_signs SET status = 'validated', validated_at = now() WHERE vital_sign_id = '00000000-0000-0000-0000-000000000060'");

        $this->assertRejected('vital_signs_validated_immutable', fn () => $db->update("UPDATE vital_signs SET temperature = 37 WHERE vital_sign_id = '00000000-0000-0000-0000-000000000060'"));
        $this->assertRejected('vital_signs_correction_check', fn () => $db->insert($this->correctedVitalSql('061', 2, 'NULL', "'00000000-0000-0000-0000-000000000060'")));
        $this->assertRejected('vital_signs_self_supersedes_trigger', fn () => $db->insert($this->correctedVitalSql('062', 2, "'self'", "'00000000-0000-0000-0000-000000000062'")));
        $this->assertRejected('vital_signs_version_coherence_trigger', fn () => $db->insert($this->correctedVitalSql('063', 1, "'root cannot supersede'", "'00000000-0000-0000-0000-000000000060'")));
        $this->assertRejected('vital_signs_supersedes_version_trigger', fn () => $db->insert($this->correctedVitalSql('066', 3, "'wrong predecessor version'", "'00000000-0000-0000-0000-000000000060'")));
        $db->insert($this->correctedVitalSql('064', 2, "'corrected measurement'", "'00000000-0000-0000-0000-000000000060'"));
        $this->assertSame(2, (int) $db->scalar("SELECT version_no FROM vital_signs WHERE vital_sign_id = '00000000-0000-0000-0000-000000000064'"));
        $this->assertRejected('vital_signs_supersedes_unique', fn () => $db->insert($this->correctedVitalSql('065', 2, "'duplicate correction'", "'00000000-0000-0000-0000-000000000060'")));
    }

    public function test_pr9b_rollback_and_reinstall_preserve_pr9a_and_h1_through_h3(): void
    {
        Artisan::call('migrate:rollback', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--step' => 3, '--force' => true]);
        $this->assertFalse($this->tableExists('allergies'));
        $this->assertFalse($this->tableExists('vital_signs'));
        $this->assertTrue($this->tableExists('medical_records'));
        $this->assertTrue($this->tableExists('local_user_refs'));
        $this->assertTrue($this->tableExists('care_episodes'));
        $this->assertTrue($this->tableExists('appointments'));
        Artisan::call('migrate', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
        $this->assertTrue($this->tableExists('medical_records'));
        $this->assertTrue($this->tableExists('clinical_notes'));
        $this->assertTrue($this->tableExists('diagnoses'));
        $this->assertTrue($this->tableExists('allergies'));
        $this->assertTrue($this->tableExists('vital_signs'));
    }

    private function insertGraph(): void
    {
        $db = DB::connection('hospital');
        $db->insert("INSERT INTO local_user_refs (local_user_ref_id, central_user_id, membership_id, display_name, role_code, synced_at) VALUES ('00000000-0000-0000-0000-000000000001', gen_random_uuid(), gen_random_uuid(), 'Dr. Ada', 'physician', now()), ('00000000-0000-0000-0000-000000000009', gen_random_uuid(), gen_random_uuid(), 'Dr. Lin', 'physician', now())");
        $db->insert("INSERT INTO doctor_profiles (doctor_profile_id, local_user_ref_id, license_number, valid_from) VALUES ('00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000001', 'MED-H4', current_date - 1)");
        $db->insert("INSERT INTO specialties (specialty_id, code, name) VALUES ('00000000-0000-0000-0000-000000000003', 'CARD-H4', 'Cardiology')");
        $db->insert("INSERT INTO doctor_specialties (doctor_specialty_id, doctor_profile_id, specialty_id, is_primary, valid_from) VALUES ('00000000-0000-0000-0000-000000000004', '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000003', true, current_date - 1)");
        $db->insert("INSERT INTO local_patients (local_patient_id, global_id, code, given_name, family_name, birth_date, sex) VALUES ('00000000-0000-0000-0000-000000000010', gen_random_uuid(), 'P-H4', 'Pat', 'Patient', '2000-01-01', 'female'), ('00000000-0000-0000-0000-000000000018', gen_random_uuid(), 'P-H4O', 'Other', 'Patient', '2000-01-01', 'female')");
        $db->insert("INSERT INTO care_episodes (care_episode_id, local_patient_id, type, started_at, reason) VALUES ('00000000-0000-0000-0000-000000000011', '00000000-0000-0000-0000-000000000010', 'outpatient', now(), 'Treatment'), ('00000000-0000-0000-0000-000000000019', '00000000-0000-0000-0000-000000000018', 'outpatient', now(), 'Other treatment')");
        $db->insert("INSERT INTO care_assignments (care_assignment_id, care_episode_id, doctor_profile_id, specialty_id, assigned_by_user_ref_id, scope, reason, source, valid_from) VALUES ('00000000-0000-0000-0000-000000000012', '00000000-0000-0000-0000-000000000011', '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000001', '{\"notes\":true}'::jsonb, 'Treatment', 'manual', now())");
        $db->insert("INSERT INTO medical_records (medical_record_id, local_patient_id, record_number, opened_at) VALUES ('00000000-0000-0000-0000-000000000013', '00000000-0000-0000-0000-000000000010', 'MR-H4', now())");
    }

    private function noteSql(string $id, int $version, string $status, string $signedAt = 'NULL', string $episode = '00000000-0000-0000-0000-000000000011', string $author = '00000000-0000-0000-0000-000000000001', string $reason = 'NULL', string $supersedes = 'NULL'): string
    {
        $supersedes = $supersedes === 'NULL' ? 'NULL' : "'{$supersedes}'";

        return "INSERT INTO clinical_notes (clinical_note_id, medical_record_id, care_episode_id, author_user_ref_id, note_type, content, status, signed_at, version_no, correction_reason, supersedes_id) VALUES ('00000000-0000-0000-0000-000000000{$id}', '00000000-0000-0000-0000-000000000013', '{$episode}', '{$author}', 'progress', 'Clinical content', '{$status}', {$signedAt}, {$version}, {$reason}, {$supersedes})";
    }

    private function diagnosisSql(string $id, string $episode, string $code = "'A00'", string $status = "'active'", string $onset = 'current_date', string $resolved = 'NULL'): string
    {
        return "INSERT INTO diagnoses (diagnosis_id, care_episode_id, clinical_note_id, author_user_ref_id, code, description, type, status, onset_date, resolved_at) VALUES ('00000000-0000-0000-0000-000000000{$id}', '{$episode}', '00000000-0000-0000-0000-000000000020', '00000000-0000-0000-0000-000000000001', {$code}, 'Cholera', 'primary', {$status}, {$onset}, {$resolved})";
    }

    private function allergySql(string $id, string $code = "'penicillin'", string $severity = "'severe'", string $status = "'active'", string $onset = 'current_date - 1', string $resolved = 'NULL', string $reaction = 'NULL'): string
    {
        return "INSERT INTO allergies (allergy_id, local_patient_id, verified_by_user_ref_id, allergen_code, allergen_name, reaction, allergy_type, severity, status, onset_date, resolved_at, recorded_at) VALUES ('00000000-0000-0000-0000-000000000{$id}', '00000000-0000-0000-0000-000000000010', '00000000-0000-0000-0000-000000000001', {$code}, NULL, {$reaction}, 'allergy', {$severity}, {$status}, {$onset}, {$resolved}, now())";
    }

    private function vitalSql(string $id, string $measurements = '36.5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL', string $measuredAt = 'now()', string $recordedAt = 'now()', string $episode = '00000000-0000-0000-0000-000000000011'): string
    {
        return "INSERT INTO vital_signs (vital_sign_id, medical_record_id, care_episode_id, recorded_by_user_ref_id, temperature, heart_rate, respiratory_rate, systolic_bp, diastolic_bp, oxygen_saturation, weight, height, glucose, measured_at, recorded_at) VALUES ('00000000-0000-0000-0000-000000000{$id}', '00000000-0000-0000-0000-000000000013', '{$episode}', '00000000-0000-0000-0000-000000000001', {$measurements}, {$measuredAt}, {$recordedAt})";
    }

    private function correctedVitalSql(string $id, int $version, string $reason, string $supersedes): string
    {
        return "INSERT INTO vital_signs (vital_sign_id, medical_record_id, care_episode_id, recorded_by_user_ref_id, temperature, measured_at, recorded_at, version_no, correction_reason, supersedes_id) VALUES ('00000000-0000-0000-0000-000000000{$id}', '00000000-0000-0000-0000-000000000013', '00000000-0000-0000-0000-000000000011', '00000000-0000-0000-0000-000000000001', 37, now(), now(), {$version}, {$reason}, {$supersedes})";
    }

    private function tableExists(string $table): bool
    {
        return (bool) DB::connection('hospital')->scalar('SELECT to_regclass(?) IS NOT NULL', ['public.'.$table]);
    }

    private function columnType(string $table, string $column): ?string
    {
        return DB::connection('hospital')->scalar('SELECT data_type FROM information_schema.columns WHERE table_name = ? AND column_name = ?', [$table, $column]);
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
