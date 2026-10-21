-- Seeder de prueba propuesto para el modulo de verificacion PostgreSQL (Semana 9).
-- Datos FICTICIOS: no hay datos reales de pacientes, credenciales ni secretos.
-- Marcas de tiempo FIJAS: con now() el estado final depende del instante de ejecucion
-- y dos sentencias separadas pueden violar los triggers de coherencia de periodo.
-- Uso: base HOSPITAL de pruebas, sobre el esquema ya migrado.
-- Las entidades que dependen de local_user_refs requieren un usuario local previo.

-- 0) Usuario local de prueba (referencias logicas a CENTRAL, sin FK cruzada).
INSERT INTO local_user_refs (local_user_ref_id, central_user_id, membership_id, display_name, role_code, status, synced_at)
VALUES ('77777777-7777-4777-8777-777777777771', '88888888-8888-4888-8888-888888888881', '88888888-8888-4888-8888-888888888882', 'Personal de prueba', 'enfermeria', 'active', TIMESTAMPTZ '2026-10-01 07:00:00+00')
ON CONFLICT DO NOTHING;

-- 1) Sala y camas (datos minimos para validar ocupacion y asignacion).
INSERT INTO wards (ward_id, code, name, location, status)
VALUES ('11111111-1111-4111-8111-111111111111', 'P-URG-1', 'Urgencias 1', 'Planta baja - Ala A', 'active'),
       ('11111111-1111-4111-8111-111111111112', 'P-UC-1', 'Unidad de cuidados 1', 'Planta 2 - Ala B', 'active')
ON CONFLICT DO NOTHING;

INSERT INTO beds (bed_id, ward_id, code, type, status)
VALUES ('22222222-2222-4222-8222-222222222221', '11111111-1111-4111-8111-111111111111', 'U1-01', 'standard', 'available'),
       ('22222222-2222-4222-8222-222222222222', '11111111-1111-4111-8111-111111111111', 'U1-02', 'standard', 'maintenance'),
       ('22222222-2222-4222-8222-222222222223', '11111111-1111-4111-8111-111111111112', 'UC1-01', 'standard', 'available')
ON CONFLICT DO NOTHING;

-- 2) Pacientes ficticios (codigo y global_id deterministas, sin datos de contacto reales).
INSERT INTO local_patients (local_patient_id, global_id, code, given_name, family_name, birth_date, sex, status, contact)
VALUES ('33333333-3333-4333-8333-333333333331', '44444444-4444-4444-8444-444444444431', 'PAC-0001', 'Ana', 'Prueba', '1985-04-12', 'female', 'active', 'contacto-de-prueba@example.test'),
       ('33333333-3333-4333-8333-333333333332', '44444444-4444-4444-8444-444444444432', 'PAC-0002', 'Luis', 'Prueba', '1979-11-03', 'male', 'active', NULL),
       ('33333333-3333-4333-8333-333333333333', '44444444-4444-4444-8444-444444444433', 'PAC-0003', 'Iris', 'Prueba', '1965-07-25', 'female', 'deceased', NULL)
ON CONFLICT DO NOTHING;

-- 3) Episodios de cuidado: abierto, cerrado y cancelado (cobertura de estados).
INSERT INTO care_episodes (care_episode_id, local_patient_id, type, status, started_at, ended_at, reason, service_code)
VALUES ('55555555-5555-4555-8555-555555555551', '33333333-3333-4333-8333-333333333331', 'outpatient', 'open', TIMESTAMPTZ '2026-10-01 08:00:00+00', NULL, 'Consulta de control', 'C-EXT'),
       ('55555555-5555-4555-8555-555555555552', '33333333-3333-4333-8333-333333333332', 'inpatient', 'closed', TIMESTAMPTZ '2026-10-05 08:00:00+00', TIMESTAMPTZ '2026-10-12 08:00:00+00', 'Ingreso por observacion', 'C-INT'),
       ('55555555-5555-4555-8555-555555555553', '33333333-3333-4333-8333-333333333333', 'emergency', 'cancelled', TIMESTAMPTZ '2026-10-06 08:00:00+00', TIMESTAMPTZ '2026-10-06 08:00:00+00' + interval '2 hours', 'Ingreso cancelado', NULL)
ON CONFLICT DO NOTHING;

-- 4) Estancias: abierta y cerrada, para validar ocupacion en el corte.
INSERT INTO bed_stays (bed_stay_id, bed_id, care_episode_id, assigned_by_user_ref_id, started_at, ended_at, reason)
SELECT '66666666-6666-4666-8666-666666666661', '22222222-2222-4222-8222-222222222221',
       '55555555-5555-4555-8555-555555555551', local_user_ref_id, TIMESTAMPTZ '2026-10-18 08:00:00+00', NULL, 'Estancia en prueba'
FROM local_user_refs LIMIT 1;

INSERT INTO bed_stays (bed_stay_id, bed_id, care_episode_id, assigned_by_user_ref_id, started_at, ended_at, reason)
SELECT '66666666-6666-4666-8666-666666666662', '22222222-2222-4222-8222-222222222223',
       '55555555-5555-4555-8555-555555555552', local_user_ref_id, TIMESTAMPTZ '2026-10-05 08:00:00+00', TIMESTAMPTZ '2026-10-12 08:00:00+00', 'Estancia cerrada'
FROM local_user_refs LIMIT 1;
