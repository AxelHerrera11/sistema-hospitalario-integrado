<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'hospital';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            CREATE TABLE medical_records (
                medical_record_id uuid PRIMARY KEY DEFAULT gen_random_uuid(), local_patient_id uuid NOT NULL REFERENCES local_patients(local_patient_id) ON DELETE RESTRICT,
                record_number text NOT NULL, opened_at timestamptz NOT NULL DEFAULT now(), status text NOT NULL DEFAULT 'open' CONSTRAINT medical_records_status_check CHECK (status IN ('open', 'closed')),
                closed_at timestamptz, created_at timestamptz NOT NULL DEFAULT now(), updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT medical_records_local_patient_id_unique UNIQUE (local_patient_id), CONSTRAINT medical_records_record_number_unique UNIQUE (record_number),
                CONSTRAINT medical_records_closure_check CHECK ((status = 'open' AND closed_at IS NULL) OR (status = 'closed' AND closed_at IS NOT NULL AND closed_at >= opened_at))
            );
            CREATE INDEX medical_records_local_patient_id_index ON medical_records (local_patient_id);
            CREATE TABLE clinical_notes (
                clinical_note_id uuid PRIMARY KEY DEFAULT gen_random_uuid(), medical_record_id uuid NOT NULL REFERENCES medical_records(medical_record_id) ON DELETE RESTRICT,
                care_episode_id uuid NOT NULL REFERENCES care_episodes(care_episode_id) ON DELETE RESTRICT, author_user_ref_id uuid NOT NULL REFERENCES local_user_refs(local_user_ref_id) ON DELETE RESTRICT,
                note_type text NOT NULL CONSTRAINT clinical_notes_type_check CHECK (btrim(note_type) <> ''), content text NOT NULL CONSTRAINT clinical_notes_content_check CHECK (btrim(content) <> ''),
                status text NOT NULL DEFAULT 'draft' CONSTRAINT clinical_notes_status_check CHECK (status IN ('draft', 'signed')), signed_at timestamptz, version_no integer NOT NULL CONSTRAINT clinical_notes_version_check CHECK (version_no > 0),
                correction_reason text, supersedes_id uuid REFERENCES clinical_notes(clinical_note_id) ON DELETE RESTRICT, created_at timestamptz NOT NULL DEFAULT now(), updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT clinical_notes_signature_check CHECK ((status = 'draft' AND signed_at IS NULL) OR (status = 'signed' AND signed_at IS NOT NULL)),
                CONSTRAINT clinical_notes_correction_check CHECK ((supersedes_id IS NULL AND correction_reason IS NULL) OR (supersedes_id IS NOT NULL AND correction_reason IS NOT NULL AND btrim(correction_reason) <> '')),
                CONSTRAINT clinical_notes_record_version_unique UNIQUE (medical_record_id, version_no)
            );
            CREATE INDEX clinical_notes_medical_record_id_index ON clinical_notes (medical_record_id); CREATE INDEX clinical_notes_care_episode_id_index ON clinical_notes (care_episode_id); CREATE INDEX clinical_notes_author_user_ref_id_index ON clinical_notes (author_user_ref_id); CREATE INDEX clinical_notes_supersedes_id_index ON clinical_notes (supersedes_id); CREATE UNIQUE INDEX clinical_notes_supersedes_unique ON clinical_notes (supersedes_id) WHERE supersedes_id IS NOT NULL;
            CREATE TABLE diagnoses (
                diagnosis_id uuid PRIMARY KEY DEFAULT gen_random_uuid(), care_episode_id uuid NOT NULL REFERENCES care_episodes(care_episode_id) ON DELETE RESTRICT,
                clinical_note_id uuid REFERENCES clinical_notes(clinical_note_id) ON DELETE RESTRICT, author_user_ref_id uuid NOT NULL REFERENCES local_user_refs(local_user_ref_id) ON DELETE RESTRICT,
                code text NOT NULL CONSTRAINT diagnoses_code_check CHECK (btrim(code) <> ''), description text NOT NULL CONSTRAINT diagnoses_description_check CHECK (btrim(description) <> ''), type text NOT NULL CONSTRAINT diagnoses_type_check CHECK (type IN ('primary', 'secondary', 'differential')),
                status text NOT NULL DEFAULT 'active' CONSTRAINT diagnoses_status_check CHECK (status IN ('active', 'resolved', 'entered_in_error')), onset_date date NOT NULL, resolved_at date, created_at timestamptz NOT NULL DEFAULT now(), updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT diagnoses_resolution_date_check CHECK ((status = 'active' AND resolved_at IS NULL) OR (status IN ('resolved', 'entered_in_error') AND resolved_at IS NOT NULL AND resolved_at >= onset_date))
            );
            CREATE INDEX diagnoses_care_episode_id_index ON diagnoses (care_episode_id); CREATE INDEX diagnoses_clinical_note_id_index ON diagnoses (clinical_note_id); CREATE INDEX diagnoses_author_user_ref_id_index ON diagnoses (author_user_ref_id);
            CREATE OR REPLACE FUNCTION enforce_clinical_note_integrity() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM medical_records r JOIN care_episodes e ON e.local_patient_id = r.local_patient_id WHERE r.medical_record_id = NEW.medical_record_id AND e.care_episode_id = NEW.care_episode_id) THEN RAISE EXCEPTION 'clinical_notes_record_episode_patient_trigger'; END IF;
                IF NOT EXISTS (SELECT 1 FROM doctor_profiles p JOIN care_assignments a ON a.doctor_profile_id = p.doctor_profile_id WHERE p.local_user_ref_id = NEW.author_user_ref_id AND a.care_episode_id = NEW.care_episode_id AND a.status = 'active' AND a.valid_from <= now() AND a.valid_to IS NULL) THEN RAISE EXCEPTION 'clinical_notes_author_assignment_trigger'; END IF;
                IF NEW.supersedes_id = NEW.clinical_note_id THEN RAISE EXCEPTION 'clinical_notes_self_supersedes_trigger'; END IF;
                IF (NEW.supersedes_id IS NULL AND NEW.version_no <> 1) OR (NEW.supersedes_id IS NOT NULL AND NEW.version_no <= 1) THEN RAISE EXCEPTION 'clinical_notes_version_coherence_trigger'; END IF;
                IF NEW.supersedes_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM clinical_notes n WHERE n.clinical_note_id = NEW.supersedes_id AND n.medical_record_id = NEW.medical_record_id AND n.version_no = NEW.version_no - 1) THEN RAISE EXCEPTION 'clinical_notes_supersedes_version_trigger'; END IF;
                IF TG_OP = 'UPDATE' AND OLD.status = 'signed' AND to_jsonb(NEW) IS DISTINCT FROM to_jsonb(OLD) THEN RAISE EXCEPTION 'clinical_notes_signed_immutable'; END IF;
                RETURN NEW;
            END; $$;
            CREATE TRIGGER clinical_notes_integrity BEFORE INSERT OR UPDATE ON clinical_notes FOR EACH ROW EXECUTE FUNCTION enforce_clinical_note_integrity();
            CREATE OR REPLACE FUNCTION preserve_clinical_record() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION 'clinical_notes_delete_protection'; END; $$;
            CREATE TRIGGER medical_records_retention BEFORE DELETE ON medical_records FOR EACH ROW EXECUTE FUNCTION preserve_clinical_record(); CREATE TRIGGER clinical_notes_retention BEFORE DELETE ON clinical_notes FOR EACH ROW EXECUTE FUNCTION preserve_clinical_record(); CREATE TRIGGER diagnoses_retention BEFORE DELETE ON diagnoses FOR EACH ROW EXECUTE FUNCTION preserve_clinical_record();
            CREATE OR REPLACE FUNCTION enforce_diagnosis_integrity() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF NEW.clinical_note_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM clinical_notes n WHERE n.clinical_note_id = NEW.clinical_note_id AND n.care_episode_id = NEW.care_episode_id AND n.author_user_ref_id = NEW.author_user_ref_id) THEN RAISE EXCEPTION 'diagnoses_note_episode_author_trigger'; END IF;
                IF NOT EXISTS (SELECT 1 FROM doctor_profiles p JOIN care_assignments a ON a.doctor_profile_id = p.doctor_profile_id WHERE p.local_user_ref_id = NEW.author_user_ref_id AND a.care_episode_id = NEW.care_episode_id AND a.status = 'active' AND a.valid_from <= now() AND a.valid_to IS NULL) THEN RAISE EXCEPTION 'diagnoses_author_assignment_trigger'; END IF;
                RETURN NEW;
            END; $$;
            CREATE TRIGGER diagnoses_integrity BEFORE INSERT OR UPDATE ON diagnoses FOR EACH ROW EXECUTE FUNCTION enforce_diagnosis_integrity();
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared('DROP TRIGGER diagnoses_integrity ON diagnoses; DROP FUNCTION enforce_diagnosis_integrity(); DROP TRIGGER diagnoses_retention ON diagnoses; DROP TRIGGER clinical_notes_retention ON clinical_notes; DROP TRIGGER medical_records_retention ON medical_records; DROP FUNCTION preserve_clinical_record(); DROP TRIGGER clinical_notes_integrity ON clinical_notes; DROP FUNCTION enforce_clinical_note_integrity(); DROP TABLE diagnoses; DROP TABLE clinical_notes; DROP TABLE medical_records;');
    }
};
