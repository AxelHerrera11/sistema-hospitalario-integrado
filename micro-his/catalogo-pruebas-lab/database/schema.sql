-- Subconjunto portable (SQLite y PostgreSQL) de lab_tests (Anexo A, propuesta) y de las columnas que se escriben de local_access_audits (H8, migración 000700).
CREATE TABLE lab_tests (
    lab_test_id    TEXT PRIMARY KEY,
    code           TEXT NOT NULL,
    name           TEXT NOT NULL,
    specimen_type  TEXT NOT NULL,
    min_volume_ml  NUMERIC NOT NULL CHECK (min_volume_ml > 0),
    result_type    TEXT NOT NULL CHECK (result_type IN ('numeric', 'qualitative', 'text')),
    unit           TEXT,
    reference_low  NUMERIC,
    reference_high NUMERIC,
    critical_low   NUMERIC,
    critical_high  NUMERIC,
    version_no     INTEGER NOT NULL,
    valid_from     DATE NOT NULL,
    valid_to       DATE,
    status         TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    UNIQUE (code, version_no)
);
CREATE TABLE local_access_audits (correlation_id TEXT NOT NULL, actor_user_ref_id TEXT NOT NULL, module TEXT NOT NULL, action TEXT NOT NULL,
    resource_type TEXT NOT NULL, resource_id TEXT, local_patient_id TEXT, care_episode_id TEXT, metadata TEXT NOT NULL DEFAULT '{}', occurred_at TEXT NOT NULL);
