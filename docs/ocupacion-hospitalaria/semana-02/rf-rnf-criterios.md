# RF/RNF y criterios de aceptación

Los requisitos se derivan de los casos `UC-OCC-01` a `UC-OCC-06` documentados en la [Semana 1](../semana-01/narrativa-alcance-trazabilidad.md).

## Requisitos funcionales

| Código | Trazabilidad | Requisito | Criterio de aceptación |
|---|---|---|---|
| RF-OCC-01 | UC-OCC-01, 06 | El sistema debe permitir que un usuario autorizado consulte la ocupación del hospital activo. | Con sesión, hospital y permiso válidos responde un resumen fechado; si falta alguno, rechaza la solicitud antes de consultar el inventario. |
| RF-OCC-02 | UC-OCC-02, 03 | El sistema debe considerar ocupada una cama en `T` cuando exista una estancia con `started_at <= T` y (`ended_at IS NULL` o `ended_at > T`). | Una estancia futura o terminada en `T` no ocupa; una estancia que contiene `T` sí ocupa. |
| RF-OCC-03 | UC-OCC-03, 05 | El sistema debe clasificar cada cama una sola vez como ocupada, disponible, mantenimiento, inactiva o `occupied` no verificada, y reportar discrepancias. | Con estancia activa se clasifica ocupada; sin estancia usa `available`, `maintenance` o `inactive`; `occupied` sin estancia queda no verificada. La suma coincide con el inventario y las inconsistencias no duplican camas. |
| RF-OCC-04 | UC-OCC-02 | El sistema debe consolidar cantidades por sala y globales, y calcular `ocupadas / (ocupadas + disponibles) * 100`. | Los totales globales equivalen a la suma por sala y la tasa se redondea a dos decimales. |
| RF-OCC-05 | UC-OCC-04 | El sistema debe aceptar un filtro opcional de sala perteneciente al hospital activo. | Una sala válida limita todos los agregados; una sala ajena o inexistente se rechaza sin revelar ni devolver datos de esa sala. |
| RF-OCC-06 | UC-OCC-01, 02 | El sistema debe representar inventario vacío y ausencia de capacidad operativa sin inventar porcentajes. | Sin camas devuelve cantidades en cero; si `ocupadas + disponibles = 0`, la tasa es no aplicable. |
| RF-OCC-07 | UC-OCC-01 | El sistema debe fallar de forma explícita cuando no pueda completar la lectura. | Si la base HOSPITAL falla, no presenta cifras parciales y comunica indisponibilidad. |

## Requisitos no funcionales

| Código | Categoría | Requisito | Criterio medible |
|---|---|---|---|
| RNF-OCC-01 | Seguridad | Toda consulta debe respetar autenticación, RBAC y hospital activo. | El 100 % de pruebas sin sesión, permiso o tenant válido falla sin devolver cifras; ninguna sala de otro hospital aparece. |
| RNF-OCC-02 | Consistencia | Una respuesta debe usar una sola fecha de corte y una fotografía atómica de los datos. | Todas las salas y el resumen global incluyen el mismo `cutoff_at`; camas y estancias se leen en una consulta única o una transacción de solo lectura con aislamiento repetible. |
| RNF-OCC-03 | Integridad | Cada cama debe contarse exactamente una vez. | En pruebas con estados válidos y discrepantes, la suma de categorías siempre equivale a `total_beds`. |
| RNF-OCC-04 | Privacidad | La respuesta agregada no debe exponer datos de pacientes. | El esquema de salida no contiene nombre, documento, diagnóstico ni identificador del paciente o episodio. |
| RNF-OCC-05 | Rendimiento | La consulta debe responder en tiempo operativo. | Con hasta 10 000 camas del hospital, el resumen responde en <= 1 s en p95 en el entorno de pruebas acordado. |
| RNF-OCC-06 | Disponibilidad | Una falla de lectura no debe confundirse con ocupación cero. | En el 100 % de fallas simuladas se muestra indisponibilidad y no un estado vacío exitoso. |
| RNF-OCC-07 | Accesibilidad | Estado e inconsistencias no deben comunicarse solo mediante color. | Cada indicador incluye etiqueta y valor textual, y el foco permite recorrer filtros y resultados con teclado. |
| RNF-OCC-08 | Mantenibilidad | Los consumidores deben depender de interfaces pequeñas según ISP. | El controller usa solo `OccupancySummaryQuery`; el agregador solo `BedOccupancySnapshotReader`; el selector solo `WardCatalogReader`. |

## Escenarios de aceptación

### Consulta autorizada

```gherkin
Dado un usuario con sesión, hospital activo y permiso de consulta
Y un inventario con 10 camas ocupadas y 10 disponibles
Cuando consulta el dashboard en la fecha de corte T
Entonces recibe 20 camas operativas y una tasa de ocupación de 50.00 %
Y la respuesta no contiene datos identificables de pacientes
```

### Regla temporal

```gherkin
Dado una estancia que inició antes de T y termina después de T
Y otra estancia cuyo ended_at es exactamente T
Cuando se consolida la ocupación en T
Entonces la primera cama se clasifica ocupada
Y la segunda no se clasifica ocupada por esa estancia
```

### Discrepancia visible

```gherkin
Dado una cama con estancia activa y status available
Cuando se consolida el resumen
Entonces la cama se cuenta una vez como ocupada
Y el contador de inconsistencias aumenta en uno
```

### Sin capacidad operativa

```gherkin
Dado una sala cuyas camas están en mantenimiento o inactivas
Cuando se consulta su ocupación
Entonces ocupadas y disponibles son cero
Y la tasa de ocupación es no aplicable
```

### Falla de lectura

```gherkin
Dado que la base HOSPITAL no está disponible
Cuando el usuario consulta el dashboard
Entonces el sistema comunica indisponibilidad
Y no muestra cantidades parciales ni ocupación cero
```
