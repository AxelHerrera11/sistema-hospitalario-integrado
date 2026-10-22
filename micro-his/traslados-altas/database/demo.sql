-- Datos FICTICIOS para el recorrido de consola sobre una base HOSPITAL desechable ya migrada.
-- Un episodio hospitalario abierto con su estancia en la cama A; la cama B libre; la cama C en mantenimiento.
-- Ejecútelo una sola vez por base: bed_stays no admite borrado (bed_stays_delete_protection).
BEGIN;
INSERT INTO local_user_refs (local_user_ref_id, central_user_id, membership_id, display_name, role_code, synced_at)
VALUES ('00000000-0000-4000-8000-0000000000f1', gen_random_uuid(), gen_random_uuid(), 'Enfermera de demostración', 'enfermeria', now());
INSERT INTO wards (ward_id, code, name, location) VALUES ('00000000-0000-4000-8000-0000000000a0', 'DEMO-TA', 'Sala de demostración', 'Piso 1');
INSERT INTO beds (bed_id, ward_id, code, type, status) VALUES
    ('00000000-0000-4000-8000-0000000000b1', '00000000-0000-4000-8000-0000000000a0', 'A', 'general', 'available'),
    ('00000000-0000-4000-8000-0000000000b2', '00000000-0000-4000-8000-0000000000a0', 'B', 'general', 'available'),
    ('00000000-0000-4000-8000-0000000000b3', '00000000-0000-4000-8000-0000000000a0', 'C', 'general', 'maintenance');
INSERT INTO local_patients (local_patient_id, global_id, code, given_name, family_name, birth_date, sex)
VALUES ('00000000-0000-4000-8000-0000000000d1', gen_random_uuid(), 'DEMO-TA-1', 'Paciente', 'Ficticio', '1990-01-01', 'unknown');
INSERT INTO care_episodes (care_episode_id, local_patient_id, type, started_at, reason)
VALUES ('00000000-0000-4000-8000-0000000000c1', '00000000-0000-4000-8000-0000000000d1', 'inpatient', now() - interval '2 days', 'Ingreso de demostración');
INSERT INTO bed_stays (bed_stay_id, care_episode_id, bed_id, assigned_by_user_ref_id, started_at, reason)
VALUES ('00000000-0000-4000-8000-0000000000e1', '00000000-0000-4000-8000-0000000000c1', '00000000-0000-4000-8000-0000000000b1',
        '00000000-0000-4000-8000-0000000000f1', now() - interval '1 day', 'Ingreso');
COMMIT;
