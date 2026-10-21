-- PostgreSQL; projection populated by the orders/catalog integration, never by reception clients.
CREATE TABLE reception_order_items (
    tenant_id varchar(100) NOT NULL, item_id varchar(100) NOT NULL,
    patient_code text NOT NULL, patient_name text NOT NULL,
    type text NOT NULL CHECK (type IN ('blood', 'urine', 'swab')),
    container text NOT NULL, min_volume double precision NOT NULL CHECK (min_volume >= 0),
    max_minutes integer NOT NULL CHECK (max_minutes > 0),
    min_temperature double precision NOT NULL, max_temperature double precision NOT NULL,
    status text NOT NULL CHECK (status IN ('pending', 'pending_new_sample', 'received', 'cancelled')),
    PRIMARY KEY (tenant_id, item_id), CHECK (min_temperature <= max_temperature)
);
CREATE TABLE reception_specimens (
    id varchar(32) PRIMARY KEY, tenant_id varchar(100) NOT NULL, item_id varchar(100) NOT NULL,
    actor_id varchar(100) NOT NULL, status text NOT NULL CHECK (status IN ('accepted', 'rejected')),
    reason text, code varchar(26) UNIQUE, received_at timestamp with time zone NOT NULL,
    FOREIGN KEY (tenant_id, item_id) REFERENCES reception_order_items(tenant_id, item_id),
    CHECK ((status = 'accepted' AND code IS NOT NULL AND reason IS NULL)
        OR (status = 'rejected' AND code IS NULL AND reason IS NOT NULL))
);
CREATE UNIQUE INDEX reception_one_accepted ON reception_specimens (tenant_id, item_id) WHERE status = 'accepted';
CREATE TABLE reception_outbox (
    specimen_id varchar(32) PRIMARY KEY REFERENCES reception_specimens(id),
    tenant_id varchar(100) NOT NULL, payload text NOT NULL, delivered_at timestamp with time zone
);
