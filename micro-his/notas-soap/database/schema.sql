-- Esquema local simplificado para SQLite, NO subconjunto de la migración hospital 000400. care_assignments e icd10_codes son de solo lectura.
-- Alineado con la migración hospital 000200/000400 en lo que usa el módulo: care_assignments cuelga de doctor_profile_id (active/closed/revoked), el usuario está en doctor_profiles.local_user_ref_id
-- y clinical_notes lleva medical_record_id. icd10_codes sigue provisional: la tabla no existe en develop.
-- Replica las reglas que no dependen de otras tablas: nota firmada inmutable, diagnóstico con el episodio y autor de su nota,
-- sin borrados y fechas ISO. local_access_audits sigue el Anexo A de la Semana 1 y es provisional hasta la migración del slice H8.
PRAGMA foreign_keys = ON;
CREATE TABLE doctor_profiles (doctor_profile_id TEXT PRIMARY KEY, local_user_ref_id TEXT NOT NULL);
CREATE TABLE care_episodes (care_episode_id TEXT PRIMARY KEY, local_patient_id TEXT NOT NULL);
CREATE TABLE medical_records (medical_record_id TEXT PRIMARY KEY, local_patient_id TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'open' CHECK (status IN ('open', 'closed')));
CREATE TABLE care_assignments (care_episode_id TEXT NOT NULL, doctor_profile_id TEXT NOT NULL, status TEXT NOT NULL CHECK (status IN ('active', 'closed', 'revoked')), valid_from TEXT NOT NULL DEFAULT '2020-01-01 00:00:00', valid_to TEXT);
CREATE TABLE icd10_codes (code TEXT PRIMARY KEY, description TEXT NOT NULL, active INTEGER NOT NULL);
CREATE TABLE clinical_notes (clinical_note_id TEXT PRIMARY KEY, medical_record_id TEXT NOT NULL, care_episode_id TEXT NOT NULL, author_user_ref_id TEXT NOT NULL, note_type TEXT NOT NULL CHECK (trim(note_type) <> ''), content TEXT NOT NULL CHECK (trim(content) <> ''), status TEXT NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'signed')), version_no INTEGER NOT NULL DEFAULT 1 CHECK (version_no > 0));
CREATE TABLE diagnoses (diagnosis_id TEXT PRIMARY KEY, clinical_note_id TEXT NOT NULL REFERENCES clinical_notes (clinical_note_id) ON DELETE RESTRICT, care_episode_id TEXT NOT NULL, author_user_ref_id TEXT NOT NULL, code TEXT NOT NULL CHECK (trim(code) <> ''), description TEXT NOT NULL CHECK (trim(description) <> ''), type TEXT NOT NULL CHECK (type IN ('primary', 'secondary', 'differential')), status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'resolved', 'entered_in_error')), onset_date TEXT NOT NULL CHECK (coalesce(date(onset_date) = onset_date, 0)));
CREATE TRIGGER clinical_notes_signed_immutable BEFORE UPDATE ON clinical_notes WHEN OLD.status = 'signed' BEGIN SELECT RAISE(ABORT, 'clinical_notes_signed_immutable'); END;
CREATE TRIGGER diagnoses_note_episode_author BEFORE INSERT ON diagnoses WHEN NOT EXISTS (SELECT 1 FROM clinical_notes n WHERE n.clinical_note_id = NEW.clinical_note_id AND n.care_episode_id = NEW.care_episode_id AND n.author_user_ref_id = NEW.author_user_ref_id)
    BEGIN SELECT RAISE(ABORT, 'diagnoses_note_episode_author_trigger'); END;
CREATE TRIGGER clinical_notes_retention BEFORE DELETE ON clinical_notes BEGIN SELECT RAISE(ABORT, 'clinical_notes_delete_protection'); END;
CREATE TRIGGER diagnoses_retention BEFORE DELETE ON diagnoses BEGIN SELECT RAISE(ABORT, 'clinical_notes_delete_protection'); END;
CREATE TABLE local_access_audits (audit_event_id TEXT PRIMARY KEY, correlation_id TEXT NOT NULL, actor_user_ref_id TEXT NOT NULL, module TEXT NOT NULL CHECK (trim(module) <> ''),
    action TEXT NOT NULL CHECK (action GLOB '[a-z]*.[a-z]*' AND action NOT GLOB '*[^a-z_.]*'), resource_type TEXT NOT NULL CHECK (trim(resource_type) <> ''), resource_id TEXT NOT NULL, care_episode_id TEXT,
    decision TEXT NOT NULL DEFAULT 'allowed' CHECK (decision IN ('allowed', 'denied')), reason TEXT, metadata TEXT NOT NULL DEFAULT '{}' CHECK (json_valid(metadata) AND json_type(metadata) = 'object'), occurred_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);
CREATE TRIGGER local_access_audits_immutable BEFORE UPDATE ON local_access_audits BEGIN SELECT RAISE(ABORT, 'local_access_audits is append-only'); END;
CREATE TRIGGER local_access_audits_no_delete BEFORE DELETE ON local_access_audits BEGIN SELECT RAISE(ABORT, 'local_access_audits is append-only'); END;
