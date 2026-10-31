CREATE TABLE prescriptions (
    prescription_id UUID PRIMARY KEY,
    local_patient_id UUID NOT NULL REFERENCES local_patients(local_patient_id) ON DELETE RESTRICT,
    care_episode_id UUID NOT NULL REFERENCES care_episodes(care_episode_id) ON DELETE RESTRICT,
    medication_id UUID NOT NULL REFERENCES medications(medication_id) ON DELETE RESTRICT,
    prescribed_by_doctor_profile_id UUID NOT NULL REFERENCES doctor_profiles(doctor_profile_id) ON DELETE RESTRICT,
    signed_by_doctor_profile_id UUID REFERENCES doctor_profiles(doctor_profile_id) ON DELETE RESTRICT,
    dose_amount NUMERIC(10, 3) NOT NULL,
    dose_unit TEXT NOT NULL,
    route TEXT NOT NULL,
    frequency_times INTEGER NOT NULL,
    frequency_interval TEXT,
    duration_value INTEGER NOT NULL,
    duration_unit TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'signed')),
    allergy_check_result TEXT NOT NULL DEFAULT 'clear' CHECK (allergy_check_result IN ('clear', 'warning')),
    signed_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK ((status = 'draft' AND signed_at IS NULL AND signed_by_doctor_profile_id IS NULL)
        OR (status = 'signed' AND signed_at IS NOT NULL AND signed_by_doctor_profile_id IS NOT NULL))
);

CREATE INDEX prescriptions_local_patient_id_index ON prescriptions (local_patient_id);
CREATE INDEX prescriptions_care_episode_id_index ON prescriptions (care_episode_id);
CREATE INDEX prescriptions_medication_id_index ON prescriptions (medication_id);
CREATE INDEX prescriptions_prescribed_by_doctor_profile_id_index ON prescriptions (prescribed_by_doctor_profile_id);
CREATE INDEX prescriptions_signed_by_doctor_profile_id_index ON prescriptions (signed_by_doctor_profile_id);
