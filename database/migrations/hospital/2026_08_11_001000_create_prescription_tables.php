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
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared('DROP TABLE IF EXISTS prescriptions;');
    }
};
