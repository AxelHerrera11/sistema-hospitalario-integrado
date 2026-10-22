# Defectos registrados

Defectos reales del módulo de ocupación, detectados en revisión o en la ejecución de la suite y
ya corregidos en el mismo PR donde se detectaron. Se registran con evidencia reproducible, tal
como exige la puerta de calidad del plan SQA.

## D-01 - La base HOSPITAL caída devolvía `500` en lugar de `503`

| Campo | Detalle |
|---|---|
| Severidad | Mayor (bloqueante para la entrega funcional) |
| Detectado por | Revisión de Yamilet sobre el PR #574, 21 de octubre de 2026 |
| Componente | `app/Providers/OcupacionServiceProvider.php` y `micro-his/ocupacion-hospitalaria/src/Persistence/PdoBedSnapshotReader.php` |
| Contrato violado | RF-OCC-07 y RNF-OCC-06 |

**Descripción.** El provider resolvía `DB::connection('hospital')->getPdo()` al construir el
adaptador, es decir fuera del `try/catch (PersistenceFailure)` del controlador. Con la base
HOSPITAL caída, la ruta respondía `500` con traza en lugar del `503` del contrato, y el cliente no
podía distinguir un fallo de infraestructura de un error del módulo.

**Reproducción (antes de corregir).**

```
SQLSTATE[08006] ... FATAL: database "base_inexistente_asii09" does not exist
```

**Causa.** Acoplamiento entre el momento de construir el adaptador y el momento de leer: la
conexión se resolvía de forma anticipada.

**Corrección.** `PdoBedSnapshotReader` acepta un `\PDO` **o** un `\Closure(): \PDO`; el provider
resuelve la conexión dentro de la lectura y traduce el fallo a `PersistenceFailure`.

**Prueba de regresión.** `test_una_base_hospital_inexistente_responde_503`: la suite conecta la
conexión `hospital` real a una base inexistente y exige `503`, nunca `500`. Está en
`tests/Feature/Ocupacion/`.

**Estado:** cerrado y verificado.

## D-02 - El nombre de un archivo de prueba se leía como un permiso y rompía la suite `Feature`

| Campo | Detalle |
|---|---|
| Severidad | Mayor (rompía la suite completa del equipo) |
| Detectado por | Revisión de Yamilet sobre el PR #574, 21 de octubre de 2026 |
| Componente | `tests/Feature/Rbac/PublishedContractsTest.php` y la ruta del archivo de pruebas del módulo |

**Descripción.** `PublishedContractsTest` recorre `docs/**` y exige que todo token con forma
`modulo.accion` esté en el catálogo de RBAC. Un nombre de archivo como `ocupacion.test.mjs` se
leía como si fuera un permiso y, al no existir en el catálogo, hacía fallar la suite `Feature`
completa, no solo la prueba del dashboard.

**Corrección.** Las pruebas del módulo se movieron a `resources/js/modules/ocupacion/*.test.mjs`
y la documentación deja de usar nombres con ese patrón.

**Prueba de regresión.** La suite `Feature` completa se ejecuta en verde tras el cambio; el
resultado registrado en el PR #574 es `OK (78, 1295)`.

**Estado:** cerrado y verificado.

## Límites del registro

- Se registran los defectos **del módulo de ocupación**. Los defectos detectados en otros módulos
  se reportan a su dueño y no se registran aquí.
- No hay defectos abiertos en el momento de esta entrega: la puerta de calidad exige cero
  hallazgos bloqueantes o mayores antes de integrar.
- El histórico de revisiones de cada PR queda en GitHub como evidencia complementaria.
