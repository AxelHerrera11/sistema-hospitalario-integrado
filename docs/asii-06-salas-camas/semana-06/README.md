# ASII-06 — Semana 6: API de wards

## Alcance

Se implementó una consulta autenticada de solo lectura `GET /api/v1/wards`,
reutilizando `CatalogService`, `WardRepository` y el adaptador PDO de Semana 5.
No hay `rooms`, CRUD, UI ni operaciones clínicas o de admisión.

El permiso existente `camas.ver` se aplica después de `api`, `tenant` y
`jwt.auth`. La respuesta solo expone `ward_id`, `code`, `name`, `location` y
`status`, más conteo e ID de solicitud. Lista vacía: `200`, `data: []`.
Persistencia no disponible: `503 WARD_CATALOG_UNAVAILABLE`, sin detalles
internos.

## Multi-tenant y validación

`TenantMiddleware` valida `X-Tenant-ID`, pero `hospital` sigue siendo una
conexión fija; `wards` y `beds` no tienen `tenant_id`, ni existe selección
dinámica de base por hospital. Por tanto, no se afirma aislamiento
multi-tenant completo. La prueba Feature cubre registro/middleware, éxito,
campos, ausencia de datos, autorización y errores; ver
[evidencia-api.md](./evidencia-api.md).
