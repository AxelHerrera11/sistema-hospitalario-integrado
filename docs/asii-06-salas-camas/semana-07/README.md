# ASII-06 — Semana 7: camas por ward

Se agrega una consulta de solo lectura para camas asociadas a una ward por su
UUID. El alcance es `wards` y `beds`: no incluye `rooms`, CRUD, UI, ocupación
clínica ni asignación de pacientes.

`GET /api/v1/wards/{ward}/beds` sigue el flujo HTTP → middleware →
`WardCatalogController` → `CatalogService` → repositorios → adaptadores PDO.
Valida la ward y filtra camas por `beds.ward_id`; ordena por código e ID.
Utiliza el permiso existente `camas.ver` y middleware `api`, `tenant`,
`jwt.auth`, `rbac:camas.ver`.

Respuesta sin camas: `200`, `data: []`. UUID inválido o ward inexistente:
`404 WARD_NOT_FOUND`. Error de almacenamiento: `503 BED_CATALOG_UNAVAILABLE`,
sin detalles internos.

`TenantMiddleware` valida el hospital, pero la conexión `hospital` continúa
fija; no hay `tenant_id` en `wards`/`beds` ni selección dinámica de base por
tenant. No se afirma aislamiento multi-tenant completo.

Esta rama parte de `ASII06_SEMANA07_BASE` (`9f25214e0b997af7b0459abfeb290fcace1594bb`),
que contiene Semana 6. Su PR sigue pendiente de aprobación; no se duplica ese
trabajo. Resultados y riesgos en [evidencia-integracion.md](./evidencia-integracion.md).
