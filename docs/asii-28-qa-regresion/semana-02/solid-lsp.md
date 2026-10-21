# Aplicación de LSP al flujo de QA manual

## LSP en este diseño

Liskov establece que un consumidor debe poder utilizar una implementación concreta en lugar de su abstracción sin que se incumplan las garantías acordadas. Las implementaciones deben aceptar al menos las entradas válidas prometidas, producir los resultados definidos y respetar las invariantes del flujo.

Para este caso, el contrato común de registro de ejecución deberá preservar las asociaciones con caso y campaña, el resultado aprobado/fallido, las observaciones y las referencias a evidencia. Los elementos descritos aquí son conceptos de diseño, no componentes ya implementados.

## Diseño problemático: antes

El diseño conceptual concentra resultados distintos bajo una abstracción que exige operaciones no válidas para todos:

```text
ResultadoPrueba
  + registrarDefecto()
  + adjuntarEvidencia()
  + marcarAprobado()
       ├─ ResultadoAprobado
       └─ ResultadoFallido
```

Se supone que el consumidor recibe un `ResultadoPrueba` y puede invocar las operaciones de su contrato. `ResultadoAprobado::registrarDefecto()` lanza una excepción o rechaza la operación con “no soportado”; `ResultadoFallido` sí permite registrar el defecto. El subtipo aprobado no cumple el contrato general y no puede sustituirlo cuando el consumidor necesita usar cualquiera de esas operaciones.

El mismo problema aparecería si una variante de ejecución exigiera evidencia obligatoria mientras otra implementación del mismo contrato la omitiera silenciosamente, o si una implementación almacenara el resultado sin la relación con la campaña. En ambos casos se incumplen las garantías esperadas, aunque compartan nombres de operaciones.

## Diseño propuesto: después

Separar la información común de ejecución de la acción condicional de registrar defectos:

```text
Contrato de registro de ejecución manual
  pre: caso diseñado y asociado a la campaña
  entrada: observaciones y referencias de evidencia
  salida: ejecución vinculada + resultado aprobado/fallido

Registro de defecto
  pre: ejecución fallida identificada
  salida: defecto vinculado a ejecución, caso y campaña
```

El resultado de ejecución es un dato con un único valor de estado permitido por registro terminado. No se representan `Aprobado` y `Fallido` como subtipos que heredan operaciones mutuamente incompatibles. El contrato de registro no promete que cualquier ejecución admita un defecto; la operación específica de defectos declara como precondición una ejecución fallida.

El contrato podrá tener, por ejemplo, una implementación de prueba en memoria y otra de persistencia. Para ser sustituibles, ambas deben conservar las mismas asociaciones, resultado, observaciones y referencias de evidencia, y expresar los rechazos de precondición de la misma forma observable. Esas implementaciones son alternativas ilustrativas, no código existente ni tecnología seleccionada.

## Precondiciones, postcondiciones e invariantes

| Operación conceptual | Precondiciones | Postcondiciones e invariantes |
|---|---|---|
| Diseñar caso manual | Se suministran pasos y un resultado esperado comprensible. | El caso conserva ambos elementos; un caso incompleto no queda disponible como ejecutable. |
| Asociar caso a campaña | La campaña y el caso diseñado están identificados. | La asociación queda disponible para seleccionar el caso en una ejecución de esa campaña. |
| Registrar ejecución | Existe la asociación caso-campaña; se presentan observaciones y evidencia de la ejecución. | Se crea un registro con ambas relaciones, observaciones, evidencia y exactamente un resultado aprobado/fallido conforme a la comparación. |
| Registrar defecto | Existe una ejecución fallida y se suministra la descripción del defecto. | El defecto queda enlazado a la ejecución, y por trazabilidad a su caso y campaña. Una ejecución aprobada no satisface la precondición. |
| Consultar trazabilidad | Se identifica una campaña, un caso, una ejecución o un defecto existente. | La consulta devuelve solo elementos relacionados por las asociaciones del flujo y permite recorrer sus vínculos. |

Las implementaciones no deben fortalecer las precondiciones de un contrato sustituto. Si un caso válido para la implementación principal puede registrarse sin una condición adicional, un sustituto no puede exigir, por ejemplo, un formato de evidencia exclusivo que el contrato no declara. Tampoco debe debilitar las postcondiciones: no puede aceptar la solicitud y omitir la asociación a campaña o perder evidencia.

## Ejemplo académico de sustitución

El consumidor prepara un caso asociado a una campaña, captura observaciones y referencias de evidencia y solicita registrar la ejecución. La misma solicitud se envía primero a una implementación en memoria y luego a una implementación persistente conforme al contrato. En ambos casos obtiene un registro con el mismo resultado y las mismas relaciones; el consumidor no necesita preguntar cuál implementación está usando ni alterar su flujo.

Como contraejemplo, un sustituto que rechaza las referencias de evidencia válidas por requerir un formato particular añade una precondición. Otro que registra la ejecución pero omite la campaña debilita una postcondición. Ambos rompen la sustitución. La verificación consiste en aplicar el mismo conjunto de casos de contrato a cada implementación, incluidas las rutas aprobado, fallido, evidencia y registro de defecto.

## Responsabilidades

| Responsabilidad conceptual | Motivo de cambio separado |
|---|---|
| Diseño del caso | Cambia cuando cambian los campos o reglas de claridad de los casos manuales. |
| Gestión de campaña | Cambia cuando cambia cómo se seleccionan o agrupan casos para regresión. |
| Registro de ejecución | Cambia cuando cambian las garantías de captura de observaciones, evidencia y resultado. |
| Registro de defectos | Cambia cuando cambia la información o política para documentar defectos derivados de fallos. |
| Consulta de trazabilidad | Cambia cuando cambia la forma de navegar relaciones y presentar el historial. |
| Adaptador de persistencia | Cambia cuando cambia el mecanismo para guardar y recuperar información, sin cambiar el contrato. |

## Dependencias

- El consumidor del flujo depende del contrato de registro, no de una implementación concreta.
- El registro de ejecución requiere referencias válidas a caso y campaña; así conserva el origen de la prueba.
- El registro de defecto depende de una ejecución fallida y hereda desde ella la ruta al caso y a la campaña. No necesita duplicar esas asociaciones como datos independientes en el contrato conceptual.
- La consulta de trazabilidad depende de las relaciones entre conceptos; no debe reconstruirlas mediante suposiciones sobre nombres o formatos de evidencia.
- Las implementaciones de persistencia dependen del mecanismo de almacenamiento, pero no dictan el comportamiento observable del consumidor.

