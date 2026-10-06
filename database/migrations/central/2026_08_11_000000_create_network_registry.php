<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            CREATE TABLE networks (
                network_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                name text NOT NULL,
                status text NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
                timezone text NOT NULL,
                metadata jsonb NOT NULL DEFAULT '{}'::jsonb CHECK (jsonb_typeof(metadata) = 'object'),
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT networks_name_unique UNIQUE (name)
            );
            CREATE TABLE hospitals (
                hospital_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                network_id uuid NOT NULL REFERENCES networks(network_id) ON DELETE RESTRICT,
                code text NOT NULL,
                name text NOT NULL,
                status text NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
                timezone text NOT NULL,
                clinical_endpoint text,
                schema_version text NOT NULL DEFAULT '1',
                metadata jsonb NOT NULL DEFAULT '{}'::jsonb CHECK (jsonb_typeof(metadata) = 'object'),
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT hospitals_network_code_unique UNIQUE (network_id, code)
            );
            CREATE INDEX hospitals_network_id_index ON hospitals (network_id);
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared('DROP TABLE hospitals; DROP TABLE networks;');
    }
};
