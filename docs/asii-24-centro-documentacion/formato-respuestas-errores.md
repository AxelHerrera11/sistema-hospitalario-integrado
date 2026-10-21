# Formato común de respuestas y errores — acuerdo transversal

- **Versión:** 1.0 (propuesta)
- **Fecha:** 20/10/2026
- **Módulo:** ASII-24 — Contratos API: OpenAPI/Postman y documentación técnica
- **Redacta:** Lis Ivette Rosales Colindrez (`Lis671`)
- **Revisan:** Yamilet (integración), Javier (base de datos), María (RBAC)
- **Estado:** propuesto para aprobación del grupo; se aplica a los módulos nuevos y se adopta en los existentes cuando se toquen.
- **Base:** `/api/v1` (`routes/api.php` y los providers de cada módulo).

## 1. Reglas generales

1. Toda respuesta es JSON UTF-8 (`Content-Type: application/json`), salvo las descargas (`text/csv`).
2. No se devuelven trazas, SQL, nombres de tablas ni rutas internas.
3. Todo error 4xx/5xx usa el mismo sobre (`§3`) y un `code` estable.
4. `trace_id` correlaciona la respuesta con los logs; no sustituye al `code`.
5. Los códigos no cambian entre versiones: si cambia la semántica, se publica un `code` nuevo.
6. Un recurso de otro tenant responde igual que uno inexistente (`404`), sin revelar su existencia.
7. `401` y `403` no explican qué permiso falta ni qué rol se requiere.

## 2. Respuesta exitosa

Objeto único:

```json
{ "data": { "appointment_id": "…", "status": "scheduled" } }
```

Colección paginada:

```json
{
  "data": [ { "appointment_id": "…" } ],
  "meta": { "current_page": 1, "per_page": 25, "total": 120, "last_page": 5 }
}
```

- `201 Created` devuelve el recurso creado en `data`.
- `200 OK` para consultas y transiciones.
- No se usa `{ "success": true }`; la presencia de `data` indica éxito.
- Las descargas (`/export`) no usan sobre: devuelven el archivo y sus cabeceras.

## 3. Respuesta de error

```json
{
  "message": "La cita se solapa con otra cita activa del mismo médico.",
  "code": "appointments_overlap",
  "trace_id": "trace-4f2a9c1b7e30"
}
```

Validación (`422`) agrega `errors`:

```json
{
  "message": "Los datos enviados no son válidos.",
  "code": "validation_failed",
  "errors": { "ends_at": ["El fin debe ser posterior al inicio."] },
  "trace_id": "trace-4f2a9c1b7e30"
}
```

- `message`: texto en español para el usuario final; no expone detalles internos.
- `code`: `snake_case`, estable y documentado en el contrato del módulo.
- `trace_id`: se reutiliza `X-Request-Id` si cumple `^[A-Za-z0-9._-]{8,64}$`; si no, se genera `trace-` + 12 hex.
- `errors`: solo en `422`; mapa `campo → lista de mensajes`.

## 4. Códigos HTTP y `code`

| HTTP | `code` | Cuándo |
|---|---|---|
| 400 | `tenant_required` | Falta la cabecera `X-Tenant-ID`. |
| 400 | `tenant_invalid` | `X-Tenant-ID` no es un UUID. |
| 400 | `request_malformed` | Cuerpo JSON inválido o no interpretable. |
| 401 | `unauthenticated` | Token ausente, inválido o expirado. |
| 403 | `forbidden` | Autenticado, pero sin el permiso de la operación. |
| 403 | `tenant_mismatch` | El token pertenece a otro hospital. |
| 404 | `appointment_not_found`, `report_not_found` | Recurso inexistente **en el tenant**. |
| 405 | `appointments_delete_protection` | Operación no permitida (borrado físico prohibido). |
| 406 | `report_format_unsupported` | Formato de exportación no soportado. |
| 409 | `appointments_overlap`, `appointments_finalized_immutable` | Conflicto de estado o de exclusión. |
| 422 | `validation_failed` | Datos inválidos; incluye `errors`. |
| 422 | `report_filter_invalid`, `report_range_too_long`, `appointments_specialty_trigger`, `appointments_status_time_check` | Regla de dominio incumplida. |
| 429 | `rate_limited` | Límite de solicitudes; incluye `Retry-After`. |
| 500 | `internal_error` | Fallo no controlado; no se revela el detalle. |
| 503 | `service_unavailable` | Dependencia no disponible (base o RBAC); reintentable. |

- El `code` va en `snake_case`; los códigos heredados en mayúsculas se migran al tocar el módulo (`TENANT_REQUIRED` → `tenant_required`, `RBAC_DENIED` → `forbidden`, `AUTH_REQUIRED` → `unauthenticated`).
- Un recurso de otro tenant responde `404` con el mismo `code` que uno inexistente.
- Un `503` nunca se usa para validaciones: esas son `422`.

## 5. Validación (`422`)

- Se conserva el validador del framework y se traduce al sobre común.
- `message` resume el primer problema; `errors` enumera **todos** los campos.
- Las reglas de negocio usan `422` con un `code` de dominio; nunca `400`.
- Un filtro de paginación fuera de rango también es `422` (`validation_failed`).

## 6. Paginación y filtros comunes

| Parámetro | Regla |
|---|---|
| `page` | Entero ≥ 1; por defecto 1. |
| `per_page` | Entero entre 1 y 100; por defecto 25. |
| `order` | `asc` o `desc`; por defecto `asc` según el recurso. |
| `date_from`, `date_to` | `Y-m-d`; al menos uno; rango máximo de 366 días. |

- El sobre de una colección usa exactamente `current_page`, `per_page`, `total` y `last_page`.
- El tenant **no** es un filtro: lo impone `X-Tenant-ID`.
- Los filtros adicionales se documentan por recurso en su contrato.

## 7. Cabeceras

| Cabecera | Uso |
|---|---|
| `Authorization: Bearer <jwt>` | Obligatoria salvo `login` y `refresh`. |
| `X-Tenant-ID` | Obligatoria en rutas de tenant; UUID del hospital activo. |
| `X-Request-Id` | Opcional; si es válida se refleja en `trace_id`. |
| `Accept` | `application/json`; `text/csv` en exportaciones. |
| `Content-Type` | `application/json` en cuerpos con datos. |
| `Retry-After` | Solo en `429` y `503`. |
| `X-Report-Filters` | Solo en exportaciones: JSON de los filtros aplicados. |

- Las respuestas con datos clínicos o personales llevan `Cache-Control: no-store, private`.
- Los tokens no viajan en la URL ni se registran en logs.
- `X-Request-Id` no se valida como identidad: es solo correlación.

## 8. Reintentos e idempotencia

- `GET` y `PUT` son idempotentes; un `POST` de creación **no** se reintenta a ciegas.
- Se reintenta solo `429`, `502`, `503` y `504`, con espera exponencial y jitter, máximo 3 intentos.
- Todo reintento tiene salida: mensaje visible y acción manual; nunca un bucle.
- `409` y `422` no se reintentan: exigen corregir datos o resolver el conflicto.
- Un `Idempotency-Key` para `POST` críticos queda como propuesta futura, fuera de este acuerdo.
- Cada intento genera su propio `trace_id`; el cliente conserva el del último intento.

## 9. Descargas (`/export`)

- Éxito: `200` con `Content-Type: text/csv; charset=utf-8` y `Content-Disposition: attachment; filename="…"`.
- Un error de exportación responde con el sobre JSON de `§3`, nunca con un CSV parcial.
- Un formato no soportado responde `406` con `report_format_unsupported`; no se inventa un formato alternativo.

## 10. Ejemplos de error

```json
{ "message": "Credenciales inválidas.", "code": "unauthenticated", "trace_id": "trace-91ab22c47d10" }
```

```json
{ "message": "No tiene permiso para exportar este reporte.", "code": "forbidden", "trace_id": "trace-2c7e10f9a833" }
```

```json
{ "message": "La cita ya fue cancelada y no admite cambios.", "code": "appointments_finalized_immutable", "trace_id": "trace-77d0b6e1c412" }
```

```json
{ "message": "El formato pdf aún no está disponible.", "code": "report_format_unsupported", "trace_id": "trace-5e2f88a1b904" }
```

## 11. Adopción y compatibilidad

| Componente | Forma actual | Acción propuesta |
|---|---|---|
| `TenantMiddleware` | `{ "error", "message" }` en 400/404/403 | Al tocarlo: `{ "message", "code", "trace_id" }`. |
| `RequirePermission` (RBAC) | `{ "code", "trace_id" }` en 401/403/503 | Agregar `message` y mapear a `forbidden`/`unauthenticated`. |
| `AuthController` | Payload plano y `{ "error", "message" }` | Excepción documentada hasta una ventana de cambio acordada. |
| Catálogo de medicamentos | `{ "medication", …, "trace_id" }` | Alinear a `data` y al sobre de error en su siguiente PR. |
| Citas (ASII-05) | `{ "data", "meta" }` y `{ "message", "code" }` | Ya sigue el sobre; falta `trace_id`. |
| Reportes (ASII-27) | Contrato con `{ "message", "code" }` | Agregar `trace_id` al implementar. |

- Los módulos nuevos cumplen este acuerdo desde su primer PR.
- Ningún cambio de forma se hace sin aprobación del grupo; un cambio incompatible se anuncia con una semana de antelación.
- Este documento no crea permisos, tablas ni dependencias: solo fija la forma de las respuestas.

## 12. Referencias

- `docs/asii-26-dashboard-base/contrato-ui-base.md` §7 (interceptor y errores).
- `docs/asii-05-citas/contrato-api.md` §4 (errores de citas).
- `docs/asii-27-reportes/contrato-filtros-export.md` §2 y §4 (errores y CSV).
- `docs/asii-24-centro-documentacion/openapi.yaml` (esquema `Error` y `Pagination`).
