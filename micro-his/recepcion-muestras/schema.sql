-- Ejercicio aislado S03/S04; no sustituye las migraciones clínicas de Laravel.
-- Los UUID locales son lógicos aquí; las FK RESTRICT reales se verifican en S05.
CREATE TABLE specimens (
    specimen_id uuid PRIMARY KEY DEFAULT gen_random_uuid(), lab_order_item_id uuid NOT NULL,
    local_patient_id uuid NOT NULL, care_episode_id uuid NOT NULL, received_by_user_ref_id uuid NOT NULL,
    request_key uuid NOT NULL UNIQUE, request_hash text NOT NULL,
    volume numeric(10,3) NOT NULL CHECK (volume BETWEEN 0 AND 1000),
    temperature numeric(6,2) NOT NULL CHECK (temperature BETWEEN -80 AND 100),
    status text NOT NULL CHECK (status IN ('accepted','rejected')), reason text,
    code text CONSTRAINT specimens_code_unique UNIQUE, received_at timestamptz NOT NULL,
    CHECK ((status='accepted' AND code IS NOT NULL AND reason IS NULL) OR (status='rejected' AND reason IS NOT NULL))
);
CREATE UNIQUE INDEX specimens_one_accepted ON specimens(lab_order_item_id) WHERE status='accepted';
CREATE INDEX specimens_lab_order_item_id_index ON specimens(lab_order_item_id);
COMMENT ON COLUMN specimens.lab_order_item_id IS 'FK a lab_order_items cuando ASII-16 publique 000600';
CREATE TABLE outbox_events (
    outbox_event_id uuid PRIMARY KEY DEFAULT gen_random_uuid(), event_id uuid NOT NULL UNIQUE,
    correlation_id uuid NOT NULL, aggregate_type text NOT NULL, aggregate_id uuid,
    event_type text NOT NULL, payload_minimal jsonb NOT NULL, status text NOT NULL DEFAULT 'pending',
    occurred_at timestamptz NOT NULL DEFAULT now()
);
