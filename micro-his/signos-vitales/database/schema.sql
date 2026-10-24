-- Esquema local simplificado para SQLite, NO subconjunto de la migración hospital 000401. care_episodes y care_assignments son de solo lectura.
-- Divergencias pendientes de integración con el esquema HOSPITAL:
-- care_assignments usa user_ref_id y los estados active/ended (la tabla real, migración 000200, usa doctor_profile_id -> doctor_profiles.local_user_ref_id y active/closed/revoked);
-- care_episodes copia patient_birth_date (la real lo tiene en local_patients.birth_date) y no modela el estado cancelled;
-- vital_signs usa recorded_by (real: recorded_by_user_ref_id), se inserta sin medical_record_id (NOT NULL en 000401) y con status 'validated' por defecto (real: 'draft'; validated exige validated_at);
-- los ids son TEXT (reales: uuid), los signos son REAL (FC, FR y presión son integer en 000401) y measured_at es TEXT (real: timestamptz);
-- local_access_audits no existe todavía en las migraciones de develop (slice H8).
CREATE TABLE care_episodes (care_episode_id TEXT PRIMARY KEY, status TEXT NOT NULL CHECK (status IN ('open', 'closed')), patient_birth_date TEXT NOT NULL);
CREATE TABLE care_assignments (care_episode_id TEXT NOT NULL REFERENCES care_episodes (care_episode_id), user_ref_id TEXT NOT NULL, status TEXT NOT NULL CHECK (status IN ('active', 'ended')));
CREATE TABLE vital_signs (vital_sign_id TEXT PRIMARY KEY, care_episode_id TEXT NOT NULL REFERENCES care_episodes (care_episode_id), recorded_by TEXT NOT NULL, measured_at TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'validated',
    temperature REAL, heart_rate REAL, respiratory_rate REAL, systolic_bp REAL, diastolic_bp REAL, oxygen_saturation REAL, weight REAL, height REAL, glucose REAL);
CREATE TABLE local_access_audits (event TEXT NOT NULL, actor_id TEXT NOT NULL, detail TEXT NOT NULL);
