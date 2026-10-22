-- =====================================================================
-- PROPUESTA 001 — Gobernanza y Auditoría
-- Índices y restricción de resultado para consultar audit_log (CENTRAL)
-- =====================================================================
-- ESTADO: PROPUESTA. NO EJECUTAR sobre ninguna base compartida.
-- Responsable de validar y convertir en migración: Javier (esquema y
-- migraciones del grupo). Ver 001_audit_log_indices_consulta.md.
--
-- Depende de que existan en CENTRAL las tablas creadas por:
--   database/migrations/central/2026_10_19_000200_create_audit_log.php
--   database/migrations/central/2026_10_20_000200_enrich_audit_log.php
-- (rama feature/asii-25-semana-16-tenants-auditoria, aún no fusionadas en develop).
-- =====================================================================

-- 1) Consulta base de gobernanza: tenant + orden cronológico inverso con
--    desempate estable por event_id (ORDER BY occurred_at DESC, event_id DESC).
CREATE INDEX audit_log_hospital_occurred_index
    ON audit_log (hospital_id, occurred_at DESC, event_id DESC);

-- 2) Filtro obligatorio por usuario dentro del tenant y rango de fechas.
CREATE INDEX audit_log_hospital_actor_occurred_index
    ON audit_log (hospital_id, actor_id, occurred_at DESC);

-- 3) Filtro por módulo/acción: event = 'modulo.accion'. text_pattern_ops
--    permite usar el índice con LIKE 'modulo.%' sin depender de la collation.
CREATE INDEX audit_log_hospital_event_occurred_index
    ON audit_log (hospital_id, event text_pattern_ops, occurred_at DESC);

-- 4) Filtro por resultado (success / failure / denied).
CREATE INDEX audit_log_hospital_result_occurred_index
    ON audit_log (hospital_id, result, occurred_at DESC);

-- 5) Restricción de valores de result. Admite NULL porque las filas escritas
--    por logRaw() sin 'result' (p. ej. App\Rbac\AuditDenials) lo dejan vacío.
--    NOT VALID evita bloquear la tabla revisando filas antiguas; validar después
--    con: ALTER TABLE audit_log VALIDATE CONSTRAINT audit_log_result_check;
ALTER TABLE audit_log
    ADD CONSTRAINT audit_log_result_check
    CHECK (result IS NULL OR result IN ('success', 'failure', 'denied')) NOT VALID;

-- Los índices existentes audit_log_actor_index y audit_log_hospital_index
-- quedan cubiertos por los índices 1 y 2; Javier decide si se eliminan.
-- audit_log_trace_index (trace_id) se conserva para el filtro de correlación.

-- Reversión (en orden inverso):
-- ALTER TABLE audit_log DROP CONSTRAINT IF EXISTS audit_log_result_check;
-- DROP INDEX IF EXISTS audit_log_hospital_result_occurred_index;
-- DROP INDEX IF EXISTS audit_log_hospital_event_occurred_index;
-- DROP INDEX IF EXISTS audit_log_hospital_actor_occurred_index;
-- DROP INDEX IF EXISTS audit_log_hospital_occurred_index;
