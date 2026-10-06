<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'hospital';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            CREATE TABLE local_user_refs (
                local_user_ref_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                central_user_id uuid NOT NULL,
                membership_id uuid NOT NULL,
                display_name text NOT NULL,
                role_code text NOT NULL,
                status text NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'revoked')),
                valid_to timestamptz,
                synced_at timestamptz NOT NULL,
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT local_user_refs_central_user_unique UNIQUE (central_user_id),
                CONSTRAINT local_user_refs_membership_unique UNIQUE (membership_id)
            );
            COMMENT ON COLUMN local_user_refs.central_user_id IS 'Logical CENTRAL central_users UUID reference; no physical cross-database foreign key.';
            COMMENT ON COLUMN local_user_refs.membership_id IS 'Logical CENTRAL hospital_memberships UUID reference; no physical cross-database foreign key.';
            CREATE INDEX local_user_refs_status_valid_to_index ON local_user_refs (status, valid_to);

            CREATE TABLE specialties (
                specialty_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                code text NOT NULL,
                name text NOT NULL,
                status text NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT specialties_code_unique UNIQUE (code)
            );

            CREATE TABLE doctor_profiles (
                doctor_profile_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                local_user_ref_id uuid NOT NULL REFERENCES local_user_refs(local_user_ref_id) ON DELETE RESTRICT,
                license_number text NOT NULL,
                status text NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'suspended')),
                valid_from date NOT NULL,
                valid_to date,
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT doctor_profiles_license_number_unique UNIQUE (license_number),
                CONSTRAINT doctor_profiles_valid_period CHECK (valid_to IS NULL OR valid_to > valid_from)
            );
            CREATE INDEX doctor_profiles_local_user_ref_id_index ON doctor_profiles (local_user_ref_id);
            CREATE UNIQUE INDEX doctor_profiles_active_open_user_unique ON doctor_profiles (local_user_ref_id) WHERE status = 'active' AND valid_to IS NULL;

            CREATE TABLE doctor_specialties (
                doctor_specialty_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                doctor_profile_id uuid NOT NULL REFERENCES doctor_profiles(doctor_profile_id) ON DELETE RESTRICT,
                specialty_id uuid NOT NULL REFERENCES specialties(specialty_id) ON DELETE RESTRICT,
                is_primary boolean NOT NULL DEFAULT false,
                valid_from date NOT NULL,
                valid_to date,
                status text NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT doctor_specialties_valid_period CHECK (valid_to IS NULL OR valid_to > valid_from),
                CONSTRAINT doctor_specialties_active_period_exclusion EXCLUDE USING gist (
                    doctor_profile_id WITH =,
                    specialty_id WITH =,
                    daterange(valid_from, COALESCE(valid_to, 'infinity'::date), '[)') WITH &&
                ) WHERE (status = 'active'),
                CONSTRAINT doctor_specialties_primary_period_exclusion EXCLUDE USING gist (
                    doctor_profile_id WITH =,
                    daterange(valid_from, COALESCE(valid_to, 'infinity'::date), '[)') WITH &&
                ) WHERE (status = 'active' AND is_primary)
            );
            CREATE INDEX doctor_specialties_doctor_profile_id_index ON doctor_specialties (doctor_profile_id);
            CREATE INDEX doctor_specialties_specialty_id_index ON doctor_specialties (specialty_id);
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared('DROP TABLE doctor_specialties; DROP TABLE doctor_profiles; DROP TABLE specialties; DROP TABLE local_user_refs;');
    }
};
