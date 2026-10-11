# Narrativa de alcance y trazabilidad - Validacion de resultados por bioquimico

## Proposito

Garantizar que los resultados preliminares de laboratorio sean **revisados, aprobados o rechazados por un bioquimico autorizado** antes de quedar disponibles para uso clinico. El proceso protege la calidad del dato, conserva trazabilidad de la decision y evita publicar resultados inconsistentes o sin revision.

## Alcance funcional

**Incluido**

- Consulta de resultados preliminares o pendientes de validacion.
- Revision del contexto: paciente, orden, muestra, prueba, valor, unidad, rango, observaciones y versiones.
- Validacion tecnica de coherencia, rango, unidad y estado del resultado.
- Aprobacion de resultados para publicacion clinica.
- Rechazo o devolucion a correccion con observacion obligatoria.
- Registro de decision, usuario, fecha, tenant, motivo y trazabilidad.
- Notificacion al medico solicitante cuando el resultado queda aprobado.
- Notificacion al tecnico cuando el resultado se devuelve para correccion.

**Fuera de alcance**

- Captura inicial del resultado: modulo *Ingreso de resultados de laboratorio*.
- Recepcion fisica de muestras: modulo *Recepcion de muestras*.
- Definicion de catalogos, unidades y rangos: modulo *Catalogo de pruebas de laboratorio*.
- Escalamiento de alertas criticas: modulo *Alertas criticas y notificaciones internas*.
- Gestion de usuarios, roles y autenticacion.

## Supuestos y restricciones

- Solo resultados en estado `pendiente_validacion` o equivalente pueden ser aprobados por el bioquimico.
- El bioquimico debe pertenecer al mismo hospital/tenant del resultado.
- Toda decision debe guardar auditoria. Sin auditoria, la decision no se confirma.
- El rechazo o devolucion requiere observacion obligatoria.
- Un resultado aprobado no se edita directamente; cualquier cambio posterior debe generar nueva version o flujo de correccion.
- Las notificaciones no deben exponer informacion clinica innecesaria.

## Trazabilidad entre los diagramas

| UC | Objetivo | Actividad (decision o paso) | Secuencia (mensajes) |
|---|---|---|---|
| UC-VAL-01 | Consultar pendientes de validacion | "Consultar bandeja de validacion" | 1-8 |
| UC-VAL-02 | Revisar detalle del resultado | "Abrir resultado y revisar contexto" | 9-14 |
| UC-VAL-03 | Validar coherencia tecnica | Decisiones "Estado validable" y "Valor coherente" | 15-17 |
| UC-VAL-04 | Aprobar resultado | Rama "Aprobar" y "Publicar para consulta clinica" | 18-23 |
| UC-VAL-05 | Rechazar resultado | Rama "Rechazar" y "Registrar observacion" | 18-23 |
| UC-VAL-06 | Devolver para correccion | Rama "Devolver a tecnico" y "Notificar correccion" | 18-25 |
| UC-VAL-07 | Registrar auditoria | "Registrar decision trazable" | 21-22 |
| UC-VAL-08 | Notificar decision | "Notificar medico o tecnico" | 24-25 |

## Flujo principal

1. El bioquimico abre la bandeja de resultados pendientes de validacion.
2. El sistema valida autenticacion, tenant y permisos.
3. El bioquimico abre el detalle de un resultado preliminar.
4. El sistema muestra orden, muestra, prueba, valor, unidad, rango, observaciones y versiones.
5. El bioquimico revisa coherencia tecnica y decide aprobar, rechazar o devolver.
6. Si aprueba, el resultado queda disponible para consulta clinica.
7. Si rechaza o devuelve, el sistema exige observacion y cambia el estado para correccion o cierre.
8. El sistema registra auditoria y notifica a los actores correspondientes.

## Resultados y errores esperados

- `200 OK`: decision aplicada correctamente.
- `401 Unauthorized`: usuario no autenticado.
- `403 Forbidden`: usuario sin permiso de bioquimico o tenant incorrecto.
- `404 Not Found`: resultado inexistente para el tenant.
- `409 Conflict`: resultado ya validado o en estado no validable.
- `422 Unprocessable Entity`: decision invalida u observacion obligatoria ausente.
- `500 Internal Server Error`: fallo de persistencia o auditoria; no se confirma la decision.

## Dependencias con otros modulos

- **Ingreso de resultados de laboratorio:** entrega resultados preliminares o pendientes de revision.
- **Catalogo de pruebas de laboratorio:** provee unidades, tipo de valor y rangos.
- **Alertas criticas:** puede recibir eventos si un resultado aprobado requiere escalamiento.
- **Expediente medico:** consulta resultados aprobados.
- **Gobernanza y auditoria:** conserva traza de decisiones sensibles.

## Archivos relacionados

- [Casos de uso](./01-casos-de-uso.svg)
- [Actividades](./02-actividad.svg)
- [Secuencia](./03-secuencia.svg)
- [README del modulo](./README.md)
