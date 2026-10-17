# Requisitos y criterios de aceptación

## Requisitos funcionales

Los requisitos describen el comportamiento esperado del proceso de creación del catálogo. Los nombres de campos y valores de estado se ajustan a las tablas `wards` y `beds` de la migración hospitalaria.

| ID | Requisito funcional | Criterio de aceptación |
|---|---|---|
| RF-01 | Crear una sala con `code`, `name` y `location`. | Dado que los tres datos son válidos y el código no existe, cuando se registra la sala, entonces queda creada con su identificador y estado predeterminado `active`. |
| RF-02 | Rechazar la creación de una sala cuyo `code` ya esté registrado. | Dada una sala con el mismo código, cuando se intenta crear otra, entonces se rechaza la operación y se conserva un solo registro para ese código. |
| RF-03 | Crear una cama asociada a una sala existente, con `code` y `type`. | Dada una sala existente, cuando se crea una cama con código disponible dentro de esa sala, entonces la cama conserva la referencia a `ward_id` y su estado predeterminado es `available`. |
| RF-04 | Impedir camas sin sala o con código repetido dentro de la misma sala. | Dada una sala inexistente o una cama con el mismo código en la misma sala, cuando se intenta guardar, entonces el registro es rechazado. |
| RF-05 | Permitir expresar el estado de una sala usando únicamente `active` o `inactive`. | Dado un estado fuera de los valores permitidos, cuando se intenta guardar la sala, entonces se rechaza el cambio y se conserva el estado anterior. |
| RF-06 | Permitir expresar el estado de una cama usando únicamente `available`, `occupied`, `maintenance` o `inactive`. | Dado un estado fuera de los valores permitidos, cuando se intenta guardar la cama, entonces se rechaza el cambio y se conserva el estado anterior. |

## Requisitos no funcionales

| ID | Categoría | Requisito no funcional | Criterio verificable |
|---|---|---|---|
| RNF-01 | Integridad | La persistencia debe respetar claves, referencias, unicidad y dominios de estado declarados en el esquema. | La prueba de persistencia comprueba que los casos RF-02, RF-04, RF-05 y RF-06 fallan sin dejar datos inválidos. |
| RNF-02 | Consistencia | Cada creación se confirma completa o no deja registro creado. | Al provocar un fallo durante la persistencia, no queda ward o cama parcialmente registrada. |
| RNF-03 | Mantenibilidad | Las validaciones de entrada, coordinación de creación y persistencia tienen responsabilidades distinguibles. | Una modificación en el formato de entrada no requiere cambiar la política de estado ni la consulta de persistencia, salvo que cambie el requisito respectivo. |
| RNF-04 | Sustituibilidad de componentes | Los componentes de persistencia pueden reemplazarse sin cambiar las reglas observables del flujo. | Una implementación alternativa de persistencia pasa los mismos casos de aceptación que la implementación principal. |

## Escenarios de aceptación

### Creación de sala

```gherkin
Dado que no existe una sala con el código "UCI-A"
Cuando se registra con nombre y ubicación válidos
Entonces se crea la sala con estado "active"
Y una nueva sala no puede duplicar el código
```

### Creación de cama vinculada

```gherkin
Dado que existe la sala "UCI-A"
Y no existe una cama con código "C-01" en esa sala
Cuando se crea la cama con su tipo
Entonces queda vinculada al ward correspondiente
Y su estado inicial es "available"
```

### Estado fuera del dominio permitido

```gherkin
Dado que una cama tiene un estado válido
Cuando se intenta guardar un estado distinto de
  "available", "occupied", "maintenance" o "inactive"
Entonces se rechaza el cambio
Y se conserva el estado previo
```

Los escenarios usan códigos como datos de ejemplo, no como valores fijados por el esquema. El esquema no define reglas de transición entre estados; por eso los criterios validan los valores permitidos, sin imponer una secuencia de cambios no documentada.

