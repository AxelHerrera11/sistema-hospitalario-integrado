<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'hospital';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            CREATE TABLE specimens (
                specimen_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                lab_order_item_id uuid NOT NULL,
                local_patient_id uuid NOT NULL REFERENCES local_patients(local_patient_id) ON DELETE RESTRICT,
                care_episode_id uuid NOT NULL REFERENCES care_episodes(care_episode_id) ON DELETE RESTRICT,
                received_by_user_ref_id uuid NOT NULL REFERENCES local_user_refs(local_user_ref_id) ON DELETE RESTRICT,
                request_key uuid NOT NULL CONSTRAINT specimens_request_key_unique UNIQUE,
                request_hash text NOT NULL CHECK (request_hash ~ '^[a-f0-9]{64}$'),
                volume numeric(10,3) NOT NULL CONSTRAINT specimens_volume_check CHECK (volume BETWEEN 0 AND 1000),
                temperature numeric(6,2) NOT NULL CONSTRAINT specimens_temperature_check CHECK (temperature BETWEEN -80 AND 100),
                status text NOT NULL CONSTRAINT specimens_status_check CHECK (status IN ('accepted','rejected')),
                reason text,
                code text CONSTRAINT specimens_code_unique UNIQUE CHECK (code ~ '^[A-F0-9]{24}-[0-9]$'),
                received_at timestamptz NOT NULL DEFAULT now(),
                created_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT specimens_state_check CHECK (
                    (status = 'accepted' AND code IS NOT NULL AND reason IS NULL)
                    OR (status = 'rejected' AND reason IS NOT NULL AND btrim(reason) <> '')
                )
            );
            COMMENT ON TABLE specimens IS 'ASII-18: muestras locales; aislamiento por base HOSPITAL.';
            COMMENT ON COLUMN specimens.lab_order_item_id IS 'FK a lab_order_items cuando ASII-16 publique 000600';
            CREATE INDEX specimens_lab_order_item_id_index ON specimens (lab_order_item_id);
            CREATE INDEX specimens_local_patient_id_index ON specimens (local_patient_id);
            CREATE INDEX specimens_care_episode_id_index ON specimens (care_episode_id);
            CREATE INDEX specimens_received_by_user_ref_id_index ON specimens (received_by_user_ref_id);
            CREATE UNIQUE INDEX specimens_one_accepted ON specimens (lab_order_item_id) WHERE status = 'accepted';
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared('DROP TABLE specimens;');
    }
};
