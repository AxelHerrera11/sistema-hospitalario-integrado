<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            CREATE TABLE audit_log (
                event text NOT NULL,
                actor_id text NOT NULL,
                hospital_id text NOT NULL,
                detail jsonb NOT NULL,
                trace_id text
            );
            CREATE INDEX audit_log_actor_index ON audit_log (actor_id);
            CREATE INDEX audit_log_hospital_index ON audit_log (hospital_id);
            CREATE INDEX audit_log_trace_index ON audit_log (trace_id);
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared('DROP TABLE IF EXISTS audit_log;');
    }
};
