<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Slice H8: auditoria local, outbox e inbox.
 *
 * Numero de archivo 000700: es el que reserva la especificacion para el slice H8
 * (openspec .../tasks.md, tarea 3.7). El 000800 queda reservado para
 * 000800_create_immutable_record_triggers.php.
 */
return new class extends Migration
{
    protected $connection = 'hospital';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            CREATE TABLE local_access_audits (
                audit_event_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                correlation_id uuid NOT NULL,
                actor_user_ref_id uuid NOT NULL REFERENCES local_user_refs(local_user_ref_id) ON DELETE RESTRICT,
                module text NOT NULL CONSTRAINT local_access_audits_module_check CHECK (btrim(module) <> ''),
                action text NOT NULL CONSTRAINT local_access_audits_action_check CHECK (btrim(action) <> ''),
                resource_type text NOT NULL CONSTRAINT local_access_audits_resource_type_check CHECK (btrim(resource_type) <> ''),
                resource_id uuid,
                local_patient_id uuid REFERENCES local_patients(local_patient_id) ON DELETE RESTRICT,
                care_episode_id uuid REFERENCES care_episodes(care_episode_id) ON DELETE RESTRICT,
                decision text NOT NULL DEFAULT 'allowed' CONSTRAINT local_access_audits_decision_check CHECK (decision IN ('allowed', 'denied')),
                reason text,
                metadata jsonb NOT NULL DEFAULT '{}'::jsonb CONSTRAINT local_access_audits_metadata_check CHECK (jsonb_typeof(metadata) = 'object'),
                occurred_at timestamptz NOT NULL DEFAULT now(),
                created_at timestamptz NOT NULL DEFAULT now()
            );
            COMMENT ON TABLE local_access_audits IS 'Slice H8: bitacora local de acceso. Solo inserciones; nunca texto clinico en metadata.';
            CREATE INDEX local_access_audits_correlation_id_index ON local_access_audits (correlation_id);
            CREATE INDEX local_access_audits_actor_user_ref_id_index ON local_access_audits (actor_user_ref_id);
            CREATE INDEX local_access_audits_resource_index ON local_access_audits (resource_type, resource_id);
            CREATE INDEX local_access_audits_occurred_at_index ON local_access_audits (occurred_at);

            CREATE TABLE outbox_events (
                outbox_event_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                event_id uuid NOT NULL,
                correlation_id uuid NOT NULL,
                aggregate_type text NOT NULL CONSTRAINT outbox_events_aggregate_type_check CHECK (btrim(aggregate_type) <> ''),
                aggregate_id uuid,
                event_type text NOT NULL CONSTRAINT outbox_events_event_type_check CHECK (btrim(event_type) <> ''),
                payload_minimal jsonb NOT NULL DEFAULT '{}'::jsonb CONSTRAINT outbox_events_payload_check CHECK (jsonb_typeof(payload_minimal) = 'object'),
                metadata jsonb NOT NULL DEFAULT '{}'::jsonb CONSTRAINT outbox_events_metadata_check CHECK (jsonb_typeof(metadata) = 'object'),
                occurred_at timestamptz NOT NULL DEFAULT now(),
                published_at timestamptz,
                status text NOT NULL DEFAULT 'pending' CONSTRAINT outbox_events_status_check CHECK (status IN ('pending', 'publishing', 'published', 'failed')),
                attempts integer NOT NULL DEFAULT 0 CONSTRAINT outbox_events_attempts_check CHECK (attempts >= 0),
                error_code text,
                created_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT outbox_events_event_id_unique UNIQUE (event_id),
                CONSTRAINT outbox_events_publication_check CHECK ((status = 'published' AND published_at IS NOT NULL AND published_at >= occurred_at) OR (status <> 'published' AND published_at IS NULL))
            );
            COMMENT ON TABLE outbox_events IS 'Slice H8: eventos salientes pendientes de publicar. Solo se actualizan las columnas de publicacion.';
            CREATE INDEX outbox_events_pending_index ON outbox_events (occurred_at) WHERE status = 'pending';
            CREATE INDEX outbox_events_correlation_id_index ON outbox_events (correlation_id);

            CREATE TABLE inbox_messages (
                message_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                event_id uuid NOT NULL,
                source_node_id uuid,
                event_type text NOT NULL CONSTRAINT inbox_messages_event_type_check CHECK (btrim(event_type) <> ''),
                payload_minimal jsonb NOT NULL DEFAULT '{}'::jsonb CONSTRAINT inbox_messages_payload_check CHECK (jsonb_typeof(payload_minimal) = 'object'),
                metadata jsonb NOT NULL DEFAULT '{}'::jsonb CONSTRAINT inbox_messages_metadata_check CHECK (jsonb_typeof(metadata) = 'object'),
                received_at timestamptz NOT NULL DEFAULT now(),
                available_at timestamptz NOT NULL DEFAULT now(),
                processed_at timestamptz,
                failed_at timestamptz,
                status text NOT NULL DEFAULT 'pending' CONSTRAINT inbox_messages_status_check CHECK (status IN ('pending', 'processing', 'processed', 'failed')),
                attempts integer NOT NULL DEFAULT 0 CONSTRAINT inbox_messages_attempts_check CHECK (attempts >= 0),
                error_code text,
                created_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT inbox_messages_event_id_unique UNIQUE (event_id),
                CONSTRAINT inbox_messages_processing_check CHECK ((status = 'processed' AND processed_at IS NOT NULL) OR (status = 'failed' AND failed_at IS NOT NULL) OR status IN ('pending', 'processing'))
            );
            COMMENT ON TABLE inbox_messages IS 'Slice H8: mensajes entrantes por procesar. Idempotencia por event_id.';
            CREATE INDEX inbox_messages_pending_index ON inbox_messages (available_at) WHERE status = 'pending';

            CREATE OR REPLACE FUNCTION enforce_local_audit_append_only() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'local_access_audits_append_only_trigger';
            END; $$;
            CREATE TRIGGER local_access_audits_append_only BEFORE UPDATE OR DELETE ON local_access_audits FOR EACH ROW EXECUTE FUNCTION enforce_local_audit_append_only();

            CREATE OR REPLACE FUNCTION enforce_outbox_payload_immutable() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_OP = 'DELETE' THEN
                    RAISE EXCEPTION 'outbox_events_no_delete_trigger';
                END IF;
                IF NEW.event_id IS DISTINCT FROM OLD.event_id
                   OR NEW.correlation_id IS DISTINCT FROM OLD.correlation_id
                   OR NEW.aggregate_type IS DISTINCT FROM OLD.aggregate_type
                   OR NEW.aggregate_id IS DISTINCT FROM OLD.aggregate_id
                   OR NEW.event_type IS DISTINCT FROM OLD.event_type
                   OR NEW.payload_minimal IS DISTINCT FROM OLD.payload_minimal
                   OR NEW.metadata IS DISTINCT FROM OLD.metadata
                   OR NEW.occurred_at IS DISTINCT FROM OLD.occurred_at
                   OR NEW.created_at IS DISTINCT FROM OLD.created_at THEN
                    RAISE EXCEPTION 'outbox_events_payload_immutable_trigger';
                END IF;
                RETURN NEW;
            END; $$;
            CREATE TRIGGER outbox_events_payload_immutable BEFORE UPDATE OR DELETE ON outbox_events FOR EACH ROW EXECUTE FUNCTION enforce_outbox_payload_immutable();
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared('DROP TRIGGER outbox_events_payload_immutable ON outbox_events; DROP TRIGGER local_access_audits_append_only ON local_access_audits; DROP FUNCTION enforce_outbox_payload_immutable(); DROP FUNCTION enforce_local_audit_append_only(); DROP TABLE inbox_messages; DROP TABLE outbox_events; DROP TABLE local_access_audits;');
    }
};
