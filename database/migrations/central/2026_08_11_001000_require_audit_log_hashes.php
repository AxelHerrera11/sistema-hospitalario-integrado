<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        $connection = DB::connection($this->connection);
        // Block inserts between preflight and constraint installation.
        $connection->statement('LOCK TABLE audit_log IN ACCESS EXCLUSIVE MODE');
        if ($connection->table('audit_log')->whereRaw(
            "previous_hash IS NULL OR event_hash IS NULL OR previous_hash !~ '^[0-9a-f]{64}$' OR event_hash !~ '^[0-9a-f]{64}$'"
        )->exists()) {
            throw new RuntimeException('Invalid audit hashes exist; migration stopped without rewriting immutable events.');
        }
        $connection->unprepared(<<<'SQL'
            ALTER TABLE audit_log
                ALTER COLUMN previous_hash SET NOT NULL,
                ALTER COLUMN event_hash SET NOT NULL,
                ADD CONSTRAINT audit_log_previous_hash_format CHECK (previous_hash ~ '^[0-9a-f]{64}$'),
                ADD CONSTRAINT audit_log_event_hash_format CHECK (event_hash ~ '^[0-9a-f]{64}$');
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            ALTER TABLE audit_log
                DROP CONSTRAINT audit_log_previous_hash_format,
                DROP CONSTRAINT audit_log_event_hash_format,
                ALTER COLUMN previous_hash DROP NOT NULL,
                ALTER COLUMN event_hash DROP NOT NULL;
            SQL);
    }
};
