<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ASII-16: persistencia compartida para órdenes de laboratorio.
 *
 * El micro-HIS de órdenes ya tiene dominio y contrato; esta migración crea el
 * límite persistente que consumen recepción, resultados y validación dentro de
 * la base HOSPITAL. El tenant se conserva aunque la instancia use una base por
 * hospital, para que las consultas no puedan mezclar contextos.
 */
return new class extends Migration
{
    protected $connection = 'hospital';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            CREATE TABLE lab_orders (
                lab_order_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                tenant_id uuid NOT NULL,
                local_patient_id uuid NOT NULL REFERENCES local_patients(local_patient_id) ON DELETE RESTRICT,
                care_episode_id uuid NOT NULL REFERENCES care_episodes(care_episode_id) ON DELETE RESTRICT,
                clinical_note_id uuid REFERENCES clinical_notes(clinical_note_id) ON DELETE RESTRICT,
                ordered_by_user_ref_id uuid NOT NULL REFERENCES local_user_refs(local_user_ref_id) ON DELETE RESTRICT,
                priority text NOT NULL DEFAULT 'ROUTINE' CHECK (priority IN ('ROUTINE', 'URGENT', 'STAT')),
                clinical_context text,
                status text NOT NULL DEFAULT 'REQUESTED' CHECK (status IN ('REQUESTED', 'COLLECTED', 'IN_PROCESS', 'RESULTED', 'VALIDATED', 'CANCELLED')),
                idempotency_key text NOT NULL,
                idempotency_request_hash text NOT NULL,
                correlation_id uuid NOT NULL DEFAULT gen_random_uuid(),
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT lab_orders_idempotency_unique UNIQUE (tenant_id, idempotency_key)
            );
            CREATE INDEX lab_orders_tenant_patient_index ON lab_orders (tenant_id, local_patient_id, created_at DESC);
            CREATE INDEX lab_orders_tenant_episode_index ON lab_orders (tenant_id, care_episode_id, created_at DESC);
            CREATE INDEX lab_orders_tenant_status_index ON lab_orders (tenant_id, status, created_at DESC);
            CREATE OR REPLACE FUNCTION enforce_lab_order_episode_patient() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM care_episodes e WHERE e.care_episode_id = NEW.care_episode_id AND e.local_patient_id = NEW.local_patient_id) THEN
                    RAISE EXCEPTION 'lab_order_episode_patient_mismatch';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER lab_orders_episode_patient_integrity
                BEFORE INSERT OR UPDATE OF local_patient_id, care_episode_id ON lab_orders
                FOR EACH ROW EXECUTE FUNCTION enforce_lab_order_episode_patient();

            CREATE TABLE lab_order_items (
                lab_order_item_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                lab_order_id uuid NOT NULL REFERENCES lab_orders(lab_order_id) ON DELETE RESTRICT,
                lab_test_id uuid NOT NULL REFERENCES lab_tests(lab_test_id) ON DELETE RESTRICT,
                test_code text NOT NULL,
                test_name text NOT NULL,
                created_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT lab_order_items_order_test_unique UNIQUE (lab_order_id, lab_test_id)
            );
            CREATE INDEX lab_order_items_order_index ON lab_order_items (lab_order_id);

            CREATE TABLE lab_order_events (
                event_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                lab_order_id uuid NOT NULL REFERENCES lab_orders(lab_order_id) ON DELETE RESTRICT,
                event_seq integer NOT NULL CHECK (event_seq > 0),
                from_status text,
                to_status text NOT NULL,
                actor_user_ref_id uuid NOT NULL REFERENCES local_user_refs(local_user_ref_id) ON DELETE RESTRICT,
                occurred_at timestamptz NOT NULL DEFAULT now(),
                correlation_id uuid NOT NULL,
                reason text,
                CONSTRAINT lab_order_events_seq_unique UNIQUE (lab_order_id, event_seq)
            );
            CREATE INDEX lab_order_events_order_index ON lab_order_events (lab_order_id, event_seq);
            CREATE OR REPLACE FUNCTION preserve_lab_order_event() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN RAISE EXCEPTION 'lab_order_events_append_only'; END;
            $$;
            CREATE TRIGGER lab_order_events_retention
                BEFORE UPDATE OR DELETE ON lab_order_events
                FOR EACH ROW EXECUTE FUNCTION preserve_lab_order_event();
        SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            DROP TRIGGER lab_order_events_retention ON lab_order_events;
            DROP FUNCTION preserve_lab_order_event();
            DROP TABLE lab_order_events;
            DROP TABLE lab_order_items;
            DROP TRIGGER lab_orders_episode_patient_integrity ON lab_orders;
            DROP FUNCTION enforce_lab_order_episode_patient();
            DROP TABLE lab_orders;
        SQL);
    }
};
