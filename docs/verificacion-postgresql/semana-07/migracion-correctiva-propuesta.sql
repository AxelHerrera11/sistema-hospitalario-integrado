-- Migracion correctiva propuesta (Semana 7). NO se aplica en este PR.
-- Motivo: el numero de archivo debe acordarse con el grupo para no colisionar con
-- los slices H5 (medicamentos), H6 (laboratorio) y H7 (auditoria y sincronizacion).
-- Propuesta de nombre: database/migrations/hospital/<AAAA_MM_DD_NNNNNN>_add_text_length_constraints.php
--
-- Hallazgo H-04: texto libre sin longitud maxima.
-- Hallazgo H-05: diagnosticos sin nota (decision pendiente, no se restringe aqui).

-- HOSPITAL ---------------------------------------------------------------------

-- Limites propuestos: los valores concretos deben acordarse con cada modulo.
ALTER TABLE local_patients
    ADD CONSTRAINT local_patients_contact_length_check
    CHECK (contact IS NULL OR char_length(btrim(contact)) <= 200);

ALTER TABLE allergies
    ADD CONSTRAINT allergies_reaction_length_check
    CHECK (reaction IS NULL OR char_length(btrim(reaction)) <= 500);

ALTER TABLE clinical_notes
    ADD CONSTRAINT clinical_notes_content_length_check
    CHECK (char_length(btrim(content)) <= 20000);

ALTER TABLE care_episodes
    ADD CONSTRAINT care_episodes_reason_length_check
    CHECK (char_length(btrim(reason)) <= 500);

ALTER TABLE bed_stays
    ADD CONSTRAINT bed_stays_reason_length_check
    CHECK (char_length(btrim(reason)) <= 500);

ALTER TABLE appointments
    ADD CONSTRAINT appointments_reason_length_check
    CHECK (char_length(btrim(reason)) <= 500);

-- CENTRAL ----------------------------------------------------------------------

ALTER TABLE hospitals
    ADD CONSTRAINT hospitals_clinical_endpoint_length_check
    CHECK (clinical_endpoint IS NULL OR char_length(btrim(clinical_endpoint)) <= 500);

ALTER TABLE central_users
    ADD CONSTRAINT central_users_display_name_length_check
    CHECK (char_length(btrim(display_name)) <= 200);

-- Reversion --------------------------------------------------------------------

-- ALTER TABLE local_patients DROP CONSTRAINT local_patients_contact_length_check;
-- ALTER TABLE allergies DROP CONSTRAINT allergies_reaction_length_check;
-- ALTER TABLE clinical_notes DROP CONSTRAINT clinical_notes_content_length_check;
-- ALTER TABLE care_episodes DROP CONSTRAINT care_episodes_reason_length_check;
-- ALTER TABLE bed_stays DROP CONSTRAINT bed_stays_reason_length_check;
-- ALTER TABLE appointments DROP CONSTRAINT appointments_reason_length_check;
-- ALTER TABLE hospitals DROP CONSTRAINT hospitals_clinical_endpoint_length_check;
-- ALTER TABLE central_users DROP CONSTRAINT central_users_display_name_length_check;

-- Pruebas requeridas: texto sobre el limite rechazado; filas existentes intactas; down reversible.
