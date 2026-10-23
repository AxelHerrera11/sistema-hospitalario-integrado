-- ESQUEMA DE PRUEBA (SQLite en memoria o archivo temporal de la demo).
-- NO es una migración del HIS ni toca las bases CENTRAL/hospital. Si el grupo
-- decide guardar ejecuciones QA en PostgreSQL, Javier debe aprobar primero la
-- propuesta de docs/CI-E2E/semana-04/propuesta-bd-javier.md.

CREATE TABLE IF NOT EXISTS qa_test_runs (
    run_id          TEXT PRIMARY KEY,
    tenant_id       TEXT NOT NULL,
    release_version TEXT NOT NULL,
    commit_sha      TEXT NOT NULL CHECK (length(commit_sha) = 40),
    environment     TEXT NOT NULL CHECK (environment IN ('local', 'ci', 'staging')),
    status          TEXT NOT NULL CHECK (status IN ('planned', 'running', 'completed', 'aborted')),
    plan_json       TEXT NOT NULL,
    created_by      TEXT NOT NULL,
    created_at      TEXT NOT NULL,
    finished_at     TEXT NULL,
    abort_reason    TEXT NULL
);
CREATE INDEX IF NOT EXISTS qa_test_runs_tenant_created ON qa_test_runs (tenant_id, created_at);

CREATE TABLE IF NOT EXISTS qa_suite_results (
    run_id        TEXT NOT NULL REFERENCES qa_test_runs (run_id),
    suite         TEXT NOT NULL,
    total         INTEGER NOT NULL CHECK (total >= 0),
    failed        INTEGER NOT NULL CHECK (failed >= 0),
    skipped       INTEGER NOT NULL CHECK (skipped >= 0),
    duration_ms   INTEGER NOT NULL CHECK (duration_ms >= 0),
    evidence_json TEXT NOT NULL,
    PRIMARY KEY (run_id, suite)
);

CREATE TABLE IF NOT EXISTS qa_gate_decisions (
    decision_id    INTEGER PRIMARY KEY AUTOINCREMENT,
    run_id         TEXT NOT NULL REFERENCES qa_test_runs (run_id),
    tenant_id      TEXT NOT NULL,
    verdict        TEXT NOT NULL CHECK (verdict IN ('approved', 'rejected', 'blocked')),
    policy_version TEXT NOT NULL,
    findings_json  TEXT NOT NULL,
    evaluated_by   TEXT NOT NULL,
    evaluated_at   TEXT NOT NULL
);
