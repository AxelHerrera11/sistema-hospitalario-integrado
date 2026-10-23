# Semana 5 — Contrato API y frontera cliente-servidor

## Alcance

Propuesta documental de contrato para las cuatro operaciones de Prescripciones,
con permisos, errores y límites de datos. No agrega rutas ejecutables ni
declara validada una implementación.

## Entregables

- [Contrato OpenAPI](openapi.yaml)
- [Respuestas clínicas y errores](respuestas.md)
- [Decisiones de integración](integracion.md)

## Decisiones vigentes

- Las cuatro operaciones son crear, listar por paciente, consultar detalle y
  firmar. El detalle usa `GET /api/v1/prescriptions/{id}`.
- Todas las operaciones requieren `BearerAuth` y `TenantHeader` juntos.
- `mild` puede resultar en creación con advertencia; `moderate` y `unknown`
  responden 422 con `exception_allowed: true`; `severe` y `life_threatening`
  responden 422 sin excepción.
- Ningún resultado 422 persiste una prescripción.
- `excepcion_alergia` permanece pendiente y sin endpoint.

Los campos y esquemas marcados `PROPUESTO` requieren confirmación contra los
contratos finales de aplicación antes de implementar.
