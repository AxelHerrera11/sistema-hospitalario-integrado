# Pruebas de caja blanca

Análisis de ramas y condiciones del flujo «consolidación y consulta de ocupación por sala y estado
de cama». Las pruebas viven en
`tests/Database/Hospital/OccupancySnapshotIntegrityTest.php` y se ejecutan contra PostgreSQL 16
real, porque las ramas dependen de la semántica de la base: el intervalo de la estancia, la
exclusión de estancias solapadas y la coerencia con el episodio de cuidado.

## Ramas del clasificador

La clasificación de una cama depende de dos hechos independientes: si existe una estancia que
cubre el corte `T` y cuál es el estado operativo registrado.

| # | Condición | Resultado esperado | Prueba |
|---|---|---|---|
| WB-01a | `started_at <= T` y `ended_at IS NULL` | Occupada | `test_open_stay_keeps_the_bed_occupied_without_end_date` |
| WB-01b | `started_at <= T` y `ended_at > T` | Occupada | `test_bed_is_occupied_only_inside_the_stay_interval` |
| WB-01c | `ended_at = T` | Libre: el intervalo es `[inicio, fin)` | `test_bed_is_occupied_only_inside_the_stay_interval` |
| WB-01d | `started_at > T` | Libre | `test_bed_is_occupied_only_inside_the_stay_interval` |
| WB-03a | Estancia vigente y `beds.status = available` | Ocupada + inconsistencia | `test_operational_status_does_not_change_the_interval_derived_occupancy` |
| WB-03b | Estancia vigente y `beds.status = maintenance` | Ocupada + inconsistencia | `test_operational_status_does_not_change_the_interval_derived_occupancy` |
| WB-03c | Sin estancia y `beds.status = occupied` | No verificada + inconsistencia | `test_operational_status_does_not_change_the_interval_derived_occupancy` |
| WB-03d | Sin estancia y `beds.status = available` | Disponible | `test_operational_status_does_not_change_the_interval_derived_occupancy` |
| WB-06 | Cama `inactive` o en `maintenance` | Fuera de la capacidad operativa: no entra en el denominador de la tasa | `test_occupancy_counters_ignore_beds_out_of_service` |

## Ramas de la consulta

| # | Condición | Resultado esperado | Prueba |
|---|---|---|---|
| WB-04 | Dos estancias activas en la misma cama | Rechazo de la base de datos (restricción de exclusividad) | `test_two_active_stays_on_the_same_bed_are_rejected` |
| WB-05 | Estancia fuera del periodo del episodio de cuidado | Rechazo por trigger de periodo | `test_stay_must_lie_inside_the_care_episode_period` |

## Ramas de la capa de aplicación y presentación

| # | Condición | Resultado esperado | Prueba |
|---|---|---|---|
| WB-A1 | Capacidad operativa cero | La tasa es `null`, nunca `0 %` | `sin capacidad operativa la tasa es null` |
| WB-A2 | Falla de persistencia | `503` con `OCCUPANCY_UNAVAILABLE`, sin cuerpo de ocupación | `la falla devuelve 503 y nunca ocupación cero` |
| WB-A3 | `Presentation` contiene SQL | Falla de arquitectura | `Presentation no contiene SQL` |
| WB-A4 | `Domain` o `Application` dependen de PDO | Falla de arquitectura | `Domain y Application no dependen de PDO` |
| WB-A5 | `Persistence` usa `query()` o `exec()` | Falla de arquitectura | `Persistence usa prepare y no query/exec` |

## Trazabilidad con el contrato

| Rama | Dónde se decide en el código integrado |
|---|---|
| WB-01a a WB-01d | `PdoBedSnapshotReader::readAt()` (expresión `EXISTS` sobre `bed_stays`) y `OccupancyCalculator` |
| WB-03a a WB-03d | `OccupancyCalculator` (compara la estancia con `beds.status` y cuenta discrepancias) |
| WB-04 | Restricción de exclusividad sobre `bed_stays` (base de datos) |
| WB-05 | Trigger de periodo `bed_stays` / `care_episodes` (base de datos) |
| WB-06 | `OccupancyCalculator` al calcular la capacidad operativa |
| WB-A1 | `OccupancyCalculator` devuelve `null` cuando el denominador es cero |
| WB-A2 | `OccupancyController` captura `PersistenceFailure` y devuelve `503` |

## Límites de la caja blanca

- Las ramas se ejercitan contra el esquema real de `develop`, no contra una base con tablas
  temporales: así se prueba también el comportamiento de los triggers y de las restricciones.
- No se instrumenta el código con cobertura de líneas: la cobertura no sustituye los casos de
  comportamiento y el plan SQA la marca como métrica complementaria (M-13).
- Las ramas que dependen de volumen (RNF-OCC-05) y de concurrencia real de escrituras quedan
  fuera: el módulo es de solo lectura y no hay entorno con 10 000 camas.
