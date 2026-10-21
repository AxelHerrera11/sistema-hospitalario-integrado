-- Pruebas de la sincronizacion sobre el esquema CENTRAL migrado de develop.
\set ON_ERROR_STOP off

-- 0. Datos minimos: red, hospital, nodo y checkpoint del movil.
INSERT INTO networks (network_id, name, timezone)
VALUES ('11111111-1111-1111-1111-111111111111', 'RED-PRUEBA', 'America/Guatemala')
ON CONFLICT (network_id) DO NOTHING;
INSERT INTO hospitals (hospital_id, network_id, code, name, timezone)
VALUES ('22222222-2222-2222-2222-222222222222', '11111111-1111-1111-1111-111111111111',
        'H-PRUEBA', 'Hospital de prueba', 'America/Guatemala')
ON CONFLICT (hospital_id) DO NOTHING;
INSERT INTO sync_nodes (node_id, hospital_id, name, schema_version, key_version)
VALUES ('33333333-3333-3333-3333-333333333333', '22222222-2222-2222-2222-222222222222',
        'nodo-H-PRUEBA', '1', '1')
ON CONFLICT (node_id) DO NOTHING;
INSERT INTO sync_checkpoints (node_id, channel, status)
VALUES ('33333333-3333-3333-3333-333333333333', 'ocupacion', 'healthy')
ON CONFLICT (node_id, channel) DO NOTHING;

-- 1. Tres eventos: dos del mismo instante (empate) y uno fallido.
INSERT INTO central_inbox_messages
    (message_id, source_node_id, event_id, event_type, payload_minimal, received_at, available_at,
     processed_at, failed_at, status, attempts, error_code)
VALUES
 ('44444444-4444-4444-4444-444444444441', '33333333-3333-3333-3333-333333333333',
  '55555555-5555-5555-5555-555555555551', 'ocupacion.cama', '{"bed_id":"A-1"}'::jsonb,
  '2026-10-20 06:00:00+00', '2026-10-20 06:00:00+00', '2026-10-20 06:00:01+00', NULL, 'processed', 1, NULL),
 ('44444444-4444-4444-4444-444444444442', '33333333-3333-3333-3333-333333333333',
  '55555555-5555-5555-5555-555555555552', 'ocupacion.cama', '{"bed_id":"A-2"}'::jsonb,
  '2026-10-20 06:00:00+00', '2026-10-20 06:00:00+00', NULL, NULL, 'pending', 0, NULL),
 ('44444444-4444-4444-4444-444444444443', '33333333-3333-3333-3333-333333333333',
  '55555555-5555-5555-5555-555555555553', 'ocupacion.cama', '{"bed_id":"A-3"}'::jsonb,
  '2026-10-20 06:05:00+00', '2026-10-20 06:05:00+00', NULL, '2026-10-20 06:05:02+00', 'failed', 3, 'TIMEOUT_RECEPCION'),
 ('44444444-4444-4444-4444-444444444444', '33333333-3333-3333-3333-333333333333',
  '55555555-5555-5555-5555-555555555554', 'ocupacion.cama', '{"bed_id":"A-4"}'::jsonb,
  '2026-10-20 06:10:00+00', '2026-10-21 08:00:00+00', NULL, NULL, 'pending', 0, NULL)
ON CONFLICT (source_node_id, event_id) DO NOTHING;

-- 2. Primera sincronizacion: el checkpoint esta vacio y devuelve lo ya disponible.
SELECT m.event_id, m.received_at, m.status, m.attempts, m.error_code
FROM central_inbox_messages m
JOIN sync_nodes n ON n.node_id = m.source_node_id
JOIN sync_checkpoints c ON c.node_id = n.node_id AND c.channel = 'ocupacion'
WHERE n.hospital_id = '22222222-2222-2222-2222-222222222222'
  AND m.available_at <= now()
  AND (c.last_occurred_at IS NULL
       OR (m.received_at, m.event_id) > (c.last_occurred_at, COALESCE(c.last_event_id, '00000000-0000-0000-0000-000000000000'::uuid)))
ORDER BY m.received_at, m.event_id
LIMIT 200;

-- 3. Avance del checkpoint con el ultimo evento devuelto (06:00:00, ...552).
UPDATE sync_checkpoints
SET last_event_id = '55555555-5555-5555-5555-555555555552',
    last_occurred_at = '2026-10-20 06:00:00+00',
    lag_seconds = 0,
    status = 'healthy',
    updated_at = now()
WHERE node_id = '33333333-3333-3333-3333-333333333333' AND channel = 'ocupacion';

-- 4. Segunda sincronizacion: solo devuelve lo posterior al checkpoint.
SELECT m.event_id, m.received_at, m.status
FROM central_inbox_messages m
JOIN sync_nodes n ON n.node_id = m.source_node_id
JOIN sync_checkpoints c ON c.node_id = n.node_id AND c.channel = 'ocupacion'
WHERE n.hospital_id = '22222222-2222-2222-2222-222222222222'
  AND m.available_at <= now()
  AND (c.last_occurred_at IS NULL
       OR (m.received_at, m.event_id) > (c.last_occurred_at, COALESCE(c.last_event_id, '00000000-0000-0000-0000-000000000000'::uuid)))
ORDER BY m.received_at, m.event_id
LIMIT 200;

-- 5. Paginacion de uno en uno: cada evento se entrega una sola vez.
DO $$
DECLARE
    v_ts timestamptz; v_event uuid; v_paginas integer := 0; v_entregados uuid[] := '{}';
BEGIN
    v_ts := NULL;
    LOOP
        SELECT m.received_at, m.event_id INTO v_ts, v_event
        FROM central_inbox_messages m
        JOIN sync_nodes n ON n.node_id = m.source_node_id
        WHERE n.hospital_id = '22222222-2222-2222-2222-222222222222'
          AND m.available_at <= now()
          AND (v_ts IS NULL OR (m.received_at, m.event_id) > (v_ts, v_event))
        ORDER BY m.received_at, m.event_id LIMIT 1;
        EXIT WHEN v_event IS NULL;
        v_paginas := v_paginas + 1;
        v_entregados := v_entregados || v_event;
        RAISE NOTICE 'pagina % -> evento %', v_paginas, v_event;
    END LOOP;
    RAISE NOTICE 'paginas=% entregados=% distintos=%', v_paginas, array_length(v_entregados, 1),
        (SELECT count(DISTINCT x) FROM unnest(v_entregados) AS x);
END $$;

-- 6. Reintento del mismo evento con otro message_id: la clave UNIQUE lo rechaza.
INSERT INTO central_inbox_messages
    (message_id, source_node_id, event_id, event_type, payload_minimal, received_at, available_at, status, attempts)
VALUES
 ('66666666-6666-6666-6666-666666666666', '33333333-3333-3333-3333-333333333333',
  '55555555-5555-5555-5555-555555555553', 'ocupacion.cama', '{"bed_id":"A-3"}'::jsonb,
  '2026-10-20 06:20:00+00', '2026-10-20 06:20:00+00', 'pending', 0);

-- 7. Reintento real: el mismo mensaje incrementa attempts y no crea otra fila.
UPDATE central_inbox_messages
SET status = 'pending', attempts = attempts + 1, failed_at = NULL, error_code = NULL
WHERE event_id = '55555555-5555-5555-5555-555555555553';
SELECT count(*) AS filas_del_evento_553, max(attempts) AS intentos
FROM central_inbox_messages WHERE event_id = '55555555-5555-5555-5555-555555555553';

-- 8. Limpieza de los datos de prueba.
DELETE FROM central_inbox_messages WHERE source_node_id = '33333333-3333-3333-3333-333333333333';
DELETE FROM sync_checkpoints WHERE node_id = '33333333-3333-3333-3333-333333333333';
DELETE FROM sync_nodes WHERE node_id = '33333333-3333-3333-3333-333333333333';
DELETE FROM hospitals WHERE hospital_id = '22222222-2222-2222-2222-222222222222';
DELETE FROM networks WHERE network_id = '11111111-1111-1111-1111-111111111111';
