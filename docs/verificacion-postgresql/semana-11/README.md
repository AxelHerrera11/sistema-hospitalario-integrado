# Semana 11 — Mejores prácticas para diseño móvil/web

## Portada

- **Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`)
- **Módulo:** Verificación de Base de Datos PostgreSQL
- **Semana:** Semana 11 — Medición de consultas frecuentes con `EXPLAIN ANALYZE` e índices justificados
- **Entregable / evidencia:** Planes de ejecución antes/después e índices propuestos como migración.
- **Estructura:** Sistema Hospitalario Integrado 2026 | Semanas 1–15.

## 1. Alcance

Medir con `EXPLAIN (ANALYZE, BUFFERS)` las consultas que el módulo de ocupación
ejecuta sobre PostgreSQL y proponer los índices que las hacen eficientes. La
medición se hizo **sobre PostgreSQL 16 real**, con un volumen sintético construido
para que las diferencias de plan sean observables.

El volumen sintético (1 003 camas, 22 salas, 50 002 estancias) está sobredimensionado
respecto de un hospital pequeño: sirve para comparar variantes entre sí, no como
objetivo de rendimiento absoluto.

## 2. Consulta medida

La fotografía de ocupación, tal como la ejecuta el módulo:

```sql
SELECT b.bed_id, w.ward_id, w.code AS ward_code, b.status,
       EXISTS (
           SELECT 1 FROM bed_stays s
           WHERE s.bed_id = b.bed_id
             AND s.started_at <= :cutoff
             AND (s.ended_at IS NULL OR s.ended_at > :cutoff)
       ) AS has_active_stay
FROM beds b
JOIN wards w ON w.ward_id = b.ward_id
ORDER BY w.code, b.code;
```

Corte usado: `2026-10-19 12:00:00+00`.

Los planes completos están en [planes-ejecucion.md](./planes-ejecucion.md) y los
índices en [indices-propuestos.sql](./indices-propuestos.sql).

## 3. Resultados

| Variante | Índice disponible | Plan dominante | Tiempo de ejecución |
|---|---|---|---:|
| A · Consulta original | solo los índices del esquema | Seq Scan sobre `bed_stays` (50 001 filas descartadas) | 4,764 ms |
| B · Consulta original | `bed_stays (bed_id, started_at, ended_at)` | Seq Scan sobre `bed_stays` (el índice no se usa) | 4,180 ms |
| C · Preagregado + `LEFT JOIN` | `bed_stays (started_at, ended_at)` | Bitmap Index Scan sobre `bed_stays_started_at_index` | 1,523 ms |
| C' · Preagregado + `LEFT JOIN` | sin índice de fecha | Seq Scan sobre `bed_stays` | 5,496 ms |
| D · Semi-join (`IN`) | `bed_stays (started_at, ended_at)` | Bitmap Index Scan + Index Scan por clave | 0,241 ms |

### 3.1 Hallazgo principal

**El índice correcto no basta: la forma de la consulta manda.** El índice
`(bed_id, started_at, ended_at)` casi natural para este patrón **no se usó** (variante
B), porque el `EXISTS` correlado se evalúa una vez por cama y el planificador eligió
lectura secuencial. Reescribir la consulta como preagregado o semi-join, con un índice
por fecha, baja el tiempo de 4,764 ms a 0,241 ms: **veinte veces menos**.

### 3.2 Segundo hallazgo

Con `EXISTS` correlado, el coste crece con el número de camas **y** con el número de
estancias leídas. El preagregado lee las estancias una sola vez y las une con las
camas, así que su coste depende del volumen de estancias activas, no del total.

## 4. Índices propuestos

```sql
-- Indice 1: apoyo de la consulta temporal de ocupacion.
-- Nombre: bed_stays_started_at_index
CREATE INDEX bed_stays_started_at_index ON bed_stays (started_at, ended_at);

-- Indice 2: apoyo de la consulta de estancias por cama y corte.
-- Nombre: bed_stays_bed_started_at_index
CREATE INDEX bed_stays_bed_started_at_index ON bed_stays (bed_id, started_at, ended_at);
```

| Índice | Consulta que sirve | Variante | Observación |
|---|---|---|---|
| `bed_stays_started_at_index` | Filtro temporal global por corte | C, D | Es el que produce el plan con Bitmap Index Scan |
| `bed_stays_bed_started_at_index` | Estancias de una cama concreta | Ad-hoc | Complementario; por sí solo no cambia el plan de la fotografía |

**Los dos índices no son obligatorios.** Con la consulta reescrita (variante D), el
índice 1 basta. El índice 2 se propone para consultas por cama (historial de una
cama, traslado). Ambos son `CREATE INDEX` no concurrentes: en el despliegue se
recomienda `CREATE INDEX CONCURRENTLY`, que **no** puede ejecutarse dentro de una
transacción de migración; por eso el despliegue debe aplicarlos en una ventana de
mantenimiento o con un comando posterior.

Costo en espacio: un índice sobre 50 002 filas de dos columnas `timestamptz` ocupa
del orden de 2 MiB. Con datos reales de un hospital (decenas de miles de estancias) el
costo es menor.

## 5. Cambios de consulta que se proponen

```sql
-- Variante recomendada: preagregado + LEFT JOIN
WITH active AS (
    SELECT DISTINCT bed_id
    FROM bed_stays
    WHERE started_at <= :cutoff
      AND (ended_at IS NULL OR ended_at > :cutoff)
)
SELECT b.bed_id, w.code AS ward_code, b.code AS bed_code, b.status,
       (a.bed_id IS NOT NULL) AS has_active_stay
FROM beds b
JOIN wards w ON w.ward_id = b.ward_id
LEFT JOIN active a ON a.bed_id = b.bed_id
ORDER BY w.code, b.code;
```

| Aspecto | Antes | Después |
|---|---|---|
| Lecturas de `bed_stays` | Una por cama | Una total |
| Índice usado | Ninguno | `bed_stays_started_at_index` |
| Tiempo medido | 4,764 ms | 1,523 ms (preagregado) / 0,241 ms (semi-join) |
| Semántica | Igual | Igual: `has_active_stay` conserva el significado de ocupación en el corte |

**Nota de exactitud:** el `EXISTS` correlado y el preagregado devuelven el mismo
resultado en el corte medido; la equivalencia debe comprobarse con una prueba que
compare ambas consultas sobre el mismo conjunto de datos.

## 6. Validación ejecutada

```bash
# comando: INSERT ... generate_series para construir 1 000 camas y 50 000 estancias sinteticas; ANALYZE bed_stays, beds, wards
# resultado: 50 002 estancias en total y estadisticas actualizadas

# comando: EXPLAIN (ANALYZE, BUFFERS) de la consulta original
# resultado: Seq Scan on bed_stays, Rows Removed by Filter: 50001, Execution Time: 4.764 ms

# comando: CREATE INDEX bed_stays_bed_started_at_index ON bed_stays (bed_id, started_at, ended_at); ANALYZE bed_stays
# resultado: el planificador sigue usando Seq Scan, Execution Time: 4.180 ms

# comando: CREATE INDEX bed_stays_started_at_index ON bed_stays (started_at, ended_at); ANALYZE bed_stays
# resultado: con la consulta preagregada, Bitmap Index Scan on bed_stays_started_at_index, Execution Time: 1.523 ms

# comando: EXPLAIN (ANALYZE) de la variante con semi-join IN
# resultado: Bitmap Index Scan sobre bed_stays_started_at_index, Execution Time: 0.241 ms
```

## 7. Riesgos y pendientes

- Las cifras vienen de un volumen sintético: cuando exista el seeder común con datos
  realistas hay que repetir la medición.
- Los índices propuestos **no se aplican en este PR**: el número de archivo de la
  migración debe acordarse con el grupo, igual que la migración correctiva de la
  semana 7.
- Cambiar la forma de la consulta del módulo de ocupación afecta a su contrato de
  lectura: debe hacerse con el módulo owner y con la prueba de equivalencia.
