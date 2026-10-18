# RF/RNF y criterios de aceptación — Semana 2

**Módulo:** `ASII-08` · **Issue:** `Refs #9` · **Base:** [Semana 1](../semana-01/narrativa-alcance-trazabilidad.md) (`UC-TA-01`, `UC-TA-02`, `R-TA-01` a `R-TA-07`).

## Convenciones

- `T`: instante `timestamptz` único que el servicio propuesto fija después de bloquear y reutiliza en todas las escrituras.
- **Liberar** la cama de origen significa terminar la estancia del episodio en `T`. No implica que la cama quede `available`.
- **Estado:** *Existente* = restricción o prueba presente en el repositorio; *Propuesto* = diseño de esta entrega, aún sin implementar.
- **Refinamiento respecto de Semana 1:** los datos que no dependen del estado (estancia esperada, cama destino y `reason`) se validan al construir la solicitud, antes de abrir la transacción. Lo que depende del estado, incluido que el destino sea distinto del origen, se valida en `ejecutar()`.

## Etapas y resultados

| Etapa | Resultado posible | Garantía |
|---|---|---|
| 1. Construir la solicitud | `SolicitudInvalida(código)` | No hay operación ni transacción. No es un resultado de `ejecutar()`. |
| 2. Autorizar | `RechazoConfirmado(NO_AUTORIZADO)` | Se rechaza antes de abrir la transacción; sin cambios. |
| 3. `ejecutar()` | `ExitoConfirmado` | El `COMMIT` se confirmó. |
| 3. `ejecutar()` | `RechazoConfirmado(código)` | Regla incumplida antes de escribir o con `ROLLBACK` dentro de la transacción; sin cambios persistidos. |
| 3. `ejecutar()` | `FalloConfirmado(código)` | Solo cuando se sabe que la transacción **no** se confirmó (fallo de persistencia antes de `COMMIT` o `COMMIT` rechazado con resultado conocido); sin cambios persistidos. |
| 3. `ejecutar()` | `ResultadoIncierto` | Cualquier duda sobre si se confirmó, incluida la pérdida de conexión durante `COMMIT`: no garantiza `ROLLBACK` ni ausencia de cambios, no informa éxito y exige consultar el estado antes de reintentar. |

Códigos de construcción: `ESTANCIA_ESPERADA_REQUERIDA`, `DESTINO_REQUERIDO`, `REASON_VACIO`.

Códigos de rechazo de `ejecutar()`: `EPISODIO_NO_INPATIENT`, `EPISODIO_NO_ABIERTO`, `SIN_ESTANCIA_ABIERTA`, `ESTANCIA_DESACTUALIZADA`, `T_INVALIDA`, `DESTINO_INEXISTENTE`, `DESTINO_IGUAL_ORIGEN`, `DESTINO_NO_AVAILABLE`, `DESTINO_SOLAPADO`.

## Requerimientos funcionales

| ID | Caso | Requerimiento | Estado |
|---|---|---|---|
| RF-TA-01 | UC-TA-01 | Construir una solicitud de traslado solo con estancia esperada, cama destino y `reason` no vacío; si falta algo, `SolicitudInvalida`. | Propuesto (`reason` no vacío también es *Existente* en `bed_stays_reason_check`) |
| RF-TA-02 | UC-TA-02 | Construir una solicitud de alta solo con estancia esperada; si falta, `SolicitudInvalida`. | Propuesto |
| RF-TA-03 | Ambos | Autorizar antes de abrir la transacción HOSPITAL; roles concretos pendientes. | Propuesto |
| RF-TA-04 | Ambos | `ejecutar()` rechaza si el episodio no es `inpatient`, no está abierto, no tiene estancia abierta, la estancia actual difiere de la esperada o `T` no es posterior al inicio del episodio y de la estancia. | Propuesto (períodos: *Existente* en `care_episodes_status_period_check` y `bed_stays_period_check`) |
| RF-TA-05 | UC-TA-01 | En el traslado, `ejecutar()` rechaza un destino inexistente, igual a la cama de origen, con estancia que se solape con `[T, ∞)` o, según la decisión propuesta R-TA-01, con `status` distinto de `available`. | Inexistente/igual: Propuesto; solape: *Existente* (exclusión GiST); `available`: Propuesto, pendiente de coordinación |
| RF-TA-06 | UC-TA-01 | Con destino válido, cerrar la estancia de origen como `[inicio, T)` y después crear la de destino `[T, ∞)` para el mismo episodio, que sigue abierto. | Propuesto (el orden lo exige el *Existente* `bed_stays_open_care_episode_unique`) |
| RF-TA-07 | UC-TA-02 | En el alta, cerrar la estancia y el episodio (`closed`) en el mismo `T`, sin estancias abiertas del episodio. | Propuesto (la base no impide cerrar un episodio con estancia abierta) |
| RF-TA-08 | Ambos | `ejecutar()` informa exactamente uno de sus cuatro resultados; ante duda sobre la confirmación, `ResultadoIncierto`. | Propuesto |
| RF-TA-09 | Ambos | El éxito incluye la liberación común (cama de origen, estancia cerrada, `T`) y el resultado propio: estancia destino y episodio abierto en el traslado; episodio cerrado en el alta. | Propuesto |
| RF-TA-10 | Ambos | **Decisión propuesta, pendiente de coordinación con ASII-06, ASII-07 y ASII-09:** este flujo no modifica `beds.status`; liberar la ocupación no declara disponible la cama. | Propuesto · no implementado |

## Requerimientos no funcionales

| ID | Atributo | Requerimiento medible | Verificación prevista |
|---|---|---|---|
| RNF-TA-01 | Sustituibilidad (LSP) | Traslado y alta pasan la misma batería del contrato común; el cliente común no usa `instanceof` ni conversiones de tipo. | Demo en memoria (PR-2) |
| RNF-TA-02 | Sin cambios ante rechazo | Tras `SolicitudInvalida` o `RechazoConfirmado`, el estado observable es idéntico al anterior. | Demo en memoria (comparación de instantáneas); en PostgreSQL, propuesto |
| RNF-TA-03 | Atomicidad | Las escrituras de cada operación se confirman todas o ninguna, en una sola transacción HOSPITAL. | Propuesto; no se verifica en Semana 2 |
| RNF-TA-04 | Concurrencia | Con dos solicitudes que traen la misma estancia esperada, como máximo una obtiene éxito. | Propuesto (`FOR UPDATE` + índice único existente); no se verifica en Semana 2 |
| RNF-TA-05 | Integridad temporal | Sin solapes por cama, como máximo una estancia abierta por episodio, estancias dentro del período del episodio y estancias finalizadas inmutables. | *Existente* en el esquema y en `SchedulingAndBedIntegrityTest` (requiere PostgreSQL; no se ejecuta aquí); la demo modela estas reglas sin sustituirlas |
| RNF-TA-06 | Resultado incierto | Sin certeza de no confirmación: no se informa éxito ni fallo sin cambios, no se reintenta automáticamente y se consulta el estado. | Contrato declarado; la demo no simula `COMMIT` |
| RNF-TA-07 | Privacidad | Ejemplos con datos ficticios; los resultados exponen solo identificadores. | Revisión del documento y de la demo |
| RNF-TA-08 | Trazabilidad | Cada RF se traza a un caso de uso y a una regla de Semana 1. | Matriz en `evidencia-verificable.md` (PR-2) |

## Criterios de aceptación

| ID | Requisito | Dado | Cuando | Entonces |
|---|---|---|---|---|
| CA-01 | RF-TA-01, RF-TA-02 | una solicitud sin estancia esperada, o de traslado sin destino o con `reason` vacío | se construye | `SolicitudInvalida` con su código; no existe operación ni transacción |
| CA-02 | RF-TA-03 | un usuario sin autorización | solicita traslado o alta | `RechazoConfirmado(NO_AUTORIZADO)` antes de abrir la transacción |
| CA-03 | RF-TA-04 | un episodio `outpatient` o `emergency` | se ejecuta traslado o alta | `RechazoConfirmado(EPISODIO_NO_INPATIENT)` y estado sin cambios |
| CA-04 | RF-TA-04 | un episodio `inpatient` cerrado | se ejecuta traslado o alta | `RechazoConfirmado(EPISODIO_NO_ABIERTO)` y estado sin cambios |
| CA-05 | RF-TA-04 | un episodio abierto sin estancia abierta, o con una estancia actual distinta de la esperada | se ejecuta traslado o alta | `RechazoConfirmado(SIN_ESTANCIA_ABIERTA)` o `(ESTANCIA_DESACTUALIZADA)` y estado sin cambios |
| CA-06 | RF-TA-04 | un `T` no posterior al inicio del episodio o de la estancia | se ejecuta traslado o alta | `RechazoConfirmado(T_INVALIDA)` y estado sin cambios |
| CA-07 | RF-TA-05 | una cama destino inexistente, igual a la de origen, con estancia solapada con `[T, ∞)` o con `status` distinto de `available` | se ejecuta el traslado | `RechazoConfirmado` con el código de destino correspondiente y estado sin cambios |
| CA-08 | RF-TA-06 | un episodio `inpatient` abierto en la cama A con estancia actual = esperada, y una cama destino B que existe, es distinta de A, no tiene estancias que se solapen con `[T, ∞)` y tiene `status = available` (decisión propuesta) | se ejecuta el traslado en `T` | A queda `[inicio, T)`, B `[T, ∞)`, mismo episodio, episodio abierto |
| CA-09 | RF-TA-07 | un episodio `inpatient` abierto con estancia actual = esperada | se ejecuta el alta en `T` | estancia y episodio terminan en `T`, episodio `closed`, ninguna estancia abierta |
| CA-10 | RF-TA-09, RNF-TA-01 | el cliente común `AvisoOcupacionLiberada` | recibe un traslado o un alta exitosos | obtiene cama de origen, estancia cerrada y `T` en ambos casos, sin conocer el tipo |
| CA-11 | RF-TA-10 | un traslado o un alta exitosos | se observa la cama de origen | este flujo no modificó su `beds.status` |
| CA-12 | RF-TA-08 | un fallo de persistencia antes de `COMMIT`, o un `COMMIT` rechazado con resultado conocido | se informa el resultado | `FalloConfirmado`, transacción cerrada, sin cambios persistidos |
| CA-13 | RF-TA-08, RNF-TA-06 | una pérdida de conexión durante `COMMIT` o cualquier duda sobre la confirmación | se informa el resultado | `ResultadoIncierto`: no se informa éxito, no se reintenta y se consulta el estado |

## Fuera de alcance

Servicios productivos, API, UI, migraciones, sincronización de `beds.status`, estado `cancelled`, traslados entre hospitales y escrituras en CENTRAL.
