-- Médica tratante (dra.lopez) con asignación activa y médico con asignación cerrada (dr.perez) en ep1, con el expediente abierto del paciente.
INSERT INTO doctor_profiles VALUES ('p-dra.lopez', 'dra.lopez'), ('p-dr.perez', 'dr.perez');
INSERT INTO care_episodes VALUES ('ep1', 'pac1');
INSERT INTO medical_records (medical_record_id, local_patient_id) VALUES ('mr1', 'pac1');
INSERT INTO care_assignments (care_episode_id, doctor_profile_id, status, valid_to) VALUES ('ep1', 'p-dra.lopez', 'active', NULL), ('ep1', 'p-dr.perez', 'closed', '2025-01-01 00:00:00');
