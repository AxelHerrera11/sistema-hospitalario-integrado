-- Subconjunto portable (SQLite y PostgreSQL) de la migración HOSPITAL 000401 (allergies) y de las tablas que lee o escribe.
CREATE TABLE local_patients (local_patient_id TEXT PRIMARY KEY);
CREATE TABLE allergies (
    allergy_id              TEXT PRIMARY KEY,
    local_patient_id        TEXT NOT NULL REFERENCES local_patients (local_patient_id),
    verified_by_user_ref_id TEXT NOT NULL,
    allergen_code           TEXT,
    allergen_name           TEXT,
    reaction                TEXT CHECK (reaction IS NULL OR trim(reaction) <> ''),
    allergy_type            TEXT NOT NULL CHECK (allergy_type IN ('allergy', 'intolerance', 'unknown')),
    severity                TEXT NOT NULL CHECK (severity IN ('mild', 'moderate', 'severe', 'life_threatening', 'unknown')),
    status                  TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'resolved')),
    CHECK (COALESCE(NULLIF(trim(allergen_code), ''), NULLIF(trim(allergen_name), '')) IS NOT NULL)
);
CREATE UNIQUE INDEX allergies_active_equivalent_unique ON allergies
    (local_patient_id, allergy_type, lower(COALESCE(NULLIF(trim(allergen_code), ''), NULLIF(trim(allergen_name), '')))) WHERE status = 'active';
CREATE TABLE local_access_audits (event TEXT NOT NULL, actor TEXT NOT NULL, subject_id TEXT NOT NULL, occurred_at TEXT NOT NULL);
