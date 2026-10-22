-- Consultas de paginacion y sincronizacion para clientes moviles (Semana 10).
-- Parametros: :cutoff (corte ISO-8601), :ultimo_ward_code, :ultimo_bed_code,
--              :channel, :hospital_id.
-- Conexion: 1, 2 y 4 en HOSPITAL (beds, wards, bed_stays); 3, 3b y 3c en CENTRAL.
-- Correccion 2026-10-20: la consulta 3 usa solo columnas que existen en el esquema
-- migrado de develop; la version anterior referenciaba channel, sequence,
-- entity_type, entity_id, operation, occurred_at y last_sequence, que no existen.

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
-- Cursor real: (received_at, event_id) del ultimo mensaje consumido.
-- El checkpoint guarda ese par en (last_occurred_at, last_event_id). La bandeja no
-- tiene columna occurred_at: received_at es su proxy y el criterio de orden es una
-- decision pendiente del grupo (ver README, seccion 7).
SELECT m.message_id,
       m.event_id,
       m.event_type,
       m.received_at,
       m.available_at,
       m.status,
       m.attempts,
       m.error_code,
       m.payload_minimal
FROM central_inbox_messages m
JOIN sync_nodes n ON n.node_id = m.source_node_id
JOIN sync_checkpoints c
  ON c.node_id = n.node_id
 AND c.channel = :channel
WHERE n.hospital_id = :hospital_id
  AND m.available_at <= now()
  AND (c.last_occurred_at IS NULL
       OR (m.received_at, m.event_id) > (c.last_occurred_at,
             COALESCE(c.last_event_id, '00000000-0000-0000-0000-000000000000'::uuid)))
ORDER BY m.received_at, m.event_id
LIMIT 200;

-- 3b. Avance del checkpoint con el ultimo evento devuelto por la consulta 3.
-- El indice UNIQUE (node_id, channel) garantiza un solo checkpoint por canal.
UPDATE sync_checkpoints
SET last_event_id = :ultimo_event_id,
    last_occurred_at = :ultimo_received_at,
    lag_seconds = GREATEST(0, EXTRACT(EPOCH FROM (now() - :ultimo_received_at))::bigint),
    status = 'healthy',
    updated_at = now()
WHERE node_id = :node_id
  AND channel = :channel;

-- 3c. Alta del checkpoint la primera vez que el movil sincroniza.
INSERT INTO sync_checkpoints (node_id, channel, last_event_id, last_occurred_at, status)
VALUES (:node_id, :channel, NULL, NULL, 'healthy')
ON CONFLICT (node_id, channel) DO NOTHING;

-- 4. Cursor de la siguiente pagina (el ultimo par devuelto).
SELECT w.code AS ward_code, b.code AS bed_code
FROM beds b
JOIN wards w ON w.ward_id = b.ward_id
WHERE (w.code, b.code) > (:ultimo_ward_code, :ultimo_bed_code)
ORDER BY w.code, b.code
LIMIT 1 OFFSET 24;
