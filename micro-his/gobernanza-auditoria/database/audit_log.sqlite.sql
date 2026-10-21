-- Esquema de PRUEBA (SQLite en memoria) que reproduce las columnas de
-- audit_log y audit_chain_state definidas en las migraciones CENTRAL de la
-- rama feature/asii-25-semana-16-tenants-auditoria:
--   2026_10_19_000200_create_audit_log.php
--   2026_10_20_000200_enrich_audit_log.php
-- No es una migración ni debe ejecutarse sobre una base compartida.
-- Tipos adaptados a SQLite: uuid/jsonb/timestamptz/char(64) -> TEXT.

CREATE TABLE audit_log (
    event_id      TEXT PRIMARY KEY,
    occurred_at   TEXT NOT NULL,
    event         TEXT NOT NULL,
    actor_id      TEXT NOT NULL,
    hospital_id   TEXT NOT NULL,
    detail        TEXT NOT NULL,
    trace_id      TEXT,
    result        TEXT,
    previous_hash TEXT,
    event_hash    TEXT
);
CREATE INDEX audit_log_actor_index ON audit_log (actor_id);
CREATE INDEX audit_log_hospital_index ON audit_log (hospital_id);
CREATE INDEX audit_log_trace_index ON audit_log (trace_id);

CREATE TABLE audit_chain_state (
    chain_id  INTEGER PRIMARY KEY CHECK (chain_id = 1),
    head_hash TEXT NOT NULL
);
INSERT INTO audit_chain_state (chain_id, head_hash) VALUES (1, '0000000000000000000000000000000000000000000000000000000000000000');

-- Equivalente SQLite del trigger audit_log_reject_update_or_delete (append-only).
CREATE TRIGGER audit_log_reject_update BEFORE UPDATE ON audit_log
BEGIN SELECT RAISE(ABORT, 'audit_log is append-only'); END;
CREATE TRIGGER audit_log_reject_delete BEFORE DELETE ON audit_log
BEGIN SELECT RAISE(ABORT, 'audit_log is append-only'); END;
