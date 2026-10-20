<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'hospital';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            CREATE TABLE doctor_schedules (
                doctor_schedule_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                doctor_profile_id uuid NOT NULL REFERENCES doctor_profiles(doctor_profile_id) ON DELETE RESTRICT,
                specialty_id uuid NOT NULL REFERENCES specialties(specialty_id) ON DELETE RESTRICT,
                weekday smallint NOT NULL CHECK (weekday BETWEEN 1 AND 7),
                start_minute smallint NOT NULL CHECK (start_minute BETWEEN 0 AND 1439),
                end_minute smallint NOT NULL CHECK (end_minute BETWEEN 1 AND 1440),
                slot_minutes smallint NOT NULL CHECK (slot_minutes BETWEEN 5 AND 240),
                valid_from date NOT NULL,
                valid_to date,
                status text NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT doctor_schedules_block_check CHECK (end_minute > start_minute AND (end_minute - start_minute) % slot_minutes = 0),
                CONSTRAINT doctor_schedules_valid_period CHECK (valid_to IS NULL OR valid_to > valid_from),
                CONSTRAINT doctor_schedules_doctor_block_exclusion EXCLUDE USING gist (
                    doctor_profile_id WITH =, weekday WITH =,
                    int4range(start_minute, end_minute, '[)') WITH &&,
                    daterange(valid_from, COALESCE(valid_to, 'infinity'::date), '[)') WITH &&
                ) WHERE (status = 'active')
            );
            CREATE INDEX doctor_schedules_doctor_profile_id_index ON doctor_schedules (doctor_profile_id);
            CREATE INDEX doctor_schedules_specialty_id_index ON doctor_schedules (specialty_id);
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared('DROP TABLE doctor_schedules;');
    }
};
