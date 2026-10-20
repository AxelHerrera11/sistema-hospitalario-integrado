-- Subconjunto de la migración hospital 000400 (SQLite para pruebas). care_assignments e icd10_codes son de solo lectura.
CREATE TABLE care_assignments (care_episode_id TEXT NOT NULL, user_ref_id TEXT NOT NULL, status TEXT NOT NULL CHECK (status IN ('active', 'ended')));
CREATE TABLE icd10_codes (code TEXT PRIMARY KEY, description TEXT NOT NULL, active INTEGER NOT NULL);
CREATE TABLE clinical_notes (clinical_note_id TEXT PRIMARY KEY, care_episode_id TEXT NOT NULL, author_user_ref_id TEXT NOT NULL, note_type TEXT NOT NULL, content TEXT NOT NULL CHECK (trim(content) <> ''), status TEXT NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'signed')), version_no INTEGER NOT NULL DEFAULT 1);
CREATE TABLE diagnoses (clinical_note_id TEXT NOT NULL REFERENCES clinical_notes (clinical_note_id), care_episode_id TEXT NOT NULL, author_user_ref_id TEXT NOT NULL, code TEXT NOT NULL, description TEXT NOT NULL, type TEXT NOT NULL CHECK (type IN ('primary', 'secondary', 'differential')), status TEXT NOT NULL DEFAULT 'active', onset_date TEXT NOT NULL);
CREATE TABLE local_access_audits (event TEXT NOT NULL, actor_id TEXT NOT NULL, detail TEXT NOT NULL);
