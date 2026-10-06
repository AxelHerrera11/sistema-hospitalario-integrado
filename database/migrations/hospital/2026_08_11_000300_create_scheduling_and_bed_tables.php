<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'hospital';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            CREATE TABLE appointments (
                appointment_id uuid PRIMARY KEY DEFAULT gen_random_uuid(), local_patient_id uuid NOT NULL REFERENCES local_patients(local_patient_id) ON DELETE RESTRICT,
                doctor_profile_id uuid NOT NULL REFERENCES doctor_profiles(doctor_profile_id) ON DELETE RESTRICT, specialty_id uuid NOT NULL REFERENCES specialties(specialty_id) ON DELETE RESTRICT,
                starts_at timestamptz NOT NULL, ends_at timestamptz NOT NULL, status text NOT NULL DEFAULT 'scheduled' CONSTRAINT appointments_status_check CHECK (status IN ('scheduled', 'confirmed', 'completed', 'cancelled')),
                reason text NOT NULL CONSTRAINT appointments_reason_check CHECK (btrim(reason) <> ''), created_at timestamptz NOT NULL DEFAULT now(), updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT appointments_status_time_check CHECK (ends_at > starts_at AND (status <> 'completed' OR ends_at <= now())),
                CONSTRAINT appointments_doctor_active_period_exclusion EXCLUDE USING gist (doctor_profile_id WITH =, tstzrange(starts_at, ends_at, '[)') WITH &&) WHERE (status IN ('scheduled', 'confirmed')),
                CONSTRAINT appointments_patient_active_period_exclusion EXCLUDE USING gist (local_patient_id WITH =, tstzrange(starts_at, ends_at, '[)') WITH &&) WHERE (status IN ('scheduled', 'confirmed'))
            );
            CREATE INDEX appointments_local_patient_id_index ON appointments (local_patient_id); CREATE INDEX appointments_doctor_profile_id_index ON appointments (doctor_profile_id); CREATE INDEX appointments_specialty_id_index ON appointments (specialty_id);
            CREATE TABLE wards (ward_id uuid PRIMARY KEY DEFAULT gen_random_uuid(), code text NOT NULL, name text NOT NULL, location text NOT NULL, status text NOT NULL DEFAULT 'active' CONSTRAINT wards_status_check CHECK (status IN ('active', 'inactive')), created_at timestamptz NOT NULL DEFAULT now(), updated_at timestamptz NOT NULL DEFAULT now(), CONSTRAINT wards_code_unique UNIQUE (code));
            CREATE TABLE beds (bed_id uuid PRIMARY KEY DEFAULT gen_random_uuid(), ward_id uuid NOT NULL REFERENCES wards(ward_id) ON DELETE RESTRICT, code text NOT NULL, type text NOT NULL, status text NOT NULL DEFAULT 'available' CONSTRAINT beds_status_check CHECK (status IN ('available', 'occupied', 'maintenance', 'inactive')), created_at timestamptz NOT NULL DEFAULT now(), updated_at timestamptz NOT NULL DEFAULT now(), CONSTRAINT beds_ward_code_unique UNIQUE (ward_id, code));
            CREATE INDEX beds_ward_id_index ON beds (ward_id);
            CREATE TABLE bed_stays (bed_stay_id uuid PRIMARY KEY DEFAULT gen_random_uuid(), care_episode_id uuid NOT NULL REFERENCES care_episodes(care_episode_id) ON DELETE RESTRICT, bed_id uuid NOT NULL REFERENCES beds(bed_id) ON DELETE RESTRICT, assigned_by_user_ref_id uuid NOT NULL REFERENCES local_user_refs(local_user_ref_id) ON DELETE RESTRICT,
                started_at timestamptz NOT NULL, ended_at timestamptz, reason text NOT NULL CONSTRAINT bed_stays_reason_check CHECK (btrim(reason) <> ''), created_at timestamptz NOT NULL DEFAULT now(), updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT bed_stays_period_check CHECK (ended_at IS NULL OR ended_at > started_at),
                CONSTRAINT bed_stays_bed_active_period_exclusion EXCLUDE USING gist (bed_id WITH =, tstzrange(started_at, COALESCE(ended_at, 'infinity'::timestamptz), '[)') WITH &&)
            );
            CREATE INDEX bed_stays_care_episode_id_index ON bed_stays (care_episode_id); CREATE INDEX bed_stays_bed_id_index ON bed_stays (bed_id); CREATE INDEX bed_stays_assigned_by_user_ref_id_index ON bed_stays (assigned_by_user_ref_id); CREATE UNIQUE INDEX bed_stays_open_care_episode_unique ON bed_stays (care_episode_id) WHERE ended_at IS NULL;
            CREATE OR REPLACE FUNCTION enforce_appointment_integrity() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN IF NOT EXISTS (SELECT 1 FROM doctor_specialties WHERE doctor_profile_id = NEW.doctor_profile_id AND specialty_id = NEW.specialty_id AND status = 'active' AND valid_from <= NEW.starts_at::date AND (valid_to IS NULL OR valid_to > NEW.starts_at::date)) THEN RAISE EXCEPTION 'appointments_specialty_trigger'; END IF; IF TG_OP = 'UPDATE' AND OLD.status IN ('completed', 'cancelled') AND to_jsonb(NEW) IS DISTINCT FROM to_jsonb(OLD) THEN RAISE EXCEPTION 'appointments_finalized_immutable'; END IF; RETURN NEW; END; $$;
            CREATE TRIGGER appointments_integrity BEFORE INSERT OR UPDATE ON appointments FOR EACH ROW EXECUTE FUNCTION enforce_appointment_integrity();
            CREATE OR REPLACE FUNCTION preserve_appointment() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION 'appointments_delete_protection'; END; $$;
            CREATE TRIGGER appointments_retention BEFORE DELETE ON appointments FOR EACH ROW EXECUTE FUNCTION preserve_appointment();
            CREATE OR REPLACE FUNCTION enforce_bed_stay_integrity() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN IF EXISTS (SELECT 1 FROM care_episodes e WHERE e.care_episode_id = NEW.care_episode_id AND (NEW.started_at < e.started_at OR (e.ended_at IS NOT NULL AND (NEW.ended_at IS NULL OR NEW.ended_at > e.ended_at)))) THEN RAISE EXCEPTION 'bed_stays_episode_period_trigger'; END IF; IF TG_OP = 'UPDATE' AND OLD.ended_at IS NOT NULL AND to_jsonb(NEW) IS DISTINCT FROM to_jsonb(OLD) THEN RAISE EXCEPTION 'bed_stays_finalized_immutable'; END IF; RETURN NEW; END; $$;
            CREATE TRIGGER bed_stays_integrity BEFORE INSERT OR UPDATE ON bed_stays FOR EACH ROW EXECUTE FUNCTION enforce_bed_stay_integrity();
            CREATE OR REPLACE FUNCTION preserve_bed_stay() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION 'bed_stays_delete_protection'; END; $$;
            CREATE TRIGGER bed_stays_retention BEFORE DELETE ON bed_stays FOR EACH ROW EXECUTE FUNCTION preserve_bed_stay();
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared('DROP TRIGGER bed_stays_retention ON bed_stays; DROP FUNCTION preserve_bed_stay(); DROP TRIGGER bed_stays_integrity ON bed_stays; DROP FUNCTION enforce_bed_stay_integrity(); DROP TRIGGER appointments_retention ON appointments; DROP FUNCTION preserve_appointment(); DROP TRIGGER appointments_integrity ON appointments; DROP FUNCTION enforce_appointment_integrity(); DROP TABLE bed_stays; DROP TABLE beds; DROP TABLE wards; DROP TABLE appointments;');
    }
};
