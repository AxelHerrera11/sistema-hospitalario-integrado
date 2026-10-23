# Semana 6 - Primera evaluación parcial

## Portada

- **Estudiante:** Javier Alexander Fajardo López (`Javifa2324`)
- **Módulo:** ASII-09 - Dashboard de ocupación hospitalaria
- **Issue:** [#36](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/36)
- **Entrega:** defensa teórica y caso práctico arquitectónico

## Tesis arquitectónica

El dashboard debe informar ocupación sin convertirse en dueño de camas o estancias. La arquitectura separa HTTP, coordinación, reglas y persistencia; deriva ocupación de intervalos `bed_stays`, conserva `beds.status` como dato operativo y expone discrepancias en vez de corregirlas u ocultarlas.

## Matriz decisión → evidencia

[Diagrama trazable de las semanas 1 a 5](./fuentes/01-trazabilidad.mmd)

| Decisión defendida | Evidencia verificable | Consecuencia |

| Decisión defendida | Evidencia verificable | Consecuencia |
|---|---|---|
| Dominio independiente | `OccupancyCalculator` y `BedSnapshot` no dependen de PDO | La regla se prueba sin infraestructura |
| Consulta por puerto | `BedSnapshotReader` con adaptador PDO y doble en memoria | El caso de uso no conoce SQL |
| Fotografía consistente | `PdoBedSnapshotReader::readAt()` usa una sentencia preparada | Todas las camas usan el mismo corte |
| Fallo seguro | Persistencia fallida produce `503` | Nunca se presenta ocupación cero falsa |
| Frontera HTTP | `GET /api/v1/occupancy` valida token, tenant y corte | El cliente no accede a la base |
| No extraer todavía | Ningún umbral operativo ha sido medido | Se evita distribuir el acoplamiento |

## Caso práctico

A las `10:00 -06:00`, la sala MED contiene cinco camas ficticias:

| Cama | `beds.status` | Estancia activa | Clasificación del dashboard |
|---|---|---:|---|
| MED-01 | `available` | Sí | Ocupada + inconsistencia |
| MED-02 | `available` | No | Disponible |
| MED-03 | `maintenance` | No | Mantenimiento |
| MED-04 | `occupied` | No | Ocupación no verificada + inconsistencia |
| MED-05 | `inactive` | No | Inactiva |

Resultado: total `5`, ocupadas `1`, disponibles `1`, mantenimiento `1`, inactivas `1`, no verificadas `1`, inconsistencias `2` y tasa operativa `50 %` (`1 / (1 + 1)`). Cada cama se cuenta una sola vez. El dashboard no libera MED-04 ni modifica MED-01: esa responsabilidad permanece en ASII-06/07/08.

## Registro del cambio práctico defendido

**Cambio solicitado en la defensa:** «Si ASII-06 actualiza `beds.status` de una cama con
estancia vigente a `available`, ¿qué debe mostrar el dashboard y qué debe hacer con el dato?».

**Respuesta defendida:** el dashboard deriva la ocupación del intervalo de la estancia y nunca del
estado operativo. La cama sigue clasificada como ocupada y se reporta una inconsistencia entre la
estancia vigente y `beds.status`; el módulo **no** corrige el estado de la cama ni libera la
estancia, porque esos datos son propiedad de ASII-06/07/08. Con la misma lógica, una cama en
`occupied` sin estancia no se cuenta como ocupada: se marca como «no verificada» y alimenta el
contador de discrepancias.

**Evidencia que sostiene la respuesta:** la prueba `operational status does not change the
interval-derived occupancy` de `tests/Database/Hospital/OccupancySnapshotIntegrityTest.php`
comprueba que una cama en mantenimiento con estancia vigente es inconsistencia y que una cama
ocupada sin estancia no está ocupada. La verificación `la estancia activa prevalece sobre status
available` del Micro-HIS cubre el mismo caso en el dominio.

**Límite reconocido:** el cambio no altera el contrato v1 ni la respuesta de la API; si el grupo
decidiera que `beds.status` fuera autoritativo, habría que revisar el contrato y migrar la regla,
lo que el plan transversal deja como decisión del equipo.

## Guion de demostración

1. Ejecutar `php micro-his/ocupacion-hospitalaria/tests/run.php`.
2. Señalar las pruebas de fuente autoritativa, discrepancias y tasa.
3. Mostrar que InMemory y PDO implementan el mismo Repository.
4. Explicar `401`, `403`, `400` y `503` del contrato HTTP.
5. Cambiar una cama ficticia durante la defensa y predecir el resumen antes de ejecutar.

## Resultado verificado

La suite local reporta `OK: 12 verificaciones aprobadas`; PostgreSQL es una integración opcional y ya fue validada en Semanas 3 y 4. La defensa no afirma métricas productivas ni infraestructura inexistente.

## Riesgos y límites

- El token estático es exclusivo del prototipo; producción requiere JWT y permiso `ocupacion.ver`.
- El tenant y el DSN se aprovisionan como pareja indivisible por instancia.
- No se muestran pacientes ni episodios identificables.
- La separación futura depende de métricas reales de carga, p95 y disponibilidad.

## Conclusión

La solución mantiene una única regla de ocupación comprobable desde dominio hasta API. El valor arquitectónico no está en añadir capas, sino en preservar propiedad de datos, consistencia temporal y fallos visibles.
