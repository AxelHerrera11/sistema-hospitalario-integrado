# Contrato API preliminar

## `POST /api/v1/lab-results`

Permiso: `lab-results.create`. Headers: `Authorization`, `X-Tenant-ID`, `Idempotency-Key`, `X-Correlation-ID`.

```json
{"order_item_id":"uuid","value":5.4,"unit":"mg/dL","comment":"captura inicial"}
```

Respuesta `201`:

```json
{"data":{"id":"uuid","status":"pending_review","version":1},"correlation_id":"uuid"}
```

## `POST /api/v1/lab-results/{id}/corrections`

Permiso: `lab-results.correct`. Requiere `reason`, valor y unidad. Crea una nueva version; nunca sobrescribe la anterior.

## Errores

`401` sin autenticacion, `403` sin permiso o tenant, `404` no encontrado, `409` version/idempotencia en conflicto, `422` regla de dominio, `503` dependencia no disponible.

## Consistencia y propiedad

Results API es propietaria de versiones y auditoria. Las consultas al catalogo son sincronas con timeout; si falla, no se publica el resultado. Todas las mutaciones registran actor, tenant, motivo y correlation ID.
