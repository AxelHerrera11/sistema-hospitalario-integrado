# Semana 10 — Diseño para movilidad

## Portada

- **Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`)
- **Módulo:** Verificación de Base de Datos PostgreSQL
- **Semana:** Semana 10 — Paginación y sincronización para clientes móviles
- **Entregable / evidencia:** Propuesta de paginación y sincronización con ejemplos de consulta.
- **Estructura:** Sistema Hospitalario Integrado 2026 | Semanas 1–15.

## 1. Alcance

Analizar las consultas que usan los clientes móviles (tableta y teléfono en sala) y
proponer cómo paginar y cómo sincronizar cuando la conexión es intermitente,
apoyándose en las tablas de sincronización de la base CENTRAL.

La ocupación es el caso de uso: el personal consulta el estado de las camas desde el
teléfono, con red intermitente, y no puede volver a cargar la lista completa.

Las cifras completas están en [metricas-medidas.md](./metricas-medidas.md).

## 2. Problema medido

Con el volumen sintético de la medición de la semana 11 (1 003 camas y 50 002
estancias), la fotografía completa de ocupación devuelve 1 003 filas; la versión móvil
debe devolver una **página**.

| Métrica | Valor medido |
|---|---|
| Filas de la fotografía completa | 1 003 |
| Filas por página (propuesta) | 25 |
| Tamaño del JSON de una página de 25 camas | 2 999 bytes (2,9 KiB) |
| Tamaño de la fotografía completa | 120 KiB (estimado por proporcionalidad) |

Un móvil con conexión de 3G descarga 120 KiB por consulta; con la paginación descarga
2,9 KiB por página. La diferencia decide si la pantalla es usable en una guardia.

## 3. Propuesta de paginación

### 3.1 Paginación por clave (keyset), no por desplazamiento

El desplazamiento con `OFFSET` obliga a la base a leer y descartar las filas anteriores;
en una lista que cambia mientras el usuario la recorre, además produce saltos y
duplicados.

```sql
-- Primera pagina
SELECT b.bed_id, w.code AS ward_code, b.code AS bed_code, b.status,
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

-- Pagina siguiente: se reanuda desde la ultima clave devuelta
SELECT b.bed_id, w.code AS ward_code, b.code AS bed_code, b.status,
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
```

| Regla | Detalle |
|---|---|
| Cursor | Par ordenado `(ward_code, bed_code)`; es único y estable |
| Tamaño de página | 25 por defecto, máximo 100, alineado con el contrato común de paginación |
| Respuesta | Además de `data`, un `meta.next_cursor` con la última clave, `null` al final |
| Consistencia | La foto usa un `cutoff_at` fijo para **toda** la paginación: si el corte cambiara entre páginas, la lista mezclaría dos instantes |
| Conteo total | Opcional: `meta.total` es una consulta extra que en móvil se omite |

### 3.2 Por qué no `OFFSET`

| Criterio | `LIMIT/OFFSET` | Keyset |
|---|---|---|
| Coste de la página 50 | Se lee y descarta todo lo anterior | Se empieza por la clave |
| Consistencia con cambios | Salta o duplica filas | No se salta; la fila modificada puede reaparecer al final, que es el comportamiento aceptado |
| Tamaño del cursor | Un entero | Dos cadenas cortas |

## 4. Propuesta de sincronización

### 4.1 Lo que ya existe en CENTRAL

| Tabla | Papel |
|---|---|
| `sync_nodes` | Un nodo por hospital conectado a la red |
| `sync_checkpoints` | Última posición sincronizada por hospital y canal |
| `central_inbox_messages` | Mensajes recibidos, con clave de idempotencia |

### 4.2 Consulta de cambios desde el último corte sincronizado

```sql
-- Lo que el movil debe traerse desde su ultimo checkpoint
SELECT m.event_id, m.entity_type, m.entity_id, m.operation, m.occurred_at
FROM central_inbox_messages m
JOIN sync_checkpoints c
  ON c.node_id = :node_id
 AND c.channel = m.channel
 AND c.last_sequence < m.sequence
WHERE m.hospital_id = :hospital_id
ORDER BY m.sequence
LIMIT 200;
```

Reglas:

1. El móvil guarda el `checkpoint` (hospital, canal y última secuencia) en el
   dispositivo, no en la base del hospital.
2. Cada respuesta trae como máximo 200 mensajes y un `next_checkpoint`.
3. La aplicación **ignora** mensajes duplicados: la clave de idempotencia de
   `central_inbox_messages` ya lo impide en el servidor, pero el móvil también debe
   tolerar repetidos tras una reconexión.
4. El corte de la fotografía de ocupación se guarda junto al checkpoint; al reconectar,
   el móvil puede mostrar la última foto marcada como desactualizada con la edad del
   corte, nunca como actual.

### 4.3 Comportamiento con conexión intermitente

| Situación | Comportamiento propuesto |
|---|---|
| Sin red | Mostrar la última foto guardada con su `cutoff_at` y la marca "desactualizada" |
| Red lenta | Cancelar la consulta si supera 2 s y reintentar una vez con backoff |
| Datos parciales | Mostrar lo recibido indicando "actualizando"; nunca mezclar páginas de cortes distintos |
| Cambio de hospital | Descartar la caché local: los datos son del hospital del `X-Tenant-ID` |

## 5. Coste en la base

| Operación | Coste |
|---|---|
| Página de 25 camas | Lectura de 25 filas de `beds` y 25 comprobaciones sobre `bed_stays` |
| Cambio de página | El mismo coste: se reanuda por clave, sin releer el inicio |
| Sincronización | Lectura por `sequence` desde `sync_checkpoints`; el índice único de secuencia evita duplicados |

## 6. Validación ejecutada

```bash
# comando: WITH page AS (SELECT ... WHERE (w.code, b.code) > ('PERF-1','P-PERF-1-01') ORDER BY w.code, b.code LIMIT 25) SELECT count(*), pg_column_size(json_agg(page)::text)
# resultado: 25 filas, 2 999 bytes (2,9 KiB) por pagina

# comando: SELECT count(*) FROM beds; SELECT count(*) FROM wards;
# resultado: 1 003 camas y 22 salas en el volumen de medicion

# comando: EXPLAIN (ANALYZE) de la consulta por clave con la pagina de 25
# resultado: Index Scan sobre wards_code_unique e Index Scan sobre beds_ward_id_index, Execution Time 0.130 ms
```

## 7. Riesgos y pendientes

- La sincronización de eventos desde el hospital hacia la red depende del slice H8
  (`outbox_events`): la tabla `central_inbox_messages` existe, pero el emisor todavía
  no.
- El tamaño de página debe alinearse con el contrato común de paginación, que sigue
  pendiente de acuerdo.
- La caché del móvil es responsabilidad del frontend; esta semana define el contrato
  de datos, no su implementación.
