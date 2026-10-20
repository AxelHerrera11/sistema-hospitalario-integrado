# Semana 10 — Diseño para movilidad

## Portada

- **Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`)
- **Módulo:** Verificación de Base de Datos PostgreSQL
- **Semana:** Semana 10 — Paginación y sincronización para clientes móviles
- **Entregable / evidencia:** Propuesta de paginación y sincronización con consultas
  ejecutadas sobre el esquema migrado de `develop`.
- **Estructura:** Sistema Hospitalario Integrado 2026 | Semanas 1–15.

## 1. Alcance

Analizar las consultas que usan los clientes móviles (tableta y teléfono en sala) y
proponer cómo paginar y cómo sincronizar cuando la conexión es intermitente,
apoyándose en las tablas de sincronización de la base CENTRAL.

La ocupación es el caso de uso: el personal consulta las camas desde el teléfono, con
red intermitente, y no puede volver a cargar la lista completa.

## 2. Problema medido

Con el volumen sintético de la medición de la semana 11 (1 003 camas y 50 002
estancias), la fotografía completa de ocupación devuelve 1 003 filas; la versión móvil
debe devolver una **página** de 25 filas, 2 999 bytes (2,9 KiB) frente a 120 KiB de la
lista completa (medición completa en [metricas-medidas.md](./metricas-medidas.md)).

## 3. Propuesta de paginación

### 3.1 Paginación por clave (keyset), no por desplazamiento

El desplazamiento con `OFFSET` obliga a la base a leer y descartar las filas anteriores;
en una lista que cambia mientras el usuario la recorre, además produce saltos y
duplicados.

Las consultas completas están en
[consultas-paginacion.sql](./consultas-paginacion.sql) (consultas 1, 2 y 4): la primera
página ordena por `(ward_code, bed_code)` y la siguiente se reanuda con
`WHERE (w.code, b.code) > (:ultimo_ward_code, :ultimo_bed_code)`.

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

La consulta que el móvil ejecuta desde su último checkpoint es la consulta 3 de
[consultas-paginacion.sql](./consultas-paginacion.sql). En resumen, une la bandeja con
`sync_nodes` (para resolver el hospital) y con `sync_checkpoints` (para el cursor), y
filtra por `(received_at, event_id)` mayor que el par guardado en
`(last_occurred_at, last_event_id)`, con `LIMIT 200`.

Reglas:

1. El cursor es el par `(received_at, event_id)`: `received_at` ordena y `event_id`
   desempata, de modo que dos mensajes del mismo instante no se saltan ni se repiten.
   El checkpoint guarda ese par en `last_occurred_at` y `last_event_id`.
2. El móvil guarda el `checkpoint` (hospital, canal y cursor) en el dispositivo, no en la
   base del hospital.
3. Cada respuesta trae como máximo 200 mensajes y un `next_checkpoint`.
4. La aplicación **ignora** mensajes duplicados: la garantía real del servidor es
   `UNIQUE (source_node_id, event_id)`, que rechaza la reinserción del mismo evento con
   `23505`; el móvil también debe tolerar repetidos tras una reconexión.
5. Un mensaje `failed` se entrega con `attempts` y `error_code` para que el cliente lo
   reintente; la consulta además filtra por `available_at`, que es la ventana de
   visibilidad del mensaje.
6. El corte de la fotografía de ocupación se guarda junto al checkpoint; al reconectar,
   el móvil puede mostrar la última foto marcada como desactualizada con la edad del
   corte, nunca como actual.

### 4.3 Corrección del 20 de octubre de 2026 (columnas reales)

La versión anterior usaba columnas que **no existen** en el esquema migrado de
`develop`. Verificado contra
`database/migrations/central/2026_08_11_000400_create_central_sync_tables.php`:

- `central_inbox_messages` no tiene `channel`, `sequence`, `entity_type`, `entity_id`,
  `operation`, `occurred_at` ni `hospital_id`; sus columnas son `message_id`,
  `source_node_id`, `event_id`, `event_type`, `payload_minimal`, `metadata`,
  `received_at`, `available_at`, `processed_at`, `failed_at`, `status`, `attempts` y
  `error_code`.
- `sync_checkpoints` no tiene `last_sequence`; su cursor es `last_event_id` y
  `last_occurred_at`, con `UNIQUE (node_id, channel)`.
- El hospital se resuelve por `sync_nodes.hospital_id` a través de `source_node_id`.
- La idempotencia real es `UNIQUE (source_node_id, event_id)`, no un "índice único de
  secuencia".
- La bandeja no tiene `occurred_at`: el orden usa `received_at` como proxy y `event_id`
  como desempate. Esa elección de criterio es una decisión del grupo.

### 4.4 Comportamiento con conexión intermitente

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
| Sincronización | Lectura desde `sync_checkpoints` por `source_node_id` y orden `(received_at, event_id)`; la idempotencia la garantiza `UNIQUE (source_node_id, event_id)` |

## 6. Validación ejecutada

Las cifras de paginación están en
[metricas-medidas.md](./metricas-medidas.md) (1 003 camas, 2 999 bytes por página,
0,130 ms). La sincronización se ejecutó el 20 de octubre de 2026 sobre PostgreSQL 16
con las migraciones `database/migrations/central` de `develop`, con el script completo
en [pruebas-sincronizacion.sql](./pruebas-sincronizacion.sql):

```bash
# comando: psql -U postgres -d his_central_test -f pruebas-sincronizacion.sql
# primera sincronizacion (checkpoint vacio): 3 filas, las dos del mismo instante ordenadas por event_id
# avance de checkpoint a (2026-10-20 06:00:00, ...552): UPDATE 1
# segunda sincronizacion: 1 fila, solo la posterior al checkpoint (06:05:00, ...553)
# paginacion de uno en uno: pagina 1 -> ...551, pagina 2 -> ...552, pagina 3 -> ...553
#   NOTICE: paginas=3 entregados=3 distintos=3
# reinsercion del mismo evento: ERROR 23505 duplicate key value violates unique
#   constraint "central_inbox_messages_source_event_unique"
# reintento real sobre la misma fila: 1 fila, intentos=4 (no se crea una fila nueva)
# plan: Index Scan sobre sync_nodes_hospital_id_index, central_inbox_messages_source_node_id_index
#   y sync_checkpoints_node_channel_unique; Sort quicksort de 25 kB, Execution Time 0.062 ms
```

## 7. Riesgos y pendientes

- **PENDIENTE** (decisión del grupo): el emisor del slice H8 (`outbox_events`) no
  existe todavía, así que la bandeja solo recibe lo que otro componente escriba.
- **PENDIENTE** (decisión del grupo): el criterio de orden de la bandeja. La propuesta
  ejecutada usa `received_at` como proxy de `occurred_at` y `event_id` como desempate;
  la alternativa es agregar `occurred_at` y un cursor dedicado, lo que exige una
  migración acordada.
- **PENDIENTE** (decisión del grupo): la bandeja no tiene columna `channel` y el
  checkpoint sí; hoy no se puede filtrar la bandeja por canal y se usa un canal único
  (`ocupacion`) por nodo.
- **PENDIENTE**: un índice compuesto `(source_node_id, received_at, event_id)` evitaría
  el `Sort` del plan; requiere migración, así que no se incluye en esta entrega.
- El tamaño de página debe alinearse con el contrato común de paginación, que sigue
  pendiente de acuerdo. La caché del móvil es responsabilidad del frontend.
