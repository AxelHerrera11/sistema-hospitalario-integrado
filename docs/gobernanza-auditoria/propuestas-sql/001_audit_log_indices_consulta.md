# Propuesta SQL 001 — índices de consulta y restricción de `result` en `audit_log`

**Estado:** propuesta para Javier (responsable del esquema y las migraciones). No se ejecutó sobre ninguna base compartida, y la migración la tiene que crear Javier.
**Archivo:** [`001_audit_log_indices_consulta.sql`](001_audit_log_indices_consulta.sql)

## 1. Esquema real revisado

| Fuente | Qué contiene | Estado |
|---|---|---|
| `origin/develop` · `database/migrations/central/*` | 15 entidades CENTRAL. La única auditoría es `cross_hospital_access_audits` (append-only, por paciente MPI). | Fusionado |
| `origin/develop` · `openspec/.../hospital-clinical-schema/spec.md` | Prevé `local_access_audit` en HOSPITAL (slice H8, tarea 3.7 `000700_create_audit_and_sync_tables.php`). | **Sin migración** |
| `docs/notas-soap/semana-01/narrativa-alcance-trazabilidad.md` (Anexo A) | Propuesta de columnas de `local_access_audits` (HOSPITAL). | Propuesta de otro módulo |
| Rama `origin/feature/asii-25-semana-16-tenants-auditoria` | `audit_log` + `audit_chain_state` en CENTRAL, con cadena SHA-256 y trigger append-only. Escritor: `App\Modules\Tenants\Infrastructure\Audit\AuditLogRepository::logRaw()`. | **No fusionada** |
| `origin/develop` · `app/Rbac/AuditDenials.php` | Ya importa `App\Modules\Tenants\Domain\Audit\AuditLogRepositoryInterface` (es decir, `audit_log`). `RbacServiceProvider` todavía enlaza `LogDenials`. | Fusionado, sin activar |

**Decisión del módulo:** el adaptador PDO (`micro-his/gobernanza-auditoria/src/Persistence/PdoAuditLog.php`) lee y escribe sobre `audit_log`, que es el único esquema de auditoría general con migración escrita y el que ya usa RBAC en develop. No se crearon tablas nuevas.

## 2. Por qué hacen falta estos cambios

La consulta de gobernanza siempre filtra por `hospital_id` (el tenant del servidor) y ordena por `occurred_at DESC, event_id DESC`. Hoy `audit_log` solo tiene índices de una sola columna en `actor_id`, `hospital_id` y `trace_id`, así que:

- ordenar y paginar dentro de un tenant con muchos eventos obliga a ordenar en memoria;
- los filtros obligatorios del README (usuario, módulo y rango `desde`/`hasta`) no tienen un índice compuesto que los cubra;
- `result` admite cualquier texto, y el módulo distingue `success`, `failure` y `denied`.

## 3. Columnas, claves, índices y relaciones

| Elemento | Propuesta | Relación con el esquema actual |
|---|---|---|
| `audit_log_hospital_occurred_index` | `(hospital_id, occurred_at DESC, event_id DESC)` | Sustituye en la práctica a `audit_log_hospital_index`. |
| `audit_log_hospital_actor_occurred_index` | `(hospital_id, actor_id, occurred_at DESC)` | Cubre `audit_log_actor_index` dentro del tenant. |
| `audit_log_hospital_event_occurred_index` | `(hospital_id, event text_pattern_ops, occurred_at DESC)` | Módulo = prefijo de `event` (`modulo.accion`). |
| `audit_log_hospital_result_occurred_index` | `(hospital_id, result, occurred_at DESC)` | Filtro por resultado. |
| `audit_log_result_check` | `result IS NULL OR result IN ('success','failure','denied')`, `NOT VALID` | Admite `NULL` porque `logRaw()` no siempre envía `result`. |
| Claves y FK | Sin cambios. `event_id` sigue siendo la PK (UUID). | `actor_id` y `hospital_id` son `text` sin FK, igual que en la rama de origen. |

No se agregan columnas. `module` no se guarda aparte: se deriva de `event`, y así se evita duplicar datos que podrían desincronizarse.

## 4. Qué debe validar Javier

1. **Ubicación del esquema:** decidir si la bitácora general vive en CENTRAL (`audit_log`, que tiene RBAC y Tenants) o en HOSPITAL (`local_access_audits`, que prevé openspec H8). La especificación de CENTRAL dice que debe tener **exactamente 15 entidades**, y `audit_log` y `audit_chain_state` lo superarían. Si se elige HOSPITAL, `PdoAuditLog` se reemplaza por otro adaptador sin cambiar los casos de uso (ver Semana 7).
2. **Tipos de identificadores:** `actor_id` y `hospital_id` son `text`, mientras que el resto del esquema usa `uuid`. ¿Se migran a `uuid` (con `NOT NULL` y sin FK entre bases)?
3. **Valor `NULL` en `result`:** ¿se mantiene, o se completan las filas antiguas y se aplica `VALIDATE CONSTRAINT`?
4. **Índices existentes:** si se eliminan `audit_log_actor_index` y `audit_log_hospital_index` una vez creados los compuestos.
5. **Plan de ejecución:** comprobar con `EXPLAIN (ANALYZE, BUFFERS)` las consultas de la sección 5 con un volumen de datos representativo.
6. **Concurrencia de la cadena:** el `SELECT ... FOR UPDATE` sobre `audit_chain_state` serializa todas las escrituras de la red. Hay que validar si eso es aceptable o si conviene usar una cadena por hospital.

## 5. Consultas que generará el módulo (parametrizadas)

```sql
SELECT COUNT(*) FROM audit_log
 WHERE hospital_id = :tenant AND actor_id = :actor
   AND (event = :module_exact OR event LIKE :module_prefix ESCAPE '!')
   AND occurred_at >= :from AND occurred_at < :until;

SELECT event_id, occurred_at, event, actor_id, hospital_id, detail, trace_id, result
  FROM audit_log
 WHERE hospital_id = :tenant /* + filtros opcionales */
 ORDER BY occurred_at DESC, event_id DESC
 LIMIT :limit OFFSET :offset;
```

## 6. Qué no se hizo

- No se modificaron migraciones existentes ni de otros integrantes.
- No se ejecutó el SQL. Las pruebas usan [`micro-his/gobernanza-auditoria/database/audit_log.sqlite.sql`](../../../micro-his/gobernanza-auditoria/database/audit_log.sqlite.sql), un esquema de prueba en SQLite en memoria que solo se usa durante las pruebas.
