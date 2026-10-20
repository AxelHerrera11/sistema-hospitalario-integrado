# Validación de la propuesta 001 — índices de consulta y restricción de `result` (CENTRAL)

Módulo de **Verificación de Base de Datos PostgreSQL** (ASII-09). Valida la propuesta de
Gobernanza y Auditoría sobre `audit_log` (CENTRAL) y la convierte en una migración reversible.

| Elemento | Estado |
|---|---|
| Propuesta 001 | **Validada con ejecución real** y convertida en migración correctiva |
| Migración | `database/migrations/central/2026_08_11_000900_add_audit_log_query_indexes.php` |
| Pruebas | `tests/Database/Central/AuditLogQueryIndexTest.php` (6 pruebas, 54 aserciones) |

## 1. Método de medición

- PostgreSQL 16 en contenedor, base `his_central_verify` con las migraciones reales (`develop` +
  las de auditoría y autenticación de la rama de gobernanza).
- Volumen: **200 000 filas**, 5 hospitales, 60 actores, 40 eventos distintos
  (`moduloN.accionM`), 20 000 minutos de historial.
- `ANALYZE` ejecutado y caché caliente en todas las mediciones; `EXPLAIN (ANALYZE, BUFFERS)`.
- La misma batería se ejecutó **antes** (sin los índices compuestos) y **después**, con el mismo
  volumen y las mismas estadísticas.

## 2. Resultados medidos

| Consulta | Antes | Después | Ganancia |
|---|---:|---:|---:|
| Lista paginada del tenant (`ORDER BY occurred_at DESC, event_id DESC` + `LIMIT`) | 15,342 ms | **0,126 ms** | **122×** |
| Conteo con tenant + actor + módulo + rango | 1,289 ms | **0,098 ms** | 13× |
| Conteo con tenant + actor + rango | 1,983 ms | **0,088 ms** | 23× |
| Conteo con tenant + módulo (prefijo `LIKE`) + rango | 1,271 ms | **0,087 ms** | 15× |
| Conteo con `event = exacto OR event LIKE prefijo` + rango | 1,342 ms | **0,613 ms** | 2,2× |
| Filtro por `result` + tenant + rango | 10,529 ms | **1,525 ms** | 6,9× |

El plan de la consulta paginada cambia de **«Bitmap Heap Scan + Sort (top-N heapsort) sobre 40 000
filas»** a **«Index Scan using audit_log_hospital_occurred_index»**: la ordenación deja de hacerse
en memoria y el `LIMIT` se resuelve con el índice.

Advertencia para la aplicación: la variante con `OR` (`event = exacto OR event LIKE prefijo`) es la
más lenta de todas, porque el planificador no puede usar un solo índice. Conviene **quitar el `OR`**:
como el módulo es `modulo.accion`, el prefijo `modulo.%` ya incluye el caso exacto.

## 3. Respuestas a los seis puntos de la propuesta

1. **Ubicación del esquema.** La especificación (`central-control-schema/spec.md`) exige
   *"CENTRAL SHALL contain exactly these 15 entities"* y *"Every entity SHALL use UUID keys"*.
   `audit_log` y `audit_chain_state` superan las 15, y `actor_id`/`hospital_id` en `text` contradicen
   la regla de UUID. En HOSPITAL, en cambio, la especificación ya prevé `local_access_audit`,
   `outbox_event` e `inbox_message` dentro de sus 27 entidades: la bitácora general **ya está
   especificada en HOSPITAL (slice H8)** y está implementada en el PR #581 con 14 pruebas. La
   ampliación de CENTRAL (15 → 20 entidades) necesita el acuerdo explícito del grupo, igual que
   `doctor_schedules`.
2. **Tipos de identificadores.** Todo el esquema CENTRAL usa `uuid` para las referencias lógicas
   (`central_users.user_id`, `hospitals.hospital_id`, `central_inbox_messages.source_node_id`).
   `text` en `actor_id`/`hospital_id` es una excepción: lo más coherente es `uuid` con `CHECK` de
   formato, pero el cambio afecta al escritor (`AuditLogRepository::logRaw()`) y al módulo de
   gobernanza, así que **queda como decisión de Cindy**, no se aplica desde la migración.
3. **`result` con `NULL`.** Se mantiene el `NULL` (el escritor no siempre lo envía), pero con una
   diferencia respecto a la propuesta: la constraint se **valida en cuanto los datos lo permiten**.
   La tabla es de solo inserciones (trigger `audit_log is append-only`), así que una fila que
   incumpliera el catálogo **no se podría corregir nunca** con un `UPDATE`. Por eso la migración
   añade el `CHECK` como `NOT VALID` y, en el mismo paso, lo valida si ninguna fila existente lo
   incumple; si alguna lo incumple, queda `NOT VALID` y documentado, en lugar de bloquear el
   despliegue. Verificado: `result = 'ok'` se rechaza con SQLSTATE **23514**; `NULL`, `success`,
   `failure` y `denied` se aceptan.
4. **Índices existentes.** `audit_log_actor_index` y `audit_log_hospital_index` quedan **cubiertos**
   por los compuestos para las consultas del módulo, pero **no se eliminan en esta migración**:
   siguen sirviendo a consultas que filtren sin `hospital_id`, y borrarlos exige volver a medir.
   `audit_log_trace_index` se conserva: el filtro por traza no lo cubre ningún compuesto.
5. **Plan de ejecución.** Medido y publicado en la sección 2, con el volumen, el método y el
   comando exactos. Se adjunta el `EXPLAIN (ANALYZE, BUFFERS)` de la consulta paginada antes y
   después.
6. **Concurrencia de la cadena.** Verificado el 20 de octubre de 2026: sin bloqueo, dos
   escritores concurrentes **bifurcan la cadena** (dos eventos con el mismo `previous_hash` y
   `head_hash` con actualización perdida); con `SELECT ... FOR UPDATE` sobre `audit_chain_state`
   la cadena **queda lineal**, pero **serializa las escrituras de todos los hospitales**, porque
   `audit_chain_state` tiene una sola fila (`chain_id = 1`). Para multi-hospital la opción
   natural es **una cadena por hospital** (`chain_id` = hospital) o cambiar el modelo a un
   `append-only` con `event_id` y `trace_id` sin cadena global.

## 4. Notas de implementación

- Los índices se crean con `CREATE INDEX` y **no** con `CREATE INDEX CONCURRENTLY`: Laravel envuelve
  cada migración en una transacción y PostgreSQL no admite `CONCURRENTLY` dentro de una
  transacción. En una tabla de auditoría nueva el bloqueo es aceptable; en producción con datos
  masiva habría que aplicar el `CREATE INDEX CONCURRENTLY` fuera de la transacción.
- La migración es reversible: `down()` elimina la constraint y los cuatro índices, y deja intactos
  los índices previos. Verificado con `migrate:rollback --step 1`.
