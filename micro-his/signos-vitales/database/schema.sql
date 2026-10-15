-- Subconjunto de la migración hospital 000401 (SQLite para pruebas). care_episodes y care_assignments son de solo lectura.
CREATE TABLE care_episodes (care_episode_id TEXT PRIMARY KEY, status TEXT NOT NULL CHECK (status IN ('open', 'closed')), patient_birth_date TEXT NOT NULL);
CREATE TABLE care_assignments (care_episode_id TEXT NOT NULL REFERENCES care_episodes (care_episode_id), user_ref_id TEXT NOT NULL, status TEXT NOT NULL CHECK (status IN ('active', 'ended')));
CREATE TABLE vital_signs (vital_sign_id TEXT PRIMARY KEY, care_episode_id TEXT NOT NULL REFERENCES care_episodes (care_episode_id), recorded_by TEXT NOT NULL, measured_at TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'validated',
    temperature REAL, heart_rate REAL, respiratory_rate REAL, systolic_bp REAL, diastolic_bp REAL, oxygen_saturation REAL, weight REAL, height REAL, glucose REAL);
CREATE TABLE local_access_audits (event TEXT NOT NULL, actor_id TEXT NOT NULL, detail TEXT NOT NULL);
