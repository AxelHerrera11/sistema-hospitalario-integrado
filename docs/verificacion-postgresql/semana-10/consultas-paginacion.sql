-- Consultas de paginacion y sincronizacion para clientes moviles (Semana 10).
-- Parametros: :cutoff (corte ISO-8601), :ultimo_ward_code, :ultimo_bed_code,
--              :node_id, :channel, :hospital_id.

-- 1. Primera pagina de la fotografia de ocupacion (25 filas por defecto).
SELECT b.bed_id,
       w.code AS ward_code,
       b.code AS bed_code,
       b.status,
       EXISTS (
           SELECT 1 FROM bed_stays s
           WHERE s.bed_id = b.bed_id
             AND s.started_at <= :cutoff
             AND (s.ended_at IS NULL OR s.ended_at > :cutoff)
       ) AS has_active_stay
FROM beds b
JOIN wards w ON w.ward_id = b.ward_id
ORDER BY w.code, b.code
LIMIT 25;

-- 2. Pagina siguiente: se reanuda desde la ultima clave devuelta (keyset, sin OFFSET).
SELECT b.bed_id,
       w.code AS ward_code,
       b.code AS bed_code,
       b.status,
       EXISTS (
           SELECT 1 FROM bed_stays s
           WHERE s.bed_id = b.bed_id
             AND s.started_at <= :cutoff
             AND (s.ended_at IS NULL OR s.ended_at > :cutoff)
       ) AS has_active_stay
FROM beds b
JOIN wards w ON w.ward_id = b.ward_id
WHERE (w.code, b.code) > (:ultimo_ward_code, :ultimo_bed_code)
ORDER BY w.code, b.code
LIMIT 25;

-- 3. Sincronizacion desde el ultimo checkpoint del movil.
SELECT m.event_id,
       m.entity_type,
       m.entity_id,
       m.operation,
       m.occurred_at,
       m.sequence
FROM central_inbox_messages m
JOIN sync_checkpoints c
  ON c.node_id = :node_id
 AND c.channel = m.channel
 AND c.last_sequence < m.sequence
WHERE m.hospital_id = :hospital_id
ORDER BY m.sequence
LIMIT 200;

-- 4. Cursor de la siguiente pagina (el ultimo par devuelto).
SELECT w.code AS ward_code, b.code AS bed_code
FROM beds b
JOIN wards w ON w.ward_id = b.ward_id
WHERE (w.code, b.code) > (:ultimo_ward_code, :ultimo_bed_code)
ORDER BY w.code, b.code
LIMIT 1 OFFSET 24;
