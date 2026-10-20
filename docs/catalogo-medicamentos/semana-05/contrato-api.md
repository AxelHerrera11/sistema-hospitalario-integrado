# Contrato API v1 - Catálogo de medicamentos (propuesto)

Especificación normativa: [`openapi.yaml`](./openapi.yaml) (OpenAPI 3.1). Estado: **diseño**. El backend Laravel no expone estas rutas; el micro-HIS las implementa como rutas locales sin `/api/v1` (ver la última tabla).

## Convenciones adoptadas del repositorio

| Convención | Fuente verificada |
|---|---|
| Prefijo `/api/v1` | README, "API base disponible"; `apiPrefix: 'api/v1'` en `bootstrap/app.php` |
| `Authorization: Bearer <JWT>` y `X-Tenant-ID` | README; middlewares `tenant` y `jwt.auth` en `routes/api.php` |
| Solicitante y hospital salen del contexto autenticado, no del cuerpo | `docs/rbac/semana-05/contrato-api.md` |
| `trace_id` en todas las respuestas | `docs/rbac/semana-05/contrato-api.md` (SD-RBAC-16) |
| Permisos `modulo.accion` y concesión literal por rol | `config/rbac.php` |

## Operaciones

| Método y ruta | Caso de uso | Permiso | Estado del permiso | Éxito |
|---|---|---|---|---|
| `GET /api/v1/medications?q=&limit=` | UC-MED-02 Buscar | `medicamentos.ver` | **Existe** (superadmin, admin_hospital, medico, enfermeria) | 200 |
| `GET /api/v1/medications/{medication_id}` | UC-MED-03 Consultar | `medicamentos.ver` | **Existe** | 200 |
| `POST /api/v1/medications` | UC-MED-01 Alta | `medicamentos.registrar` | **Pendiente**: catalogado en `config/rbac.php` (`pending`), sin rol activo | 201 |
| `PATCH /api/v1/medications/{medication_id}/validity` | UC-MED-04 Cambiar vigencia | `medicamentos.cambiar_vigencia` | **Pendiente**, igual que el alta | 200 |

Para los dos permisos se propone el rol `admin_hospital`, como gestor del catálogo de Semana 1. Desde #622 figuran en `rbac.catalog` y en `rbac.pending` con ese rol, pero ningún rol activo los recibe: activarlos requiere aprobación del módulo RBAC, y hasta entonces esas rutas deben responder 403 (denegación por defecto).

## Cuerpos

- **Alta:** `code`, `name`, `active_ingredient` y `pharmacological_group` (texto, obligatorios). Campos desconocidos: 422. `validity` no se acepta: todo alta nace `active`.
- **Vigencia:** `{ "validity": "active" | "inactive" }`. Repetir el estado actual: 422.
- **Medicamento:** `medication_id`, `code`, `name`, `active_ingredient`, `pharmacological_group` y `validity`. No hay presentación, lote ni fecha de vencimiento: no forman parte de este catálogo.

## Errores

Cuerpo único: `{ "error": "CODE", "message": "...", "field": "campo", "trace_id": "..." }`. `field` aparece solo cuando aplica.

| Estado | `error` | Cuándo |
|---|---|---|
| 400 | `BODY_INVALID` | El cuerpo no es un objeto JSON |
| 401 | `UNAUTHENTICATED` | Falta el JWT o no es válido |
| 403 | `FORBIDDEN` / `TENANT_MISMATCH` | Sin permiso, o `X-Tenant-ID` distinto del hospital del token |
| 404 | `MEDICATION_NOT_FOUND` | El identificador no existe en el hospital |
| 409 | `MEDICATION_CODE_DUPLICATED` | Código normalizado ya registrado en el hospital |
| 422 | `MEDICATION_INVALID` | Regla de dominio o tipo inválido (con `field`) |
| 503 | `CATALOG_UNAVAILABLE` | Fallo de persistencia; nunca se informa éxito |

## Del micro-HIS al API v1

| Micro-HIS (Semanas 3–4) | API v1 | Diferencia |
|---|---|---|
| `POST /medications` | `POST /api/v1/medications` | Agrega autenticación, permiso, tenant y `trace_id` |
| `GET /medications?q=` | `GET /api/v1/medications?q=&limit=` | `limit` opcional (1–50; el micro-HIS fija 50) |
| `GET /medications/{id}/validity` | `GET /api/v1/medications/{medication_id}` | Devuelve el medicamento completo con su vigencia, lo que necesita Prescripciones |
| `PATCH /medications/{id}/validity` | `PATCH /api/v1/medications/{medication_id}/validity` | Agrega permiso y `trace_id` |
