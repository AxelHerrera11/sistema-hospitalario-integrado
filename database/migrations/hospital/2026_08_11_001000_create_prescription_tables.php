<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'hospital';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            CREATE TABLE prescriptions (
                prescription_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                local_patient_id uuid NOT NULL,
                care_episode_id uuid NOT NULL,
                medication_id uuid NOT NULL,
                prescribed_by_doctor_profile_id uuid NOT NULL,
                signed_by_doctor_profile_id uuid,
                dose_amount numeric(10,3) NOT NULL,
                dose_unit text NOT NULL,
                route text NOT NULL,
                frequency_times integer NOT NULL,
                frequency_interval text,
                duration_value integer NOT NULL,
                duration_unit text NOT NULL,
                status text NOT NULL DEFAULT 'draft' CONSTRAINT prescriptions_status_check CHECK (status IN ('draft', 'signed')),
                allergy_check_result text NOT NULL DEFAULT 'clear' CONSTRAINT prescriptions_allergy_check_result_check CHECK (allergy_check_result IN ('clear', 'warning')),
                signed_at timestamptz,
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT prescriptions_dose_amount_positive_check CHECK (dose_amount > 0),
                CONSTRAINT prescriptions_frequency_times_positive_check CHECK (frequency_times > 0),
                CONSTRAINT prescriptions_duration_value_positive_check CHECK (duration_value > 0),
                CONSTRAINT prescriptions_signing_consistency_check CHECK (
                    (status = 'draft' AND signed_at IS NULL AND signed_by_doctor_profile_id IS NULL)
                    OR (status = 'signed' AND signed_at IS NOT NULL AND signed_by_doctor_profile_id IS NOT NULL)
                ),
                CONSTRAINT prescriptions_local_patient_id_fk FOREIGN KEY (local_patient_id) REFERENCES local_patients(local_patient_id) ON DELETE RESTRICT,
                CONSTRAINT prescriptions_care_episode_id_fk FOREIGN KEY (care_episode_id) REFERENCES care_episodes(care_episode_id) ON DELETE RESTRICT,
                CONSTRAINT prescriptions_medication_id_fk FOREIGN KEY (medication_id) REFERENCES medications(medication_id) ON DELETE RESTRICT,
                CONSTRAINT prescriptions_prescribed_by_doctor_profile_id_fk FOREIGN KEY (prescribed_by_doctor_profile_id) REFERENCES doctor_profiles(doctor_profile_id) ON DELETE RESTRICT,
                CONSTRAINT prescriptions_signed_by_doctor_profile_id_fk FOREIGN KEY (signed_by_doctor_profile_id) REFERENCES doctor_profiles(doctor_profile_id) ON DELETE RESTRICT
            );
            CREATE INDEX prescriptions_local_patient_id_index ON prescriptions (local_patient_id);
            CREATE INDEX prescriptions_care_episode_id_index ON prescriptions (care_episode_id);
            CREATE INDEX prescriptions_medication_id_index ON prescriptions (medication_id);
            CREATE INDEX prescriptions_prescribed_by_doctor_profile_id_index ON prescriptions (prescribed_by_doctor_profile_id);
            CREATE INDEX prescriptions_signed_by_doctor_profile_id_index ON prescriptions (signed_by_doctor_profile_id);
            CREATE OR REPLACE FUNCTION prevent_signed_prescription_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF OLD.status = 'signed' THEN
                    RAISE EXCEPTION 'prescriptions_signed_immutable';
                END IF;
                IF TG_OP = 'DELETE' THEN
                    RETURN OLD;
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER prescriptions_signed_immutable
                BEFORE UPDATE OR DELETE ON prescriptions
                FOR EACH ROW EXECUTE FUNCTION prevent_signed_prescription_mutation();
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS prescriptions_signed_immutable ON prescriptions;
            DROP FUNCTION IF EXISTS prevent_signed_prescription_mutation();
            DROP TABLE IF EXISTS prescriptions;
            SQL);
    }
};
