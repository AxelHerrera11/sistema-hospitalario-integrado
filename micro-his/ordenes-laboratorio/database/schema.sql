-- Subconjunto portable (PostgreSQL 13+ y SQLite) del esquema HOSPITAL para órdenes de laboratorio.
-- Referencias lógicas a lab_tests, specimens y lab_result_versions: sus tablas pertenecen a otros
-- módulos y no se crean aquí. El nombre y código de la prueba se guardan como instantánea.
CREATE TABLE lab_orders (
    lab_order_id       TEXT PRIMARY KEY,
    tenant_id          TEXT NOT NULL,
    local_patient_id   TEXT NOT NULL,
    care_episode_id    TEXT NOT NULL,
    clinical_note_id   TEXT,
    ordered_by         TEXT NOT NULL,
    priority           TEXT NOT NULL CHECK (priority IN ('ROUTINE', 'URGENT', 'STAT')),
    clinical_context   TEXT,
    status             TEXT NOT NULL CHECK (status IN ('ISSUED', 'COLLECTED', 'IN_PROCESS', 'RESULTED', 'VALIDATED', 'CANCELLED')),
    idempotency_key    TEXT NOT NULL,
    correlation_id     TEXT NOT NULL,
    created_at         TEXT NOT NULL,
    updated_at         TEXT NOT NULL,
    UNIQUE (tenant_id, idempotency_key)
);

CREATE TABLE lab_order_items (
    lab_order_id  TEXT NOT NULL,
    lab_test_id   TEXT NOT NULL,
    test_code     TEXT NOT NULL,
    test_name     TEXT NOT NULL,
    PRIMARY KEY (lab_order_id, lab_test_id),
    FOREIGN KEY (lab_order_id) REFERENCES lab_orders (lab_order_id) ON DELETE CASCADE
);

-- Historial append-only: la aplicación solo inserta; nunca actualiza ni borra un evento.
CREATE TABLE lab_order_events (
    event_id        TEXT PRIMARY KEY,
    lab_order_id    TEXT NOT NULL,
    event_seq       INTEGER NOT NULL,
    from_status     TEXT,
    to_status       TEXT NOT NULL,
    actor           TEXT NOT NULL,
    occurred_at     TEXT NOT NULL,
    correlation_id  TEXT NOT NULL,
    reason          TEXT,
    UNIQUE (lab_order_id, event_seq),
    FOREIGN KEY (lab_order_id) REFERENCES lab_orders (lab_order_id) ON DELETE CASCADE
);

CREATE INDEX lab_orders_patient_index ON lab_orders (tenant_id, local_patient_id, created_at);
CREATE INDEX lab_orders_episode_index ON lab_orders (tenant_id, care_episode_id, created_at);
CREATE INDEX lab_orders_status_index ON lab_orders (tenant_id, status, created_at);
