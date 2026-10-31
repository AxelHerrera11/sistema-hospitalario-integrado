# Contrato API preliminar (semana 5)

> Documento de la entrega de la semana 5. El contrato **vigente** del módulo es
> [`../contrato-api.md`](../contrato-api.md), publicado en el PR #342 y ya aplicado por el
> endpoint `GET /api/v1/occupancy` del PR #574. Este archivo conserva la versión preliminar de la
> semana, con el token estático del prototipo, para trazabilidad de la entrega.

## Consulta de ocupación

`GET /api/v1/occupancy?cutoff_at={ISO-8601}`

| Elemento | Contrato |
|---|---|
| Autenticación | `Authorization: Bearer <token>`; el prototipo usa secreto local y producción debe sustituirlo por JWT validado |
| Hospital | `X-Tenant-ID`; cada instancia del prototipo se configura junto con un único DSN HOSPITAL y rechaza cualquier otro tenant |
| Trazabilidad | `X-Request-ID` opcional, máximo 64 caracteres seguros; el servidor genera uno si falta |
| Permiso futuro | `ocupacion.ver`, validado por gateway/HIS antes de invocar el módulo |
| Fecha | `cutoff_at` opcional en formato canónico `YYYY-MM-DDThh:mm:ss±hh:mm`; si falta se usa la hora del servidor |
| Semántica | Consulta de solo lectura; no requiere idempotency key ni modifica camas o estancias |

Respuesta `200`:

```json
{"data":{"cutoff_at":"2026-10-14T10:00:00-06:00","global":{"total_beds":20,"occupied":12,"available":6,"maintenance":1,"inactive":0,"unverified_occupied":1,"inconsistencies":2,"occupancy_rate":66.67},"wards":[]},"meta":{"request_id":"req-123"}}
```

La fotografía se calcula con una sola lectura preparada. Una cama se considera ocupada cuando `started_at <= cutoff_at` y `ended_at` es nulo o posterior al corte. `beds.status` no reemplaza el intervalo y cualquier discrepancia se informa.

## Errores

Todos devuelven `error.code`, mensaje seguro y `meta.request_id` cuando la solicitud alcanza el handler.

| HTTP | Código | Condición |
|---:|---|---|
| 400 | `INVALID_CUTOFF` | Fecha no escalar o fuera del formato canónico indicado |
| 401 | `UNAUTHENTICATED` | Token ausente o inválido |
| 403 | `TENANT_FORBIDDEN` | Hospital distinto al autorizado |
| 404 | `NOT_FOUND` | Ruta desconocida |
| 405 | `METHOD_NOT_ALLOWED` | Método distinto de GET |
| 503 | `OCCUPANCY_UNAVAILABLE` | Base HOSPITAL no disponible; nunca se responde ocupación cero |

## Operación

- **Resiliencia:** timeout de cliente de 2 s; un reintento con jitter solo ante fallo de conexión o `503`. El dashboard conserva la última fotografía marcada como desactualizada, nunca como actual.
- **Observabilidad:** log estructurado sin datos clínicos con request ID, tenant interno, estado, duración y edad del corte; métricas de latencia p95, tasa 5xx e inconsistencias.
- **Consistencia:** lectura fuerte dentro de una base HOSPITAL mediante una sentencia. Entre hospitales no existe una transacción global; cada respuesta identifica su corte.
- **Seguridad:** TLS en tránsito, JWT y permiso en producción, secretos fuera del repositorio, rate limit y lista permitida de hospitales. El despliegue debe aprovisionar `MICROHIS_TENANT_ID` y `MICROHIS_PG_DSN` como una pareja indivisible; el token estático solo demuestra el flujo local.
