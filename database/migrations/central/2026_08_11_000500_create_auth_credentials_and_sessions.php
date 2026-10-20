<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            CREATE TABLE auth_credentials (
                user_id uuid PRIMARY KEY REFERENCES central_users(user_id) ON DELETE CASCADE,
                email_normalized text NOT NULL,
                password_hash text NOT NULL,
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT auth_credentials_email_unique UNIQUE (email_normalized)
            );
            CREATE TABLE auth_sessions (
                session_id uuid PRIMARY KEY,
                user_id uuid NOT NULL REFERENCES central_users(user_id) ON DELETE CASCADE,
                hospital_id uuid NOT NULL REFERENCES hospitals(hospital_id) ON DELETE CASCADE,
                jti text NOT NULL,
                issued_at timestamptz NOT NULL,
                expires_at timestamptz NOT NULL,
                revoked_at timestamptz,
                revoked_reason text,
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT auth_sessions_jti_unique UNIQUE (jti)
            );
            CREATE INDEX auth_sessions_user_hospital_index ON auth_sessions (user_id, hospital_id);
            CREATE INDEX auth_sessions_active_index ON auth_sessions (expires_at) WHERE revoked_at IS NULL;
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared('DROP TABLE auth_sessions; DROP TABLE auth_credentials;');
    }
};
