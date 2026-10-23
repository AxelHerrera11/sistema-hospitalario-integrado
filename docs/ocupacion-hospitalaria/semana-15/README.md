# Semana 15 - Pruebas unitarias, de caja blanca y de caja negra

## Portada

- **Estudiante:** Javier Alexander Fajardo López (`Javifa2324`)
- **Módulo:** ASII-09 - Dashboard de ocupación hospitalaria
- **Issue:** [#36](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/36)
- **Entrega:** pruebas trazadas a los RF/RNF del módulo, con comandos y resultados reales.

## Alcance y límites

Se prueban las reglas de ocupación, salas, estados de cama, indicadores, actualización y
procedencia de datos del propio módulo. **No se prueban módulos ajenos**: las tablas `wards`,
`beds` y `bed_stays` se usan como fixtures y su corrección es responsabilidad de ASII-06/07/08.

Las suites que se ejecutan viven en el repositorio, no en este documento:

| Suite | Ubicación | Tipo | Resultado del 21/10/2026 |
|---|---|---|---|
| Verificaciones del Micro-HIS | `micro-his/ocupacion-hospitalaria/tests/run.php` | Unitarias y de arquitectura | `OK: 12 verificaciones aprobadas` |
| Contrato HTTP | `tests/Feature/Ocupacion/` | Caja negra | `16 passed (66 assertions)` |
| Semántica en base de datos | `tests/Database/Hospital/OccupancySnapshotIntegrityTest.php` | Caja blanca | `6 passed (59 assertions)` |
| Utilidades de la pantalla | `resources/js/modules/ocupacion/ocupacion.test.mjs` | Unitarias de presentación | `OK: 14 aserciones` |

- [Matriz RF/RNF → prueba](./matriz-rf-prueba.md)
- [Caja blanca](./caja-blanca.md)
- [Caja negra](./caja-negra.md)
- [Defectos registrados](./defectos.md)
- [Declaración de IA](./DECLARACION_IA.md)

## Pruebas unitarias (mínimo 3)

Las tres reglas de dominio del módulo, ejecutadas sin base de datos mediante el doble en memoria
del puerto `BedSnapshotReader`:

| # | Regla probada | Verificación | Resultado |
|---|---|---|---|
| U-01 | La estancia activa prevalece sobre `beds.status` | `la estancia activa prevalece sobre status available` | PASS |
| U-02 | La tasa usa solo camas operativas | `la tasa usa solo camas operativas` | PASS |
| U-03 | Sin capacidad operativa la tasa es `null`, no `0 %` | `sin capacidad operativa la tasa es null` | PASS |
| U-04 | Cada cama se cuenta exactamente una vez | `cada cama se cuenta exactamente una vez` | PASS |
| U-05 | Sin estancia, `occupied` queda como no verificada | `occupied sin estancia queda no verificada` | PASS |

Las cuatro reglas restantes son de arquitectura y de límites:

| # | Regla probada | Verificación | Resultado |
|---|---|---|---|
| U-06 | Domain y Application no dependen de PDO | `Domain y Application no dependen de PDO` | PASS |
| U-07 | Presentation no contiene SQL | `Presentation no contiene SQL` | PASS |
| U-08 | Persistence usa sentencias preparadas | `Persistence usa prepare y no query/exec` | PASS |

## Pruebas de caja blanca (mínimo 2)

| # | Rama o condición probada | Dónde | Resultado |
|---|---|---|---|
| WB-01 | La fotografía atómica con `started_at <= T` y `ended_at` nulo o posterior | `OccupancySnapshotIntegrityTest::test_bed_is_occupied_only_inside_the_stay_interval` | PASS |
| WB-02 | Una estancia abierta sin `ended_at` mantiene la cama ocupada indefinidamente | `OccupancySnapshotIntegrityTest::test_open_stay_keeps_the_bed_occupied_without_end_date` | PASS |
| WB-03 | El estado operativo no borra una estancia vigente y produce inconsistencia | `OccupancySnapshotIntegrityTest::test_operational_status_does_not_change_the_interval_derived_occupancy` | PASS |
| WB-04 | Dos estancias activas en la misma cama se rechazan | `OccupancySnapshotIntegrityTest::test_two_active_stays_on_the_same_bed_are_rejected` | PASS |
| WB-05 | La estancia debe quedar dentro del periodo del episodio de cuidado | `OccupancySnapshotIntegrityTest::test_stay_must_lie_inside_the_care_episode_period` | PASS |
| WB-06 | Los contadores ignoran camas fuera de servicio | `OccupancySnapshotIntegrityTest::test_occupancy_counters_ignore_beds_out_of_service` | PASS |

Detalle de ramas, condiciones y datos de entrada en [caja-blanca.md](./caja-blanca.md).

## Pruebas de caja negra (mínimo 4)

| # | Categoría | Caso | Resultado |
|---|---|---|---|
| BN-01 | Equivalencia | Consulta autorizada con sesión, hospital y permiso válidos | `200` con corte, global y salas |
| BN-02 | Equivalencia | Consulta sin token | `401`, sin cifras |
| BN-03 | Equivalencia | Consulta sin el permiso `ocupacion.ver` | `403`, sin cifras |
| BN-04 | Equivalencia | Hospital declarado distinto al autorizado | `403` `TENANT_MISMATCH` |
| BN-05 | Límite | Corte en el instante exacto de inicio de la estancia | La cama está ocupada |
| BN-06 | Límite | Corte en el instante exacto de fin de la estancia | La cama está libre |
| BN-07 | Límite | Corte con formato inválido | `400`, sin cifras |
| BN-08 | Errores | Base HOSPITAL caída o inexistente | `503` y nunca ocupación cero |
| BN-09 | Errores | Método distinto de `GET` | `405` |
| BN-10 | Privacidad | Revisión del esquema de respuesta | Sin datos de pacientes ni `SQLSTATE` |

Detalle de clases de equivalencia, límites y permisos en [caja-negra.md](./caja-negra.md).

## Comandos y resultados

Salida real del 21 de octubre de 2026 sobre `develop` en `75bd69ce`, con PostgreSQL 16 en los
contenedores `bd-central` (55431) y `bd-hospital` (55433):

```bash
$ php micro-his/ocupacion-hospitalaria/tests/run.php
== Camino feliz y reglas de dominio ==
PASS  responde 200 con la misma fecha de corte
PASS  cada cama se cuenta exactamente una vez
PASS  la estancia activa prevalece sobre status available
PASS  occupied sin estancia queda no verificada
PASS  la tasa usa solo camas operativas
PASS  la distribución usa el inventario total
PASS  consolida las salas sin perder camas
PASS  sin capacidad operativa la tasa es null

== Error de persistencia ==
PASS  la falla devuelve 503 y nunca ocupación cero

== Arquitectura ==
PASS  Domain y Application no dependen de PDO
PASS  Presentation no contiene SQL
PASS  Persistence usa prepare y no query/exec

OK: 12 verificaciones aprobadas

$ php artisan test tests/Feature/Ocupacion tests/Database/Hospital/OccupancySnapshotIntegrityTest.php
Tests:    22 passed (125 assertions)
Duration: 2.12s

$ node --test resources/js/modules/ocupacion/ocupacion.test.mjs
OK: 14 aserciones de la pantalla de ocupación aprobadas
```

## Evidencia visual

Las capturas de interfaz del flujo principal y del error crítico están en la semana 11:
[`semana-11/capturas/`](../semana-11/capturas/). La evidencia visual de esta semana son las
respuestas HTTP reales registradas en la bitácora de la semana 17
(`docs/ocupacion-hospitalaria/semana-17/bitacora-despliegue.md`): `200` con datos reales, `401`,
`403` por tenant, `403` por permiso y `503` con la base caída, todos con `APP_DEBUG=false` y sin
traza en la respuesta.

## Defectos registrados

Dos defectos reales del módulo, ambos detectados en revisión y ya corregidos, están en
[defectos.md](./defectos.md): la conexión resuelta de forma anticipada que devolvía `500` en vez
de `503`, y el falso positivo de `PublishedContractsTest` al leer el nombre de un archivo de
prueba como si fuera un permiso.

## Lo que no se probó

- No se ejecutó lector de pantalla ni certificación WCAG: la accesibilidad se verificó por
  inspección del marcado y por las aserciones de la vista.
- No se midió el rendimiento con 10 000 camas (RNF-OCC-05): no hay entorno con ese volumen, se
  reporta como «sin datos».
- No se probó el filtro por sala (RF-OCC-05): la pantalla y el contrato no lo exponen todavía; es
  la propuesta R-3 de la semana 13.
