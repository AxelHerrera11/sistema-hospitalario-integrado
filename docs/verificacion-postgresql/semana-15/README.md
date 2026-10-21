# Semana 15 — Pruebas unitarias, caja blanca y caja negra

## Portada

- **Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`)
- **Módulo:** Verificación de Base de Datos PostgreSQL
- **Semana:** Semana 15 — Pruebas de restricciones y casos límite
- **Entregable / evidencia:** Pruebas en el repositorio del grupo con su ejecución exitosa ([casos-caja-negra.md](./casos-caja-negra.md) y [informe-cobertura.md](./informe-cobertura.md)).
- **Estructura:** Sistema Hospitalario Integrado 2026 | Semanas 1–15.

## 1. Alcance

Escribir pruebas automatizadas de restricciones y migraciones (caja blanca) y definir
los casos límite que la API debe cumplir (caja negra).

La prueba de caja blanca se ejecuta **contra PostgreSQL 16 real** y vive en
`tests/Database/`, el mismo lugar donde el repositorio ya guarda las pruebas de
integridad de los slices.

## 2. Caja blanca: prueba publicada

`tests/Database/Hospital/OccupancySnapshotIntegrityTest.php` cubre la semántica de
ocupación que el dashboard de ASII-09 consume, es decir, las reglas que el esquema
federado debe garantizar:

| Caso | Qué demuestra |
|---|---|
| `test_bed_is_occupied_only_inside_the_stay_interval` | La cama está ocupada en el corte solo si `started_at <= T` y `ended_at > T` o es nulo; en el borde (`T = ended_at`) ya está libre |
| `test_open_stay_keeps_the_bed_occupied_without_end_date` | Una estancia sin `ended_at` sigue abierta indefinidamente |
| `test_operational_status_does_not_change_the_interval_derived_occupancy` | `beds.status` es estado operativo: no borra una estancia vigente y la discrepancia se puede detectar |
| `test_two_active_stays_on_the_same_bed_are_rejected` | La exclusión GiST rechaza dos estancias activas en la misma cama |
| `test_stay_must_lie_inside_the_care_episode_period` | El trigger `bed_stays_episode_period_trigger` rechaza una estancia fuera del periodo del episodio |
| `test_occupancy_counters_ignore_beds_out_of_service` | Las camas en mantenimiento o inactivas salen del denominador de la tasa de ocupación |

### 2.1 Correcciones durante la elaboración

La primera ejecución dio **2 errores y 2 fallos**. Los cuatro tienen el mismo origen:
los datos de prueba no respetaban las reglas del esquema.

| Fallo | Causa | Corrección |
|---|---|---|
| `bed_stays_episode_period_trigger` al abrir una estancia | El episodio de prueba estaba cerrado y una estancia sin `ended_at` no puede pertenecer a un episodio cerrado | Se crearon episodios abiertos y cerrados por separado |
| `bed_stays_episode_period_trigger` al insertar la primera estancia | La estancia se asoció a un episodio con periodo incompatible | Se asoció a un episodio abierto que contiene el intervalo |
| Inconsistencias: 2 en lugar de 0 | La aserción estaba mal planteada: una cama en mantenimiento con estancia vigente **también** es inconsistencia | Se corrigieron las expectativas y se separaron dos casos explícitos |
| Contadores: 5 camas en lugar de 3 | El grafo de prueba ya tenía 3 camas y se añadieron 2 más | Se ajustó la expectativa al total real |

Ninguno de los fallos era del esquema: eran expectativas mal escritas en la prueba.
La corrección se hizo en la prueba, nunca en el esquema.

## 3. Validación ejecutada

```bash
# comando: php vendor/bin/phpunit tests/Database/Hospital/OccupancySnapshotIntegrityTest.php
# resultado: OK (6 tests, 59 assertions) sobre PostgreSQL 16

# comando: php vendor/bin/phpunit tests/Database
# resultado: OK (59 tests, 738 assertions) — la suite completa con la prueba nueva

# comando: php vendor/bin/pint --test tests/Database/Hospital/OccupancySnapshotIntegrityTest.php
# resultado: PASS

# comando: php -l tests/Database/Hospital/OccupancySnapshotIntegrityTest.php
# resultado: sin errores de sintaxis

# comando: git diff --check
# resultado: sin salida
```

## 4. Riesgos y pendientes

- Los casos de caja negra no se ejecutaron contra HTTP porque la ruta no existe; se
  entregarán al módulo de ocupación cuando la implemente.
- La prueba usa `migrate:fresh` sobre la base HOSPITAL: nunca debe ejecutarse contra
  una base con datos reales.
- La cobertura de las tablas de laboratorio, alertas y medicamentos sigue pendiente:
  sus migraciones no existen.
