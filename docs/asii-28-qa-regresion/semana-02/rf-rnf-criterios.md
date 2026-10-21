# Requisitos y criterios de aceptación

Estos requisitos describen el comportamiento esperado del flujo como propuesta funcional. No afirman que ya exista un sistema de gestión QA que los implemente.

## Requisitos funcionales

| ID | Requisito funcional | Criterio de aceptación |
|---|---|---|
| RF-01 | Diseñar un caso manual indicando su propósito, pasos y resultado esperado. | Dado un caso con pasos y resultado esperado definidos, cuando se guarda, entonces queda disponible como caso diseñado; un caso sin esos elementos se rechaza para evitar una ejecución ambigua. |
| RF-02 | Crear una campaña de regresión con una identificación y un conjunto de casos seleccionados. | Dada una campaña y casos diseñados, cuando se asocian, entonces la campaña conserva la relación con cada caso añadido. |
| RF-03 | Ejecutar manualmente un caso asociado a una campaña y registrar las observaciones obtenidas. | Dada una asociación existente entre caso y campaña, cuando el analista completa los pasos, entonces se registra una ejecución vinculada a ambos y se compara lo observado con el resultado esperado. |
| RF-04 | Registrar el resultado de la ejecución como aprobado o fallido. | Dado que el resultado observado coincide con el esperado, cuando se concluye la ejecución, entonces el resultado es aprobado; si no coincide, es fallido. |
| RF-05 | Asociar evidencia a la ejecución que documenta. | Dada una ejecución en curso, cuando se adjunta evidencia, entonces la evidencia queda identificada como perteneciente a esa ejecución y puede consultarse desde ella. |
| RF-06 | Registrar un defecto a partir de una ejecución fallida. | Dada una ejecución fallida, cuando se registra un defecto, entonces quedan relacionados defecto, ejecución, caso y campaña. Una ejecución aprobada no genera un defecto funcional por este flujo. |
| RF-07 | Consultar la cadena de trazabilidad de campaña a ejecución. | Dada una campaña, cuando se consulta su historial, entonces puede identificarse cada caso asociado, sus ejecuciones, resultados, evidencias y defectos relacionados, cuando existan. |
| RF-08 | Conservar las observaciones y el resultado de cada ejecución. | Dada una nueva ejecución del mismo caso en otra campaña o momento, cuando se registra, entonces no sustituye ni altera la evidencia y resultado de ejecuciones anteriores. |

## Requisitos no funcionales

| ID | Categoría | Requisito no funcional | Criterio verificable |
|---|---|---|---|
| RNF-01 | Integridad referencial | Los elementos relacionados deben conservar la cadena caso-campaña-ejecución-evidencia-defecto. | Para cada defecto registrado, es posible navegar a su ejecución y de ahí al caso y campaña; la evidencia se consulta desde su ejecución. |
| RNF-02 | Consistencia | El resultado aprobado/fallido debe corresponder a la comparación registrada. | Una ejecución con observación coincidente se registra aprobada y una no coincidente como fallida; no se guarda un resultado sin observación ni resultado esperado. |
| RNF-03 | Inmutabilidad histórica | Nuevas ejecuciones no deben reescribir la evidencia histórica. | Tras registrar una segunda ejecución, los datos asociados a la primera permanecen idénticos. |
| RNF-04 | Sustituibilidad (LSP) | Implementaciones que cumplen el contrato de registro de ejecución deben ofrecer garantías equivalentes. | La misma batería de contrato pasa para cada implementación sustituible sin cambios en el consumidor. |
| RNF-05 | Claridad de resultado | Los estados de resultado se expresan sin ambigüedad. | Toda ejecución terminada presenta exactamente uno de estos resultados: aprobado o fallido. |

## Escenarios de aceptación

### Resultado aprobado

```gherkin
Dado un caso asociado a una campaña
Y los pasos se completaron con observaciones que coinciden con lo esperado
Cuando se registra la ejecución manual
Entonces queda como aprobada
Y sus observaciones y evidencia quedan asociadas a la ejecución
```

### Resultado fallido y defecto

```gherkin
Dado un caso asociado a una campaña
Y las observaciones no coinciden con el resultado esperado
Cuando se registra la ejecución y el defecto correspondiente
Entonces la ejecución queda como fallida
Y el defecto permite volver a la ejecución, al caso y a la campaña
```

### Sustitución de almacenamiento

```gherkin
Dado el mismo caso, campaña, observaciones y evidencia
Cuando el consumidor registra la ejecución con cualquiera de dos implementaciones conformes
Entonces ambas conservan las mismas asociaciones y resultado
Y ninguna exige pasos adicionales propios de una implementación
```

El formato físico de evidencias, el ciclo de vida de defectos, las prioridades, severidades y estados de campaña no se fijan aquí porque no se han verificado estructuras que los definan.

