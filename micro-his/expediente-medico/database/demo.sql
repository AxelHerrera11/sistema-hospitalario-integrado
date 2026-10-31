-- DATOS FICTICIOS del prototipo y de las pruebas (personas inventadas). Solo se cargan en SQLite locales creadas por
-- public/index.php o tests/; nunca en una base compartida. Identificadores con forma UUID, como en la base real.
-- Usuarios (local_user_refs): dra. López y dr. Pérez (medico), Ruiz (enfermeria), Mora (recepcion), Sol (admin_hospital).
INSERT INTO local_user_refs (local_user_ref_id, central_user_id, membership_id, display_name, role_code, synced_at) VALUES
    ('11111111-1111-4111-8111-111111111111', 'c1111111-1111-4111-8111-111111111111', 'b1111111-1111-4111-8111-111111111111', 'Dra. Andrea López', 'medico', '2026-10-01 00:00:00+00:00'),
    ('22222222-2222-4222-8222-222222222222', 'c2222222-2222-4222-8222-222222222222', 'b2222222-2222-4222-8222-222222222222', 'Dr. Bruno Pérez', 'medico', '2026-10-01 00:00:00+00:00'),
    ('33333333-3333-4333-8333-333333333333', 'c3333333-3333-4333-8333-333333333333', 'b3333333-3333-4333-8333-333333333333', 'Enf. Carla Ruiz', 'enfermeria', '2026-10-01 00:00:00+00:00'),
    ('44444444-4444-4444-8444-444444444444', 'c4444444-4444-4444-8444-444444444444', 'b4444444-4444-4444-8444-444444444444', 'Diego Mora', 'recepcion', '2026-10-01 00:00:00+00:00'),
    ('55555555-5555-4555-8555-555555555555', 'c5555555-5555-4555-8555-555555555555', 'b5555555-5555-4555-8555-555555555555', 'Elena Sol', 'admin_hospital', '2026-10-01 00:00:00+00:00');
INSERT INTO doctor_profiles (doctor_profile_id, local_user_ref_id, license_number, valid_from) VALUES
    ('d1111111-1111-4111-8111-111111111111', '11111111-1111-4111-8111-111111111111', 'COL-10001', '2026-01-01'),
    ('d2222222-2222-4222-8222-222222222222', '22222222-2222-4222-8222-222222222222', 'COL-10002', '2026-01-01');
-- P-0001 con expediente y tres episodios; P-0002 activo sin expediente; P-0003 fallecido sin expediente.
INSERT INTO local_patients (local_patient_id, global_id, code, given_name, family_name, birth_date, sex, status) VALUES
    ('a0000000-0000-4000-8000-000000000001', 'f0000000-0000-4000-8000-000000000001', 'P-0001', 'Ana', 'Gómez', '1980-05-14', 'female', 'active'),
    ('a0000000-0000-4000-8000-000000000002', 'f0000000-0000-4000-8000-000000000002', 'P-0002', 'Luis', 'Pérez', '1992-11-03', 'male', 'active'),
    ('a0000000-0000-4000-8000-000000000003', 'f0000000-0000-4000-8000-000000000003', 'P-0003', 'Marta', 'Ríos', '1941-02-20', 'female', 'deceased');
INSERT INTO medical_records (medical_record_id, local_patient_id, record_number, opened_at) VALUES
    ('e0000000-0000-4000-8000-000000000001', 'a0000000-0000-4000-8000-000000000001', 'EXP-P-0001', '2026-03-02 09:00:00+00:00');
INSERT INTO care_episodes (care_episode_id, local_patient_id, type, status, started_at, ended_at, reason, service_code) VALUES
    ('0e000000-0000-4000-8000-000000000001', 'a0000000-0000-4000-8000-000000000001', 'outpatient', 'closed', '2026-03-02 09:00:00+00:00', '2026-03-02 10:00:00+00:00', 'Control de presión arterial', 'CONS-EXT'),
    ('0e000000-0000-4000-8000-000000000002', 'a0000000-0000-4000-8000-000000000001', 'emergency', 'closed', '2026-07-14 22:10:00+00:00', '2026-07-15 03:30:00+00:00', 'Dolor torácico', 'EMERG'),
    ('0e000000-0000-4000-8000-000000000003', 'a0000000-0000-4000-8000-000000000001', 'inpatient', 'open', '2026-10-20 08:00:00+00:00', NULL, 'Neumonía en observación', 'MED-INT'),
    ('0e000000-0000-4000-8000-000000000004', 'a0000000-0000-4000-8000-000000000002', 'outpatient', 'open', '2026-10-22 14:00:00+00:00', NULL, 'Primera consulta', 'CONS-EXT');
-- La dra. López atiende los episodios abiertos de P-0001 y P-0002. La asignación del dr. Pérez a P-0001 ya terminó.
INSERT INTO care_assignments (care_assignment_id, care_episode_id, doctor_profile_id, specialty_id, assigned_by_user_ref_id, scope, reason, source, status, valid_from, valid_to) VALUES
    ('ca000000-0000-4000-8000-000000000001', '0e000000-0000-4000-8000-000000000003', 'd1111111-1111-4111-8111-111111111111', 'sp-medicina-interna', '44444444-4444-4444-8444-444444444444', '{"care":"attending"}', 'Médica tratante', 'episode', 'active', '2026-10-20 08:00:00+00:00', NULL),
    ('ca000000-0000-4000-8000-000000000002', '0e000000-0000-4000-8000-000000000004', 'd1111111-1111-4111-8111-111111111111', 'sp-medicina-interna', '44444444-4444-4444-8444-444444444444', '{"care":"attending"}', 'Médica tratante', 'appointment', 'active', '2026-10-22 14:00:00+00:00', NULL),
    ('ca000000-0000-4000-8000-000000000003', '0e000000-0000-4000-8000-000000000002', 'd2222222-2222-4222-8222-222222222222', 'sp-urgencias', '44444444-4444-4444-8444-444444444444', '{"care":"attending"}', 'Guardia de urgencias', 'emergency', 'closed', '2026-07-14 22:10:00+00:00', '2026-07-15 03:30:00+00:00');
