-- Subconjunto portable: umbrales de lab_tests (ASII-17), care_assignments simplificada, critical_alerts (Anexo A) y auditoría.
CREATE TABLE lab_tests (lab_test_id TEXT PRIMARY KEY, critical_low NUMERIC, critical_high NUMERIC);
CREATE TABLE care_assignments (care_episode_id TEXT NOT NULL, local_patient_id TEXT NOT NULL, user_ref_id TEXT NOT NULL, status TEXT NOT NULL);
CREATE TABLE critical_alerts (
    critical_alert_id           TEXT PRIMARY KEY,
    lab_result_version_id       TEXT NOT NULL UNIQUE, -- idempotencia: una alerta por resultado
    local_patient_id            TEXT NOT NULL,
    lab_test_id                 TEXT NOT NULL,
    measured_value              NUMERIC NOT NULL,
    direction                   TEXT NOT NULL CHECK (direction IN ('low', 'high')),
    current_recipient           TEXT NOT NULL,
    escalation_level            INTEGER NOT NULL CHECK (escalation_level BETWEEN 0 AND 2),
    due_at                      TEXT NOT NULL,
    status                      TEXT NOT NULL DEFAULT 'open' CHECK (status IN ('open', 'acknowledged')),
    acknowledged_by_user_ref_id TEXT,
    CHECK ((status = 'acknowledged') = (acknowledged_by_user_ref_id IS NOT NULL))
);
CREATE TABLE local_access_audits (event TEXT NOT NULL, actor TEXT NOT NULL, subject_id TEXT NOT NULL, occurred_at TEXT NOT NULL);
