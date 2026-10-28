<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'hospital';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            CREATE TABLE lab_pending_results (
                id uuid NOT NULL, tenant_id uuid NOT NULL, patient_code text NOT NULL,
                test_code text NOT NULL, expected_unit text NOT NULL, status text NOT NULL,
                value text, unit text, observation text, version integer NOT NULL DEFAULT 1,
                PRIMARY KEY (id, tenant_id)
            );
            CREATE TABLE lab_results_for_review (
                id uuid NOT NULL, tenant_id uuid NOT NULL, patient_code text NOT NULL,
                test_code text NOT NULL, value text NOT NULL, unit text NOT NULL,
                expected_unit text NOT NULL, status text NOT NULL, decision_reason text,
                version integer NOT NULL DEFAULT 1, PRIMARY KEY (id, tenant_id)
            );
            CREATE TABLE nativephp_local_actions (
                id text NOT NULL, tenant_id uuid NOT NULL, actor_id text NOT NULL,
                kind text NOT NULL, payload text NOT NULL, status text NOT NULL,
                failure_reason text, created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(), PRIMARY KEY (id)
            );
        SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared(
            'DROP TABLE nativephp_local_actions; DROP TABLE lab_results_for_review; DROP TABLE lab_pending_results;'
        );
    }
};
