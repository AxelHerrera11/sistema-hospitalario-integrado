<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            CREATE TABLE auth_refresh_tokens (
                refresh_token_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                family_id uuid NOT NULL,
                session_id uuid NOT NULL REFERENCES auth_sessions(session_id) ON DELETE CASCADE,
                user_id uuid NOT NULL REFERENCES central_users(user_id) ON DELETE CASCADE,
                hospital_id uuid NOT NULL REFERENCES hospitals(hospital_id) ON DELETE CASCADE,
                token_hash char(64) NOT NULL UNIQUE CHECK (token_hash ~ '^[a-f0-9]{64}$'),
                issued_at timestamptz NOT NULL DEFAULT now(),
                expires_at timestamptz NOT NULL,
                consumed_at timestamptz,
                revoked_at timestamptz,
                revoked_reason text,
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT auth_refresh_tokens_expiry_check CHECK (expires_at > issued_at)
            );
            CREATE INDEX auth_refresh_tokens_family_index ON auth_refresh_tokens (family_id);
            CREATE INDEX auth_refresh_tokens_scope_index ON auth_refresh_tokens (user_id, hospital_id, session_id);
            CREATE INDEX auth_refresh_tokens_active_expiry_index ON auth_refresh_tokens (expires_at) WHERE revoked_at IS NULL AND consumed_at IS NULL;
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared('DROP TABLE auth_refresh_tokens;');
    }
};
