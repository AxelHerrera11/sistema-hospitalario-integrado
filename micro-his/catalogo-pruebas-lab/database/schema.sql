-- Subconjunto portable (SQLite y PostgreSQL) de lab_tests (Anexo A, propuesta) y local_access_audits (H8).
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
CREATE TABLE local_access_audits (event TEXT NOT NULL, actor TEXT NOT NULL, subject_id TEXT NOT NULL, occurred_at TEXT NOT NULL);
