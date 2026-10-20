# Requerimientos y criterios de aceptación

## Base y trazabilidad

Los requerimientos recorren el proceso en el orden registro → búsqueda → actualización segura. Los atributos utilizados en esta entrega se limitan al esquema verificable de `local_patients` y sirven como base para definir los requisitos, criterios de aceptación y validaciones del flujo.

## Requerimientos funcionales

| ID | Flujo | Requerimiento | Criterio de aceptación |
|---|---|---|---|
| RF-01 | Registro | Registrar un expediente local con los campos obligatorios definidos por `local_patients`. | Dado un registro válido, cuando se guarda, entonces existe un expediente con identidad local y global y se aplican los valores por defecto y restricciones del esquema. |
| RF-02 | Registro | Rechazar datos que infrinjan las restricciones del modelo. | Dado un código o `global_id` ya registrado, una fecha de nacimiento futura o un valor fuera de los conjuntos permitidos, cuando se intenta registrar, entonces no se persiste un expediente inválido. |
| RF-03 | Búsqueda | Consultar expedientes locales utilizando los datos presentes en el modelo. | Dado un criterio de búsqueda, cuando se consulta, entonces los resultados corresponden a expedientes locales y muestran información compatible con los campos existentes; una búsqueda sin coincidencias no altera datos. |
| RF-04 | Actualización | Actualizar de forma atómica datos permitidos del expediente sin cambiar su identidad ni eludir las restricciones del esquema. | Dado un expediente existente y cambios válidos, cuando se actualiza, entonces persisten los cambios y se conserva su identidad; si la validación falla, no queda una actualización parcial. |
| RF-05 | Actualización | Informar explícitamente cuando el expediente no existe o los cambios no pueden aplicarse. | Dado un identificador inexistente o datos incompatibles, cuando se solicita la actualización, entonces el resultado indica el rechazo y el expediente permanece sin cambios. |

## Requerimientos no funcionales

| ID | Categoría | Requerimiento | Criterio verificable |
|---|---|---|---|
| RNF-01 | Integridad | Las implementaciones respetan las restricciones de datos del esquema local. | En las pruebas del contrato, todos los intentos inválidos definidos en RF-02 son rechazados y no dejan registros inválidos. |
| RNF-02 | Consistencia | Una actualización es indivisible. | Al inducir un fallo durante el guardado, el expediente conserva el estado previo completo; no se observa una combinación parcial de valores. |
| RNF-03 | Sustituibilidad | Un consumidor del contrato no depende de comportamientos particulares de una implementación. | La misma batería de contrato pasa sin cambios para cada implementación registrada. |
| RNF-04 | Privacidad | El flujo expone únicamente los datos necesarios para el propósito de cada operación. | Las respuestas de búsqueda y los mensajes de rechazo no incluyen datos que el contrato no haya solicitado. |

## Escenarios adicionales

```gherkin
Escenario: fallo de validación en actualización
  Dado un expediente existente
  Cuando se envía una fecha de nacimiento posterior a la fecha actual
  Entonces se rechaza la operación
  Y se conserva el expediente previo sin cambios parciales
```

```gherkin
Escenario: buscar sin coincidencias
  Dado un criterio sin expedientes coincidentes
  Cuando se ejecuta la búsqueda
  Entonces se obtiene una colección vacía
  Y ningún expediente resulta modificado
```

El esquema no determina el formato del criterio de búsqueda, permisos, mensajes de interfaz ni una política de resolución de ediciones concurrentes. Esos aspectos quedan fuera de esta especificación para no convertir decisiones aún no presentes en requisitos implícitos.
