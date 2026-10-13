# RF/RNF y criterios de aceptacion

## Requerimientos funcionales

| Codigo | Requerimiento funcional | Criterios de aceptacion |
| --- | --- | --- |
| RF-VR-01 | El sistema debe listar resultados pendientes de revision por hospital activo. | Dado un bioquimico autenticado con `X-Tenant-ID`, cuando consulta la bandeja, entonces solo ve resultados pendientes del hospital indicado. |
| RF-VR-02 | El sistema debe mostrar el detalle necesario para decidir: paciente, muestra, prueba, valor, unidad, rango y observaciones tecnicas. | Dado un resultado pendiente, cuando el bioquimico abre el detalle, entonces visualiza los datos minimos para tomar decision sin editar el resultado original. |
| RF-VR-03 | El sistema debe permitir validar un resultado cuando cumple reglas clinicas y tecnicas. | Dado un resultado pendiente y consistente, cuando el bioquimico lo valida, entonces cambia a estado `validado` y queda listo para publicacion clinica. |
| RF-VR-04 | El sistema debe permitir rechazar un resultado con motivo obligatorio. | Dado un resultado pendiente, cuando se intenta rechazar sin motivo, entonces la operacion se rechaza y el estado no cambia. |
| RF-VR-05 | El sistema debe bloquear doble decision sobre el mismo resultado. | Dado un resultado ya validado o rechazado, cuando otro usuario intenta decidirlo, entonces recibe error de conflicto. |
| RF-VR-06 | El sistema debe registrar auditoria de la decision. | Dado una validacion o rechazo exitoso, cuando termina la transaccion, entonces existe un evento con usuario, fecha, decision, motivo y tenant. |
| RF-VR-07 | El sistema debe notificar al modulo correspondiente segun la decision. | Dado un resultado validado o rechazado, cuando se confirma la decision, entonces se emite una notificacion interna para publicacion o recaptura. |

## Requerimientos no funcionales

| Codigo | Requerimiento no funcional | Criterios de aceptacion |
| --- | --- | --- |
| RNF-VR-01 | Seguridad por rol y tenant. | Ningun usuario sin rol de bioquimico puede validar o rechazar resultados; ningun usuario accede a resultados de otro tenant. |
| RNF-VR-02 | Trazabilidad clinica. | Toda decision conserva evidencia de quien decidio, cuando, por que y sobre que version del resultado. |
| RNF-VR-03 | Consistencia transaccional. | Si falla la persistencia de la decision, auditoria o notificacion obligatoria, la operacion completa se revierte. |
| RNF-VR-04 | Control de concurrencia. | Dos decisiones simultaneas sobre el mismo resultado no pueden dejar estados contradictorios. |
| RNF-VR-05 | Minimizacion de datos sensibles. | Los eventos de auditoria y notificacion usan identificadores y no copian mas datos clinicos de los necesarios. |
| RNF-VR-06 | Independencia de infraestructura. | El caso de uso de decision no depende directamente de PDO, ORM, colas ni proveedores de correo. |

## Escenarios de aceptacion

### Validacion exitosa

```gherkin
Dado que existe un resultado pendiente de revision
Y el bioquimico tiene permiso en el hospital activo
Cuando confirma la validacion del resultado
Entonces el sistema cambia el estado a validado
Y registra auditoria de la decision
Y emite notificacion interna de resultado publicable
```

### Rechazo sin motivo

```gherkin
Dado que existe un resultado pendiente de revision
Cuando el bioquimico intenta rechazarlo sin indicar motivo
Entonces el sistema responde con error de validacion
Y conserva el estado pendiente
Y no registra una decision definitiva
```

### Conflicto por doble decision

```gherkin
Dado que un resultado fue validado por otro bioquimico
Cuando se intenta rechazar la misma version del resultado
Entonces el sistema responde con conflicto
Y no sobrescribe la decision existente
```
