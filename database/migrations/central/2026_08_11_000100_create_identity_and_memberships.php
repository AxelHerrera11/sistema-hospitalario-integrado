<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            CREATE TABLE central_users (
                user_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                subject text NOT NULL,
                email text,
                display_name text NOT NULL,
                status text NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
                last_authenticated_at timestamptz,
                metadata jsonb NOT NULL DEFAULT '{}'::jsonb CHECK (jsonb_typeof(metadata) = 'object'),
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT central_users_subject_unique UNIQUE (subject)
            );
            CREATE UNIQUE INDEX central_users_email_lower_unique ON central_users (lower(email)) WHERE email IS NOT NULL;
            CREATE TABLE hospital_memberships (
                membership_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                user_id uuid NOT NULL REFERENCES central_users(user_id) ON DELETE RESTRICT,
                hospital_id uuid NOT NULL REFERENCES hospitals(hospital_id) ON DELETE RESTRICT,
                role_code text NOT NULL,
                status text NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'revoked')),
                valid_from timestamptz NOT NULL,
                valid_to timestamptz,
                metadata jsonb NOT NULL DEFAULT '{}'::jsonb CHECK (jsonb_typeof(metadata) = 'object'),
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT hospital_memberships_valid_period CHECK (valid_to IS NULL OR valid_to > valid_from)
            );
            CREATE INDEX hospital_memberships_user_id_index ON hospital_memberships (user_id);
            CREATE INDEX hospital_memberships_hospital_id_index ON hospital_memberships (hospital_id);
            CREATE UNIQUE INDEX hospital_memberships_active_open_unique ON hospital_memberships (user_id, hospital_id, role_code) WHERE status = 'active' AND valid_to IS NULL;
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared('DROP TABLE hospital_memberships; DROP TABLE central_users;');
    }
};
