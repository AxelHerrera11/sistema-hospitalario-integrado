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

## Seguimiento de los defectos

| ID | Severidad | Detectado en | Corregido en | Prueba de regresión | Estado |
|---|---|---|---|---|---|
| D-01 | Mayor | Revisión del PR #574 (21-oct-2026) | PR #574, commits `03cb198` | `test_una_base_hospital_inexistente_responde_503` | Cerrado |
| D-02 | Mayor | Revisión del PR #574 (21-oct-2026) | PR #574, commits `03cb198` | Suite `Feature` completa (`OK (78, 1295)`) | Cerrado |

Comandos para reproducir la evidencia de ambos:

```bash
$ php artisan test tests/Feature/Ocupacion
Tests:    16 passed (66 assertions)

$ php artisan test tests/Feature/Rbac/PublishedContractsTest.php
Tests:    1 passed (14 assertions)
```

## Criterio de registro

Un hallazgo entra en este registro cuando cumple al menos una de estas condiciones:

1. **Se detected en el módulo de ocupación** y ya está corregido, con prueba de regresión que
   falle antes de la corrección y pase después.
2. **Afecta al contrato o a la seguridad** del módulo, aunque lo detecte otra persona.
3. **Es un defecto de otro módulo**: se reporta a su dueño y no se registra aquí, salvo que
   impida cumplir un RF/RNF de ASII-09.

No se registran como defectos las funcionalidades no implementadas y declaradas (por ejemplo, el
filtro por sala): esas se listan como pendientes con responsable. No se registran observaciones
sin reproducción.

## Verificación de los criterios de registro

| Criterio | D-01 | D-02 |
|---|---|---|
| Detectado en el módulo de ocupación | Sí: provider y adaptador de persistencia | Sí: ruta del archivo de pruebas y suite de RBAC |
| Corregido antes de publicar esta entrega | Sí, en el PR #574 | Sí, en el PR #574 |
| Prueba de regresión que falla antes y pasa después | `test_una_base_hospital_inexistente_responde_503` | Suite `Feature` completa, que se rompía por el falso positivo |
| No es una funcionalidad no implementada | Es un comportamiento incorrecto (`500` en vez de `503`) | Es un defecto de la suite compartida, no una carencia |
| Reproducible con un comando | Sí: `php artisan test --filter=test_una_base_hospital_inexistente_responde_503` | Sí: `php artisan test --testsuite Feature` |
