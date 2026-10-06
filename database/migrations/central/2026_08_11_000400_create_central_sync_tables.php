<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            CREATE TABLE sync_nodes (
                node_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                hospital_id uuid NOT NULL REFERENCES hospitals(hospital_id) ON DELETE RESTRICT,
                name text NOT NULL CHECK (btrim(name) <> ''),
                status text NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'degraded')),
                last_seen_at timestamptz,
                schema_version text NOT NULL CHECK (btrim(schema_version) <> ''),
                key_version text NOT NULL CHECK (btrim(key_version) <> ''),
                metadata jsonb NOT NULL DEFAULT '{}'::jsonb CHECK (jsonb_typeof(metadata) = 'object'),
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT sync_nodes_hospital_id_unique UNIQUE (hospital_id)
            );
            CREATE INDEX sync_nodes_hospital_id_index ON sync_nodes (hospital_id);

            CREATE TABLE sync_checkpoints (
                checkpoint_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                node_id uuid NOT NULL REFERENCES sync_nodes(node_id) ON DELETE RESTRICT,
                channel text NOT NULL CHECK (btrim(channel) <> ''),
                last_event_id uuid,
                last_occurred_at timestamptz,
                lag_seconds bigint NOT NULL DEFAULT 0 CHECK (lag_seconds >= 0),
                status text NOT NULL DEFAULT 'healthy' CHECK (status IN ('healthy', 'degraded', 'failed')),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT sync_checkpoints_node_channel_unique UNIQUE (node_id, channel)
            );
            CREATE INDEX sync_checkpoints_node_id_index ON sync_checkpoints (node_id);

            CREATE TABLE central_inbox_messages (
                message_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                source_node_id uuid NOT NULL REFERENCES sync_nodes(node_id) ON DELETE RESTRICT,
                event_id uuid NOT NULL,
                event_type text NOT NULL CHECK (btrim(event_type) <> ''),
                payload_minimal jsonb NOT NULL CHECK (jsonb_typeof(payload_minimal) = 'object'),
                metadata jsonb NOT NULL DEFAULT '{}'::jsonb CHECK (jsonb_typeof(metadata) = 'object'),
                received_at timestamptz NOT NULL DEFAULT now(),
                available_at timestamptz NOT NULL DEFAULT now(),
                processed_at timestamptz,
                failed_at timestamptz,
                status text NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'processed', 'failed')),
                attempts bigint NOT NULL DEFAULT 0 CHECK (attempts >= 0),
                error_code text,
                CONSTRAINT central_inbox_messages_source_event_unique UNIQUE (source_node_id, event_id),
                CONSTRAINT central_inbox_messages_available_after_received CHECK (available_at >= received_at),
                CONSTRAINT central_inbox_messages_status_times CHECK (
                    (status IN ('pending', 'processing') AND processed_at IS NULL AND failed_at IS NULL AND error_code IS NULL)
                    OR (status = 'processed' AND processed_at IS NOT NULL AND processed_at >= received_at AND processed_at >= available_at AND failed_at IS NULL AND error_code IS NULL)
                    OR (status = 'failed' AND failed_at IS NOT NULL AND failed_at >= received_at AND failed_at >= available_at AND processed_at IS NULL AND error_code IS NOT NULL AND btrim(error_code) <> '')
                )
            );
            CREATE INDEX central_inbox_messages_source_node_id_index ON central_inbox_messages (source_node_id);
            CREATE INDEX central_inbox_messages_status_available_at_index ON central_inbox_messages (status, available_at);
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared('DROP TABLE central_inbox_messages; DROP TABLE sync_checkpoints; DROP TABLE sync_nodes;');
    }
};
