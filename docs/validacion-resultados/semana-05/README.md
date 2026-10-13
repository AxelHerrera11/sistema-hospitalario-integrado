# Semana 5 - Validacion de resultados por bioquimico

## Entregable

Contrato REST preliminar y plan de integracion para aprobar, rechazar o devolver resultados con trazabilidad.

## Frontera propuesta

Validation API es propietaria de decisiones, estados y auditoria de validacion. Results API conserva los valores y versiones. La comunicacion entre ambas usa identificadores de resultado/version y evita compartir tablas.

## Seguridad y resiliencia

- JWT, `X-Tenant-ID` y permiso `lab-results.validate`.
- Control optimista mediante `version` para evitar decisiones sobre datos obsoletos.
- Idempotencia por decision y `409` ante conflicto.
- Correlation ID, eventos de auditoria y metricas de aprobaciones/rechazos.

## Evidencia

- `fuentes/01-integracion-api.mmd`: secuencia de validacion.
- `contrato-api.md`: endpoints, permisos, payloads y errores.
