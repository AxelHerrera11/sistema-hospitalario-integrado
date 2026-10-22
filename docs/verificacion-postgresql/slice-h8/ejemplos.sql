-- Ejemplos ejecutables del slice H8 (ASII-09, módulo de verificación de BD)
-- Uso: psql -d his_hospital_test -f ejemplos.sql
-- Requiere el grafo mínimo: local_user_refs, local_patients y care_episodes.

-- 1. Registrar un signo vital: auditoría local y evento de salida en la MISMA transacción.
BEGIN;

INSERT INTO local_access_audits (
    correlation_id, actor_user_ref_id, module, action, resource_type,
    resource_id, local_patient_id, care_episode_id, decision, metadata
) VALUES (
    '11111111-1111-4111-8111-111111111111',
    '00000000-0000-0000-0000-000000000001',
    'signos_vitales', 'signo_vital.registrado', 'vital_sign',
    '00000000-0000-0000-0000-0000000000e1',
    '00000000-0000-0000-0000-000000000002',
    '00000000-0000-0000-0000-000000000007',
    'allowed',
    '{"trace_id": "trace-a1b2c3"}'::jsonb
);

INSERT INTO outbox_events (
    event_id, correlation_id, aggregate_type, aggregate_id, event_type, payload_minimal
) VALUES (
    '22222222-2222-4222-8222-222222222222',
    '11111111-1111-4111-8111-111111111111',
    'vital_sign',
    '00000000-0000-0000-0000-0000000000e1',
    'signo_vital.alerta_generada',
    '{"vital_sign_id": "00000000-0000-0000-0000-0000000000e1"}'::jsonb
);

COMMIT;

-- 2. Consultar la traza completa de una petición (auditoría + evento por correlation_id).
SELECT audit_event_id, module, action, decision, occurred_at, metadata
FROM local_access_audits
WHERE correlation_id = '11111111-1111-4111-8111-111111111111';

SELECT event_id, event_type, status, occurred_at, published_at
FROM outbox_events
WHERE correlation_id = '11111111-1111-4111-8111-111111111111';

-- 3. Publicador: tomar los eventos pendientes en orden y marcarlos como publicados.
--    Solo se actualizan status, published_at, attempts y error_code; el payload es inmutable.
SELECT event_id, event_type, payload_minimal, attempts
FROM outbox_events
WHERE status = 'pending'
ORDER BY occurred_at
FOR UPDATE SKIP LOCKED;

UPDATE outbox_events
SET status = 'published', published_at = now(), attempts = attempts + 1
WHERE event_id = '22222222-2222-4222-8222-222222222222';

-- 4. Idempotencia de la recepción: el mismo event_id se rechaza con 23505.
INSERT INTO inbox_messages (event_id, event_type)
VALUES ('33333333-3333-4333-8333-333333333333', 'vital_sign.confirmado');
-- El reintento del mismo INSERT devuelve: ERROR: duplicate key value violates unique constraint
-- "inbox_messages_event_id_unique" (SQLSTATE 23505). En la aplicación se trata como idempotencia.

-- 5. Procesar y fallar mensajes de la bandeja.
UPDATE inbox_messages
SET status = 'processed', processed_at = now(), attempts = attempts + 1
WHERE event_id = '33333333-3333-4333-8333-333333333333';

UPDATE inbox_messages
SET status = 'failed', failed_at = now(), attempts = attempts + 1, error_code = 'TRIGGER_MISMATCH'
WHERE event_id = '33333333-3333-4333-8333-333333333333';

-- 6. La auditoría es de solo inserción: estas dos órdenes deben fallar con P0001.
-- UPDATE local_access_audits SET action = 'signo_vital.editado';
-- DELETE FROM local_access_audits;
