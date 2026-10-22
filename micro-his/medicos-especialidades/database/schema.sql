-- Subconjunto de la migración hospital 000100 (SQLite para pruebas). appointments es solo lectura (módulo Citas).
CREATE TABLE specialties (specialty_id TEXT PRIMARY KEY, code TEXT NOT NULL UNIQUE, status TEXT NOT NULL CHECK (status IN ('active', 'inactive')));
CREATE TABLE doctor_profiles (doctor_profile_id TEXT PRIMARY KEY, local_user_ref_id TEXT NOT NULL, license_number TEXT NOT NULL UNIQUE, status TEXT NOT NULL DEFAULT 'active', valid_from TEXT NOT NULL, valid_to TEXT);
CREATE TABLE doctor_specialties (doctor_profile_id TEXT NOT NULL REFERENCES doctor_profiles (doctor_profile_id), specialty_id TEXT NOT NULL REFERENCES specialties (specialty_id), is_primary INTEGER NOT NULL, valid_from TEXT NOT NULL, valid_to TEXT, status TEXT NOT NULL DEFAULT 'active');
CREATE TABLE appointments (doctor_profile_id TEXT NOT NULL, starts_at TEXT NOT NULL, status TEXT NOT NULL);
CREATE TABLE local_access_audits (event TEXT NOT NULL, actor_id TEXT NOT NULL, detail TEXT NOT NULL);
