# Datos ficticios extra para ASII-21: bioquímico y jefatura médica, perfil y asignación de cuidado real de la médica, episodios y la prueba GLU.
# Uso (desde la raíz del repositorio, con las variables E2E del job de CI): bash datos-e2e-alertas.sh
S=$(dirname "$0")
for par in bioquimico.e2e@hospital-ficticio.test:bioquimico jefatura.e2e@hospital-ficticio.test:jefatura_medica; do
  U=$(php $S/agregar-usuario-e2e.php ${par%%:*} ${par##*:})
  psql -q -d his_hospital_e2e -c "INSERT INTO local_user_refs (central_user_id, membership_id, display_name, role_code, synced_at) VALUES ('$U', gen_random_uuid(), '${par##*:} ficticio', '${par##*:}', now())"
done
psql -q -d his_hospital_e2e <<'SQL'
WITH m AS (SELECT local_user_ref_id FROM local_user_refs WHERE role_code = 'medico'),
     p AS (INSERT INTO doctor_profiles (local_user_ref_id, license_number, valid_from) SELECT local_user_ref_id, 'MED-E2E', current_date - 1 FROM m RETURNING doctor_profile_id),
     s AS (INSERT INTO specialties (code, name) VALUES ('MI-E2E', 'Medicina interna') RETURNING specialty_id),
     d AS (INSERT INTO doctor_specialties (doctor_profile_id, specialty_id, is_primary, valid_from) SELECT doctor_profile_id, specialty_id, true, current_date - 1 FROM p, s RETURNING doctor_profile_id, specialty_id),
     e AS (INSERT INTO care_episodes (local_patient_id, type, started_at, reason) SELECT local_patient_id, 'inpatient', now() - interval '1 hour', 'Ingreso'
           FROM local_patients WHERE code IN ('PAC-E2E-01', 'PAC-E2E-02') RETURNING care_episode_id, local_patient_id)
INSERT INTO care_assignments (care_episode_id, doctor_profile_id, specialty_id, assigned_by_user_ref_id, scope, reason, source, valid_from)
SELECT e.care_episode_id, d.doctor_profile_id, d.specialty_id, m.local_user_ref_id, '{"labs":true}'::jsonb, 'Médica tratante', 'episode', now() - interval '1 hour'
FROM e JOIN local_patients lp USING (local_patient_id), d, m WHERE lp.code = 'PAC-E2E-01';
INSERT INTO lab_tests (code, name, specimen_type, min_volume_ml, result_type, unit, reference_low, reference_high, critical_low, critical_high, version_no, valid_from, created_by_user_ref_id)
SELECT 'GLU', 'Glucosa', 'serum', 0.5, 'numeric', 'mg/dL', 70, 100, 40, 400, 1, current_date, local_user_ref_id FROM local_user_refs WHERE role_code = 'superadmin';
SQL
