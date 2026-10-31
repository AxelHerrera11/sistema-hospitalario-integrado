-- Esquema de PRUEBA y PROTOTIPO para SQLite. NO es una migración ni se ejecuta sobre la base compartida.
-- Reproduce con los MISMOS nombres de tabla y columna el subconjunto de la base HOSPITAL que usa el expediente:
--   000100 local_user_refs, doctor_profiles | 000200 local_patients, care_episodes, care_assignments | 000400 medical_records
-- Tipos adaptados (uuid/timestamptz/jsonb -> TEXT). Divergencias conocidas, todas en tablas de solo lectura para el expediente:
--   * care_assignments sin FK a specialties y sin el trigger enforce_care_assignment_integrity (no se crean especialidades).
--   * doctor_profiles sin la FK a local_user_refs: el prototipo no necesita sincronizar identidades de CENTRAL.
-- medical_records conserva sus CHECK, sus UNIQUE y el trigger de retención (no se borra un expediente).
PRAGMA foreign_keys = ON;

CREATE TABLE local_user_refs (
    local_user_ref_id TEXT PRIMARY KEY, central_user_id TEXT NOT NULL UNIQUE, membership_id TEXT NOT NULL UNIQUE,
    display_name TEXT NOT NULL, role_code TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'revoked')), valid_to TEXT, synced_at TEXT NOT NULL
);
CREATE TABLE doctor_profiles (
    doctor_profile_id TEXT PRIMARY KEY, local_user_ref_id TEXT NOT NULL, license_number TEXT NOT NULL UNIQUE,
    status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'suspended')), valid_from TEXT NOT NULL, valid_to TEXT
);
CREATE TABLE local_patients (
    local_patient_id TEXT PRIMARY KEY, global_id TEXT NOT NULL UNIQUE, code TEXT NOT NULL UNIQUE,
    given_name TEXT NOT NULL, family_name TEXT NOT NULL, birth_date TEXT NOT NULL,
    sex TEXT NOT NULL CHECK (sex IN ('female', 'male', 'other', 'unknown')), contact TEXT,
    status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'deceased')),
    mpi_link_status TEXT NOT NULL DEFAULT 'pending' CHECK (mpi_link_status IN ('pending', 'linked', 'unlinked'))
);
CREATE TABLE care_episodes (
    care_episode_id TEXT PRIMARY KEY, local_patient_id TEXT NOT NULL REFERENCES local_patients (local_patient_id) ON DELETE RESTRICT,
    type TEXT NOT NULL CHECK (type IN ('outpatient', 'emergency', 'inpatient')),
    status TEXT NOT NULL DEFAULT 'open' CHECK (status IN ('open', 'closed', 'cancelled')),
    started_at TEXT NOT NULL, ended_at TEXT, reason TEXT NOT NULL CHECK (trim(reason) <> ''), service_code TEXT,
    CHECK ((status = 'open' AND ended_at IS NULL) OR (status IN ('closed', 'cancelled') AND ended_at > started_at))
);
CREATE TABLE care_assignments (
    care_assignment_id TEXT PRIMARY KEY, care_episode_id TEXT NOT NULL REFERENCES care_episodes (care_episode_id) ON DELETE RESTRICT,
    doctor_profile_id TEXT NOT NULL REFERENCES doctor_profiles (doctor_profile_id) ON DELETE RESTRICT, specialty_id TEXT NOT NULL,
    assigned_by_user_ref_id TEXT NOT NULL, grant_id TEXT, scope TEXT NOT NULL CHECK (json_valid(scope) AND scope <> '{}'),
    reason TEXT NOT NULL CHECK (trim(reason) <> ''),
    source TEXT NOT NULL CHECK (source IN ('manual', 'episode', 'appointment', 'emergency', 'cross_hospital_grant')),
    status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'closed', 'revoked')), valid_from TEXT NOT NULL, valid_to TEXT,
    CHECK ((status = 'active' AND valid_to IS NULL) OR (status IN ('closed', 'revoked') AND valid_to > valid_from))
);
CREATE TABLE medical_records (
    medical_record_id TEXT PRIMARY KEY, local_patient_id TEXT NOT NULL REFERENCES local_patients (local_patient_id) ON DELETE RESTRICT,
    record_number TEXT NOT NULL, opened_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status TEXT NOT NULL DEFAULT 'open' CHECK (status IN ('open', 'closed')), closed_at TEXT,
    CONSTRAINT medical_records_local_patient_id_unique UNIQUE (local_patient_id),
    CONSTRAINT medical_records_record_number_unique UNIQUE (record_number),
    CONSTRAINT medical_records_closure_check CHECK ((status = 'open' AND closed_at IS NULL) OR (status = 'closed' AND closed_at IS NOT NULL AND closed_at >= opened_at))
);
CREATE TRIGGER medical_records_retention BEFORE DELETE ON medical_records BEGIN SELECT RAISE(ABORT, 'clinical_notes_delete_protection'); END;
