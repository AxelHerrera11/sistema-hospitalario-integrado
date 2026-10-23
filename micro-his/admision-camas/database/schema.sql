-- Subconjunto portable (SQLite y PostgreSQL) de las migraciones HOSPITAL 000200, 000300 y 000700 (local_access_audits, H8).
-- En PostgreSQL la cama la protege la exclusión GiST bed_stays_bed_active_period_exclusion; aquí, un índice único parcial.
CREATE TABLE local_patients (local_patient_id TEXT PRIMARY KEY);
CREATE TABLE beds (
    bed_id TEXT PRIMARY KEY, code TEXT NOT NULL, type TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'available' CHECK (status IN ('available', 'occupied', 'maintenance', 'inactive'))
);
CREATE TABLE care_episodes (
    care_episode_id  TEXT PRIMARY KEY,
    local_patient_id TEXT NOT NULL REFERENCES local_patients (local_patient_id),
    type             TEXT NOT NULL CHECK (type IN ('outpatient', 'emergency', 'inpatient')),
    status           TEXT NOT NULL DEFAULT 'open' CHECK (status IN ('open', 'closed', 'cancelled')),
    started_at       TEXT NOT NULL,
    reason           TEXT NOT NULL CHECK (trim(reason) <> '')
);
CREATE TABLE bed_stays (
    bed_stay_id             TEXT PRIMARY KEY,
    care_episode_id         TEXT NOT NULL REFERENCES care_episodes (care_episode_id),
    bed_id                  TEXT NOT NULL REFERENCES beds (bed_id),
    assigned_by_user_ref_id TEXT NOT NULL,
    started_at              TEXT NOT NULL,
    ended_at                TEXT,
    reason                  TEXT NOT NULL CHECK (trim(reason) <> '')
);
CREATE UNIQUE INDEX bed_stays_open_care_episode_unique ON bed_stays (care_episode_id) WHERE ended_at IS NULL;
CREATE UNIQUE INDEX bed_stays_open_bed_unique ON bed_stays (bed_id) WHERE ended_at IS NULL;
CREATE TABLE local_access_audits (correlation_id TEXT NOT NULL, actor_user_ref_id TEXT NOT NULL, module TEXT NOT NULL, action TEXT NOT NULL,
    resource_type TEXT NOT NULL, resource_id TEXT, local_patient_id TEXT, care_episode_id TEXT, metadata TEXT NOT NULL DEFAULT '{}', occurred_at TEXT NOT NULL);
