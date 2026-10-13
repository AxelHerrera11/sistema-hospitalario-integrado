# Contrato API preliminar

## `GET /api/v1/lab-results/pending-validation`

Permiso: `lab-results.validate`. Devuelve resultados pendientes del tenant actual con `id`, `version`, prueba, valor, unidad y rango.

## `POST /api/v1/lab-results/{id}/validation`

Headers: `Authorization`, `X-Tenant-ID`, `Idempotency-Key`, `X-Correlation-ID`.

```json
{"version":2,"decision":"approved","comment":"resultado dentro del rango"}
```

Decisiones permitidas: `approved`, `rejected`, `returned_for_correction`. Rechazo y devolucion requieren comentario.

## Respuestas y errores

`201` decision registrada, `401` no autenticado, `403` sin permiso/tenant, `404` resultado inexistente, `409` version obsoleta o decision repetida, `422` transicion o comentario invalido, `503` Results API no disponible.

## Consistencia

Validation API guarda la decision y emite un evento `lab.result.validated`. Results API actualiza el estado de su propia entidad de forma idempotente. Si el evento falla, queda en outbox para reintento y la auditoria conserva el estado de entrega.
