<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'hospital';
    public function up(): void
    {
        DB::connection('hospital')->unprepared(<<<'SQL'
            CREATE TABLE reception_order_items (
                item_id uuid PRIMARY KEY,
                local_patient_id uuid NOT NULL REFERENCES local_patients(local_patient_id) ON DELETE RESTRICT,
                care_episode_id uuid NOT NULL REFERENCES care_episodes(care_episode_id) ON DELETE RESTRICT,
                patient_code varchar(100) NOT NULL, patient_name varchar(200) NOT NULL,
                type text NOT NULL CHECK (type IN ('blood','urine','swab')),
                container varchar(100) NOT NULL,
                min_volume numeric NOT NULL CHECK (min_volume BETWEEN 0 AND 1000),
                max_minutes integer NOT NULL CHECK (max_minutes BETWEEN 1 AND 10080),
                min_temperature numeric NOT NULL, max_temperature numeric NOT NULL,
                status text NOT NULL CHECK (status IN ('pending','cancelled','pending_new_sample')),
                CHECK (min_temperature >= -80 AND max_temperature <= 100 AND min_temperature <= max_temperature)
            );
            CREATE INDEX reception_orders_patient_index ON reception_order_items(patient_code);
            SQL);
    }
    public function down(): void { DB::connection('hospital')->statement('DROP TABLE reception_order_items'); }
};
