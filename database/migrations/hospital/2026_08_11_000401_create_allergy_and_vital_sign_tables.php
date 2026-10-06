<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'hospital';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            CREATE TABLE allergies (
                allergy_id uuid PRIMARY KEY DEFAULT gen_random_uuid(), local_patient_id uuid NOT NULL REFERENCES local_patients(local_patient_id) ON DELETE RESTRICT, verified_by_user_ref_id uuid NOT NULL REFERENCES local_user_refs(local_user_ref_id) ON DELETE RESTRICT,
                allergen_code text, allergen_name text, reaction text CONSTRAINT allergies_reaction_check CHECK (reaction IS NULL OR btrim(reaction) <> ''), allergy_type text NOT NULL CONSTRAINT allergies_type_check CHECK (allergy_type IN ('allergy', 'intolerance', 'unknown')), severity text NOT NULL CONSTRAINT allergies_severity_check CHECK (severity IN ('mild', 'moderate', 'severe', 'life_threatening', 'unknown')),
                status text NOT NULL DEFAULT 'active' CONSTRAINT allergies_status_check CHECK (status IN ('active', 'inactive', 'resolved')), onset_date date, resolved_at date, recorded_at timestamptz NOT NULL DEFAULT now(), created_at timestamptz NOT NULL DEFAULT now(), updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT allergies_allergen_check CHECK (COALESCE(NULLIF(btrim(allergen_code), ''), NULLIF(btrim(allergen_name), '')) IS NOT NULL),
                CONSTRAINT allergies_status_date_check CHECK ((status = 'active' AND resolved_at IS NULL) OR (status IN ('inactive', 'resolved') AND resolved_at IS NOT NULL AND (onset_date IS NULL OR resolved_at >= onset_date)))
            );
            CREATE INDEX allergies_local_patient_id_index ON allergies (local_patient_id); CREATE INDEX allergies_verified_by_user_ref_id_index ON allergies (verified_by_user_ref_id);
            CREATE UNIQUE INDEX allergies_active_equivalent_unique ON allergies (local_patient_id, allergy_type, lower(COALESCE(NULLIF(btrim(allergen_code), ''), NULLIF(btrim(allergen_name), '')))) WHERE status = 'active';
            CREATE TABLE vital_signs (
                vital_sign_id uuid PRIMARY KEY DEFAULT gen_random_uuid(), medical_record_id uuid NOT NULL REFERENCES medical_records(medical_record_id) ON DELETE RESTRICT, care_episode_id uuid NOT NULL REFERENCES care_episodes(care_episode_id) ON DELETE RESTRICT, recorded_by_user_ref_id uuid NOT NULL REFERENCES local_user_refs(local_user_ref_id) ON DELETE RESTRICT,
                temperature numeric, heart_rate integer, respiratory_rate integer, systolic_bp integer, diastolic_bp integer, oxygen_saturation numeric, weight numeric, height numeric, glucose numeric,
                measured_at timestamptz NOT NULL, recorded_at timestamptz NOT NULL DEFAULT now(), status text NOT NULL DEFAULT 'draft' CONSTRAINT vital_signs_status_check CHECK (status IN ('draft', 'validated', 'entered_in_error')), validated_at timestamptz, version_no integer NOT NULL DEFAULT 1 CONSTRAINT vital_signs_version_check CHECK (version_no > 0), correction_reason text, supersedes_id uuid REFERENCES vital_signs(vital_sign_id) ON DELETE RESTRICT, created_at timestamptz NOT NULL DEFAULT now(), updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT vital_signs_measurement_check CHECK (num_nonnulls(temperature, heart_rate, respiratory_rate, systolic_bp, diastolic_bp, oxygen_saturation, weight, height, glucose) > 0),
                CONSTRAINT vital_signs_temperature_check CHECK (temperature IS NULL OR temperature > 25 AND temperature <= 45), CONSTRAINT vital_signs_heart_rate_check CHECK (heart_rate IS NULL OR heart_rate > 0 AND heart_rate <= 300), CONSTRAINT vital_signs_respiratory_rate_check CHECK (respiratory_rate IS NULL OR respiratory_rate > 0 AND respiratory_rate <= 100), CONSTRAINT vital_signs_systolic_bp_check CHECK (systolic_bp IS NULL OR systolic_bp > 0 AND systolic_bp <= 300), CONSTRAINT vital_signs_diastolic_bp_check CHECK (diastolic_bp IS NULL OR diastolic_bp > 0 AND diastolic_bp <= 200), CONSTRAINT vital_signs_blood_pressure_check CHECK (systolic_bp IS NULL OR diastolic_bp IS NULL OR systolic_bp >= diastolic_bp), CONSTRAINT vital_signs_oxygen_saturation_check CHECK (oxygen_saturation IS NULL OR oxygen_saturation >= 0 AND oxygen_saturation <= 100), CONSTRAINT vital_signs_weight_check CHECK (weight IS NULL OR weight > 0 AND weight <= 1000), CONSTRAINT vital_signs_height_check CHECK (height IS NULL OR height > 0 AND height <= 300), CONSTRAINT vital_signs_glucose_check CHECK (glucose IS NULL OR glucose > 0 AND glucose <= 2000),
                CONSTRAINT vital_signs_recorded_at_check CHECK (recorded_at >= measured_at), CONSTRAINT vital_signs_validation_check CHECK ((status = 'validated' AND validated_at IS NOT NULL AND validated_at >= recorded_at) OR (status IN ('draft', 'entered_in_error') AND validated_at IS NULL)), CONSTRAINT vital_signs_correction_check CHECK ((supersedes_id IS NULL AND correction_reason IS NULL) OR (supersedes_id IS NOT NULL AND correction_reason IS NOT NULL AND btrim(correction_reason) <> ''))
            );
            CREATE INDEX vital_signs_medical_record_id_index ON vital_signs (medical_record_id); CREATE INDEX vital_signs_care_episode_id_index ON vital_signs (care_episode_id); CREATE INDEX vital_signs_recorded_by_user_ref_id_index ON vital_signs (recorded_by_user_ref_id); CREATE INDEX vital_signs_supersedes_id_index ON vital_signs (supersedes_id); CREATE UNIQUE INDEX vital_signs_supersedes_unique ON vital_signs (supersedes_id) WHERE supersedes_id IS NOT NULL;
            CREATE OR REPLACE FUNCTION enforce_vital_sign_integrity() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                 IF NOT EXISTS (SELECT 1 FROM medical_records r JOIN care_episodes e ON e.local_patient_id = r.local_patient_id WHERE r.medical_record_id = NEW.medical_record_id AND e.care_episode_id = NEW.care_episode_id) THEN RAISE EXCEPTION 'vital_signs_record_episode_patient_trigger'; END IF;
                 IF NOT EXISTS (SELECT 1 FROM doctor_profiles p JOIN care_assignments a ON a.doctor_profile_id = p.doctor_profile_id WHERE p.local_user_ref_id = NEW.recorded_by_user_ref_id AND a.care_episode_id = NEW.care_episode_id AND a.status = 'active' AND a.valid_from <= now() AND a.valid_to IS NULL) THEN RAISE EXCEPTION 'vital_signs_author_assignment_trigger'; END IF;
                IF NEW.supersedes_id = NEW.vital_sign_id THEN RAISE EXCEPTION 'vital_signs_self_supersedes_trigger'; END IF;
                IF (NEW.supersedes_id IS NULL AND NEW.version_no <> 1) OR (NEW.supersedes_id IS NOT NULL AND NEW.version_no <= 1) THEN RAISE EXCEPTION 'vital_signs_version_coherence_trigger'; END IF;
                IF NEW.supersedes_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM vital_signs v WHERE v.vital_sign_id = NEW.supersedes_id AND v.medical_record_id = NEW.medical_record_id AND v.care_episode_id = NEW.care_episode_id AND v.version_no = NEW.version_no - 1) THEN RAISE EXCEPTION 'vital_signs_supersedes_version_trigger'; END IF;
                IF TG_OP = 'UPDATE' AND OLD.status = 'validated' AND to_jsonb(NEW) IS DISTINCT FROM to_jsonb(OLD) THEN RAISE EXCEPTION 'vital_signs_validated_immutable'; END IF;
                RETURN NEW;
            END; $$;
            CREATE TRIGGER vital_signs_integrity BEFORE INSERT OR UPDATE ON vital_signs FOR EACH ROW EXECUTE FUNCTION enforce_vital_sign_integrity();
            CREATE OR REPLACE FUNCTION preserve_h4_observation() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION '%_delete_protection', TG_TABLE_NAME; END; $$;
            CREATE TRIGGER allergies_retention BEFORE DELETE ON allergies FOR EACH ROW EXECUTE FUNCTION preserve_h4_observation(); CREATE TRIGGER vital_signs_retention BEFORE DELETE ON vital_signs FOR EACH ROW EXECUTE FUNCTION preserve_h4_observation();
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared('DROP TRIGGER vital_signs_retention ON vital_signs; DROP TRIGGER allergies_retention ON allergies; DROP FUNCTION preserve_h4_observation(); DROP TRIGGER vital_signs_integrity ON vital_signs; DROP FUNCTION enforce_vital_sign_integrity(); DROP TABLE vital_signs; DROP TABLE allergies;');
    }
};
