# Contrato API REST — RBAC v1

## Convenciones

- **Base:** todas las rutas cuelgan de `/api/v1`.
- **Formato:** JSON en petición y respuesta (`Content-Type: application/json`).
- **Códigos:** `200`, `201`, `400`, `401`, `403`, `404`, `422`, `503`.
- **Trazabilidad:** todas las respuestas incluyen `trace_id` (SD-RBAC-16).
- **Autenticación:** delegada a Usuarios/Auth; RBAC solo autoriza (SD-RBAC-14).
- **Identidades:** el **solicitante** (autenticado) y el **hospital** (tenancy) provienen del contexto de autenticación, no del cuerpo. `user_id` en el cuerpo es el **usuario evaluado** (puede diferir del solicitante). El handler PHP vanilla simula el contexto con `X-User-ID`/`X-Tenant-ID`, que **no** son autoritativas; en un controlador Laravel provienen de la sesión y del middleware de tenancy.
- **Idempotencia:** `POST /authorize` es una consulta sin efectos sobre el estado.
- **Estado:** `POST /api/v1/authorize` está implementado por `RbacHttpHandler::authorize`; el resto de endpoints sigue *propuesto*. Depende de **PR #70** (Semana 4, ya **mergeado**), que aporta `RoleResolver` e `InMemoryRbacStore`.
- **Permiso de endpoint obligatorio:** el handler debe configurarse con el permiso que protege el endpoint (p. ej. `rbac.autorizar`). Si falta, falla cerrado con `503` `endpoint_no_configurado`; nunca autoriza sin guardia.

## Endpoints

| Método | Ruta | Permiso | Descripción |
|---|---|---|---|
| GET | `/api/v1/roles` | `rbac.roles.ver` | Lista roles |
| GET | `/api/v1/roles/{role}` | `rbac.roles.ver` | Detalle de un rol y sus permisos |
| POST | `/api/v1/roles/{role}/permissions` | `rbac.roles.asignar` | Asigna/actualiza permisos de un rol |
| POST | `/api/v1/authorize` | `rbac.autorizar` | Decide permitir/denegar una acción |
| GET | `/api/v1/permissions` | `rbac.permisos.ver` | Catálogo de permisos |
| GET | `/api/v1/denials` | `rbac.denegaciones.ver` | Historial de denegaciones (auditoría) |

## Dos permisos que no son alias

| Permiso | Operación | Dónde se aplica |
|---|---|---|
| `rbac.asignar` | Asignar o revocar una **membresía** (rol ↔ usuario) en un hospital | `AssignRole` del micro-HIS |
| `rbac.roles.asignar` | Cambiar el **conjunto de permisos de un rol** | `POST /roles/{role}/permissions` (propuesto) |

El contrato HTTP usa `rbac.roles.asignar` para editar roles; **no** reutiliza
`rbac.asignar`, que pertenece a la asignación de membresías. No hay traducción ni
alias entre ambos: un cliente que solo tenga `rbac.asignar` no puede editar roles, y
uno que solo tenga `rbac.roles.asignar` no puede asignar membresías.

## Ejemplo: `POST /api/v1/authorize`

Request:

```json
{ "user_id": "...", "action": "prescripciones.crear" }
```

`solicitante` y `hospital` viajan en el contexto autenticado (no en el cuerpo).

### Semántica de `user_id`

| Caso | Comportamiento |
|---|---|
| **Ausente** | Se evalúa al **solicitante** autenticado (`X-User-ID`). |
| **Presente y válido** (string no vacío, recortado) | Se evalúa al usuario indicado, dentro del hospital del solicitante. |
| **Presente e inválido** (`[]`, `null`, `""`, `false` o no string) | `422` `payload_invalido` con `trace_id`; **no** se invoca el caso de uso de autorización ni se sustituye por el solicitante. |

Response `200` — permitido:

```json
{ "allowed": true, "trace_id": "..." }
```

Response `200` — denegado (operación evaluada):

```json
{ "allowed": false, "reason": "permiso_faltante", "trace_id": "..." }
```

Response `401` — sin autenticación:

```json
{ "code": "no_autenticado", "trace_id": "..." }
```

Response `403` — solicitante sin permiso para invocar el endpoint (distinto de la denegación evaluada):

```json
{ "code": "operacion_no_autorizada", "trace_id": "..." }
```

Response `503` — fallo de dependencia o configuración (decisión *fail-safe*, siempre `allowed:false`):

```json
{ "allowed": false, "code": "registro_no_disponible", "reason": "registro_no_disponible", "trace_id": "..." }
```

El cuerpo `503` es uniforme: `allowed:false`, `code`, `reason` y `trace_id`. `code` y `reason` llevan el motivo: `registro_no_disponible` (falló `DenialRecorder`), `resolucion_no_disponible` (fallaron `RoleResolver`/`PermissionProvider`) o `endpoint_no_configurado` (el endpoint no tiene permiso configurado); nunca se expone el detalle interno de la excepción.

## Falla segura

Si `DenialRecorder` no responde, `authorize` **igual** devuelve `allowed: false`: nunca permite por defecto. El `503` (`registro_no_disponible`) solo informa que **no se pudo registrar** la denegación; la decisión sigue siendo denegar y **no se finge** una auditoría exitosa. Si falla la resolución de roles o permisos, se responde `503` (`resolucion_no_disponible`) con `allowed:false`, sin exponer el detalle de la excepción. Finalmente, si el endpoint no se configuró con su permiso, se responde `503` (`endpoint_no_configurado`): la ausencia de configuración nunca habilita el acceso.

## Propiedad de datos

RBAC es dueño de roles, permisos y denegaciones. **No** es dueño de usuarios: los resuelve `RoleResolver` (Usuarios/Auth). El contrato no expone base de datos ni esquema; solo recursos y acciones.

## Códigos de estado

| Código | Significado |
|---|---|
| `200` | Decisión o consulta exitosa |
| `201` | Recurso creado (asignación) |
| `400` | Petición malformada |
| `401` | Sin autenticación |
| `403` | Solicitante autenticado sin permiso para invocar el endpoint (`operacion_no_autorizada`) |
| `404` | Recurso inexistente |
| `422` | Payload inválido (incluye `user_id` presente pero inválido) |
| `503` | Dependencia caída (resolución de roles/permisos o registro de denegaciones) o endpoint sin permiso configurado (`endpoint_no_configurado`); decisión *fail-safe* |

## Versionado

El contrato se versiona en la ruta (`/api/v1`); un cambio incompatible sube a `/api/v2` sin romper a los clientes actuales.
