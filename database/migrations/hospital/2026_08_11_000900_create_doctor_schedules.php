<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tabla `doctor_schedules` (entidad 28) con la regla de especialidad acordada
 * con ASII-04: trigger de integridad que exige una `doctor_specialties` vigente
 * para el medico, la especialidad y la vigencia del horario.
 */
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

            -- Regla de especialidad acordada con ASII-04 (opcion B): un horario
            -- solo puede existir si el medico tiene vigente esa especialidad en
            -- `doctor_specialties` durante la vigencia del horario. Las dos
            -- vigencias se comparan como intervalos semiabiertos [desde, hasta).
            -- La FK compuesta no es implementable porque `doctor_specialties` no
            -- tiene UNIQUE (doctor_profile_id, specialty_id): el modelo es por
            -- vigencias (SQLSTATE 42830).
            CREATE OR REPLACE FUNCTION doctor_schedules_require_specialty() RETURNS trigger
            LANGUAGE plpgsql AS $$
            BEGIN
                IF EXISTS (SELECT 1 FROM doctor_profiles WHERE doctor_profile_id = NEW.doctor_profile_id)
                   AND EXISTS (SELECT 1 FROM specialties WHERE specialty_id = NEW.specialty_id)
                   AND NOT EXISTS (
                       SELECT 1
                       FROM doctor_specialties s
                       WHERE s.doctor_profile_id = NEW.doctor_profile_id
                         AND s.specialty_id = NEW.specialty_id
                         AND s.status = 'active'
                         AND s.valid_from < COALESCE(NEW.valid_to, 'infinity'::date)
                         AND NEW.valid_from < COALESCE(s.valid_to, 'infinity'::date)
                   )
                THEN
                    RAISE EXCEPTION 'doctor_schedules_specialty_guard: el medico % no tiene vigente la especialidad %',
                        NEW.doctor_profile_id, NEW.specialty_id
                        USING ERRCODE = '23514';
                END IF;

                RETURN NEW;
            END;
            $$;

            CREATE TRIGGER doctor_schedules_specialty_guard
                BEFORE INSERT OR UPDATE ON doctor_schedules
                FOR EACH ROW EXECUTE FUNCTION doctor_schedules_require_specialty();
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS doctor_schedules_specialty_guard ON doctor_schedules;
            DROP FUNCTION IF EXISTS doctor_schedules_require_specialty();
            DROP TABLE doctor_schedules;
            SQL);
    }
};
