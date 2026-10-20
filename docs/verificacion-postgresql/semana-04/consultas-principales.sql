-- Consultas principales del repositorio de ocupacion hospitalaria (Semana 4).
-- Todas usan la conexion 'hospital' y el tenant ya resuelto por el middleware.
-- Parametros: :cutoff = instante de corte ISO-8601 con zona horaria.

-- 1. Fotografia de ocupacion por sala y estado de cama.
--    Una sola sentencia garantiza la foto consistente: beds y bed_stays se leen juntos.
SELECT b.bed_id,
       w.ward_id,
       w.code AS ward_code,
       b.status,
       EXISTS (
           SELECT 1
           FROM bed_stays s
           WHERE s.bed_id = b.bed_id
             AND s.started_at <= :cutoff
             AND (s.ended_at IS NULL OR s.ended_at > :cutoff)
       ) AS has_active_stay
FROM beds b
JOIN wards w ON w.ward_id = b.ward_id
ORDER BY w.code, b.code;

-- 2. Inconsistencias entre el intervalo activo y el estado operativo de la cama.
--    El dashboard las muestra; el modulo de BD no las corrige.
SELECT b.bed_id,
       b.code AS bed_code,
       b.status AS operational_status,
       s.started_at,
       s.ended_at
FROM beds b
JOIN bed_stays s ON s.bed_id = b.bed_id
WHERE s.started_at <= :cutoff
  AND (s.ended_at IS NULL OR s.ended_at > :cutoff)
  AND b.status <> 'occupied';

-- 3. Estancia abierta de un episodio de cuidado.
--    El indice unico bed_stays_open_care_episode_unique impide dos estancias abiertas.
SELECT bed_stay_id,
       bed_id,
       care_episode_id,
       started_at
FROM bed_stays
WHERE care_episode_id = :care_episode_id
  AND ended_at IS NULL;

-- 4. Camas operativas por sala para el denominador de la tasa de ocupacion.
SELECT w.code AS ward_code,
       count(*) AS total_beds,
       count(*) FILTER (WHERE b.status IN ('maintenance', 'inactive')) AS out_of_service
FROM beds b
JOIN wards w ON w.ward_id = b.ward_id
GROUP BY w.code
ORDER BY w.code;

-- 5. Coherencia episodio-paciente usada por el trigger enforce_clinical_note_integrity.
--    La misma forma de JOIN valida que una nota pertenezca al episodio del paciente.
SELECT ce.care_episode_id
FROM care_episodes ce
JOIN local_patients lp ON lp.local_patient_id = ce.local_patient_id
WHERE ce.care_episode_id = :care_episode_id
  AND lp.local_patient_id = :local_patient_id;
