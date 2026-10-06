<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'hospital';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            CREATE TABLE local_patients (
                local_patient_id uuid PRIMARY KEY DEFAULT gen_random_uuid(), global_id uuid NOT NULL,
                code text NOT NULL, given_name text NOT NULL, family_name text NOT NULL, birth_date date NOT NULL CHECK (birth_date <= current_date),
                sex text NOT NULL CONSTRAINT local_patients_sex_check CHECK (sex IN ('female', 'male', 'other', 'unknown')), contact text,
                status text NOT NULL DEFAULT 'active' CONSTRAINT local_patients_status_check CHECK (status IN ('active', 'inactive', 'deceased')),
                mpi_link_status text NOT NULL DEFAULT 'pending' CONSTRAINT local_patients_mpi_link_status_check CHECK (mpi_link_status IN ('pending', 'linked', 'unlinked')),
                created_at timestamptz NOT NULL DEFAULT now(), updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT local_patients_global_id_unique UNIQUE (global_id), CONSTRAINT local_patients_code_unique UNIQUE (code)
            );
            COMMENT ON COLUMN local_patients.global_id IS 'Logical CENTRAL mpi_patients UUID reference; no physical cross-database foreign key.';
            CREATE TABLE care_episodes (
                care_episode_id uuid PRIMARY KEY DEFAULT gen_random_uuid(), local_patient_id uuid NOT NULL REFERENCES local_patients(local_patient_id) ON DELETE RESTRICT,
                type text NOT NULL CONSTRAINT care_episodes_type_check CHECK (type IN ('outpatient', 'emergency', 'inpatient')),
                status text NOT NULL DEFAULT 'open' CONSTRAINT care_episodes_status_check CHECK (status IN ('open', 'closed', 'cancelled')),
                started_at timestamptz NOT NULL, ended_at timestamptz, reason text NOT NULL CONSTRAINT care_episodes_reason_check CHECK (btrim(reason) <> ''), service_code text,
                created_at timestamptz NOT NULL DEFAULT now(), updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT care_episodes_status_period_check CHECK ((status = 'open' AND ended_at IS NULL) OR (status IN ('closed', 'cancelled') AND ended_at > started_at))
            );
            CREATE INDEX care_episodes_local_patient_id_index ON care_episodes (local_patient_id);
            CREATE TABLE care_assignments (
                care_assignment_id uuid PRIMARY KEY DEFAULT gen_random_uuid(), care_episode_id uuid NOT NULL REFERENCES care_episodes(care_episode_id) ON DELETE RESTRICT,
                doctor_profile_id uuid NOT NULL REFERENCES doctor_profiles(doctor_profile_id) ON DELETE RESTRICT, specialty_id uuid NOT NULL REFERENCES specialties(specialty_id) ON DELETE RESTRICT,
                assigned_by_user_ref_id uuid NOT NULL REFERENCES local_user_refs(local_user_ref_id) ON DELETE RESTRICT, grant_id uuid,
                scope jsonb NOT NULL CONSTRAINT care_assignments_scope_object_check CHECK (jsonb_typeof(scope) = 'object' AND scope <> '{}'::jsonb),
                reason text NOT NULL CONSTRAINT care_assignments_reason_check CHECK (btrim(reason) <> ''),
                source text NOT NULL CONSTRAINT care_assignments_source_check CHECK (source IN ('manual', 'episode', 'appointment', 'emergency', 'cross_hospital_grant')),
                status text NOT NULL DEFAULT 'active' CONSTRAINT care_assignments_status_check CHECK (status IN ('active', 'closed', 'revoked')),
                valid_from timestamptz NOT NULL, valid_to timestamptz, created_at timestamptz NOT NULL DEFAULT now(), updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT care_assignments_source_grant_check CHECK ((source = 'cross_hospital_grant' AND grant_id IS NOT NULL) OR (source <> 'cross_hospital_grant' AND grant_id IS NULL)),
                CONSTRAINT care_assignments_period_check CHECK (status NOT IN ('active', 'closed', 'revoked') OR (status = 'active' AND valid_to IS NULL) OR (status IN ('closed', 'revoked') AND valid_to > valid_from))
            );
            COMMENT ON COLUMN care_assignments.grant_id IS 'Logical CENTRAL cross_hospital_grants UUID reference; no physical cross-database foreign key.';
            CREATE INDEX care_assignments_care_episode_id_index ON care_assignments (care_episode_id);
            CREATE INDEX care_assignments_doctor_profile_id_index ON care_assignments (doctor_profile_id);
            CREATE INDEX care_assignments_specialty_id_index ON care_assignments (specialty_id);
            CREATE INDEX care_assignments_assigned_by_user_ref_id_index ON care_assignments (assigned_by_user_ref_id);
            CREATE UNIQUE INDEX care_assignments_active_equivalent_unique ON care_assignments (care_episode_id, doctor_profile_id, specialty_id, scope) WHERE status = 'active' AND valid_to IS NULL;
            CREATE OR REPLACE FUNCTION enforce_care_assignment_integrity() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM doctor_specialties ds JOIN specialties s USING (specialty_id) WHERE ds.doctor_profile_id = NEW.doctor_profile_id AND ds.specialty_id = NEW.specialty_id AND ds.status = 'active' AND s.status = 'active' AND ds.valid_from <= NEW.valid_from::date AND (ds.valid_to IS NULL OR ds.valid_to > NEW.valid_from::date)) THEN RAISE EXCEPTION 'care_assignments_specialty_trigger'; END IF;
                IF TG_OP = 'UPDATE' AND (to_jsonb(NEW) - ARRAY['status', 'valid_to', 'updated_at']) IS DISTINCT FROM (to_jsonb(OLD) - ARRAY['status', 'valid_to', 'updated_at']) THEN RAISE EXCEPTION 'care_assignments_evidence_immutable'; END IF;
                RETURN NEW;
            END; $$;
            CREATE TRIGGER care_assignments_integrity BEFORE INSERT OR UPDATE ON care_assignments FOR EACH ROW EXECUTE FUNCTION enforce_care_assignment_integrity();
            CREATE OR REPLACE FUNCTION preserve_care_assignment() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION 'care_assignments_delete_protection'; END; $$;
            CREATE TRIGGER care_assignments_retention BEFORE DELETE ON care_assignments FOR EACH ROW EXECUTE FUNCTION preserve_care_assignment();
            CREATE OR REPLACE FUNCTION preserve_care_episode_patient() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN IF NEW.local_patient_id <> OLD.local_patient_id AND EXISTS (SELECT 1 FROM care_assignments WHERE care_episode_id = OLD.care_episode_id) THEN RAISE EXCEPTION 'care_episodes_patient_immutable'; END IF; RETURN NEW; END; $$;
            CREATE TRIGGER care_episodes_patient_retention BEFORE UPDATE ON care_episodes FOR EACH ROW EXECUTE FUNCTION preserve_care_episode_patient();
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared('DROP TRIGGER care_episodes_patient_retention ON care_episodes; DROP FUNCTION preserve_care_episode_patient(); DROP TRIGGER care_assignments_retention ON care_assignments; DROP FUNCTION preserve_care_assignment(); DROP TRIGGER care_assignments_integrity ON care_assignments; DROP FUNCTION enforce_care_assignment_integrity(); DROP TABLE care_assignments; DROP TABLE care_episodes; DROP TABLE local_patients;');
    }
};
