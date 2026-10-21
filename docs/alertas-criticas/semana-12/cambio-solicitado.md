# Caso práctico de diseño — cambio solicitado (ASII-21)

**Pedido:** Dirección médica pide que la **bandeja de la jefatura** muestre también las alertas **ya escaladas de otros médicos**, para intervenir antes de que se agote el escalamiento.

## Impacto por capa

| Pieza | ¿Cambia? | Qué cambia |
|---|---|---|
| Domain | No | El nivel y el destinatario ya existen en `CriticalAlert` |
| Application | Sí | `GetInbox` acepta el filtro «servicio» para quien tenga el permiso nuevo |
| Contrato API | Sí, compatible | `GET /api/v1/critical-alerts?scope=service` y el permiso `alertas.supervisar` |
| UI | Sí | Pestaña «Escaladas del servicio» con el médico original y los minutos vencidos |
| Accesibilidad | Revisar | Las alertas de otros no deben anunciarse con `role="alert"`, para no saturar |
| Riesgo | Medio | Ver datos de pacientes ajenos: el permiso nuevo es solo para jefaturas y queda auditado |

## Cómo lo absorbe el diseño

El cambio entra por una extensión compatible del contrato y por la UI. Las reglas que protegen al paciente (las de la [matriz](./matriz-decision-rf-rnf.md)) no se tocan. Antes de implementarlo hay que actualizar el flujo UX (Semana 8) y repetir el recorrido del prototipo (Semana 11) con el estado nuevo.
