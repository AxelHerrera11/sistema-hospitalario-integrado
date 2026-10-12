# RF/RNF y criterios de aceptacion

## Requerimientos funcionales

| Codigo | Requerimiento funcional | Criterios de aceptacion |
|---|---|---|
| RF-IR-01 | El sistema debe listar resultados pendientes por hospital activo. | Dado un tecnico autenticado con `X-Tenant-ID`, cuando consulta la bandeja, entonces solo ve pruebas pendientes del hospital indicado. |
| RF-IR-02 | El sistema debe permitir capturar valor, unidad y observacion para una prueba pendiente. | Dado un resultado pendiente, cuando el tecnico ingresa datos validos, entonces el sistema registra el resultado y cambia el estado. |
| RF-IR-03 | El sistema debe validar tipo de dato, unidad esperada y rango tecnico. | Dado un valor fuera de formato, unidad distinta o rango invalido, cuando se intenta guardar, entonces responde con error de validacion y no persiste cambios. |
| RF-IR-04 | El sistema debe permitir correccion controlada con motivo obligatorio. | Dado un resultado existente corregible, cuando se solicita correccion sin motivo, entonces el sistema rechaza la operacion. |
| RF-IR-05 | El sistema debe conservar version anterior al corregir. | Dado un resultado corregido, cuando se consulta su historial, entonces se ve valor anterior, valor nuevo, usuario, fecha y motivo. |
| RF-IR-06 | El sistema debe auditar capturas y correcciones. | Dado una captura o correccion exitosa, cuando termina la transaccion, entonces existe un evento de auditoria asociado. |
| RF-IR-07 | El sistema debe notificar disponibilidad o revision segun el estado final. | Dado un resultado guardado, cuando queda disponible o pendiente de revision, entonces se registra una notificacion al actor correspondiente. |

## Requerimientos no funcionales

| Codigo | Requerimiento no funcional | Criterios de aceptacion |
|---|---|---|
| RNF-IR-01 | Seguridad por rol y tenant. | Ningun usuario sin permiso de laboratorio puede capturar o corregir resultados; ningun usuario accede a resultados de otro tenant. |
| RNF-IR-02 | Trazabilidad clinica. | Toda correccion conserva historial y motivo sin sobrescribir la version previa. |
| RNF-IR-03 | Consistencia transaccional. | Si falla la persistencia del resultado o la auditoria, la operacion completa se rechaza. |
| RNF-IR-04 | Minimizacion de datos sensibles. | Los eventos de auditoria no incluyen mas datos clinicos que los identificadores necesarios. |
| RNF-IR-05 | Mensajes de error claros. | Los errores indican campo y razon sin exponer datos sensibles ni detalles internos. |
| RNF-IR-06 | Extensibilidad de validaciones. | Agregar un nuevo tipo de prueba no obliga a modificar consumidores que solo capturan o auditan. |

## Escenarios de aceptacion

### Captura valida

```gherkin
Dado que existe una prueba pendiente para una muestra recibida
Y el tecnico tiene permiso de captura en el hospital activo
Cuando ingresa valor, unidad y observacion validos
Entonces el sistema guarda el resultado
Y registra auditoria
Y cambia el estado segun regla de revision
```

### Correccion sin motivo

```gherkin
Dado que existe un resultado corregible
Cuando el usuario intenta corregir el valor sin informar motivo
Entonces el sistema responde 422
Y no crea una nueva version
Y no modifica el resultado vigente
```

### Unidad incompatible

```gherkin
Dado que la prueba espera una unidad definida en catalogo
Cuando se envia un resultado con unidad distinta
Entonces el sistema rechaza la captura
Y muestra el error de unidad esperada
```
