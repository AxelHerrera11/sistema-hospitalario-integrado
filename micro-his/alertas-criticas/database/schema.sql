-- Subconjunto portable (SQLite y PostgreSQL) con los nombres reales de HOSPITAL: umbrales de lab_tests (ASII-17, 000600), relación de
-- cuidado vigente (care_assignments → doctor_profiles y care_episodes, 000100 y 000200), critical_alerts (000660) y local_access_audits (H8, 000700).
CREATE TABLE lab_tests (lab_test_id TEXT PRIMARY KEY, critical_low NUMERIC, critical_high NUMERIC);
CREATE TABLE doctor_profiles (doctor_profile_id TEXT PRIMARY KEY, local_user_ref_id TEXT NOT NULL);
CREATE TABLE care_episodes (care_episode_id TEXT PRIMARY KEY, local_patient_id TEXT NOT NULL);
CREATE TABLE care_assignments (care_episode_id TEXT NOT NULL, doctor_profile_id TEXT NOT NULL, status TEXT NOT NULL, valid_from TEXT NOT NULL, valid_to TEXT);
CREATE TABLE critical_alerts (
    critical_alert_id             TEXT PRIMARY KEY,
    lab_result_version_id         TEXT NOT NULL UNIQUE, -- idempotencia: una alerta por resultado
    lab_test_id                   TEXT NOT NULL,
    local_patient_id              TEXT NOT NULL,
    care_episode_id               TEXT NOT NULL,
    measured_value                NUMERIC NOT NULL,
    direction                     TEXT NOT NULL CHECK (direction IN ('low', 'high')),
    current_recipient_user_ref_id TEXT, -- NULL = jefatura del servicio (nivel ≥ 1)
    escalation_level              INTEGER NOT NULL CHECK (escalation_level BETWEEN 0 AND 2),
    due_at                        TEXT NOT NULL,
    status                        TEXT NOT NULL DEFAULT 'open' CHECK (status IN ('open', 'acknowledged')),
    acknowledged_by_user_ref_id   TEXT,
    acknowledged_at               TEXT,
    CHECK ((status = 'acknowledged') = (acknowledged_by_user_ref_id IS NOT NULL AND acknowledged_at IS NOT NULL))
);
CREATE TABLE local_access_audits (correlation_id TEXT NOT NULL, actor_user_ref_id TEXT NOT NULL, module TEXT NOT NULL, action TEXT NOT NULL,
    resource_type TEXT NOT NULL, resource_id TEXT, local_patient_id TEXT, care_episode_id TEXT, metadata TEXT NOT NULL DEFAULT '{}', occurred_at TEXT NOT NULL);
