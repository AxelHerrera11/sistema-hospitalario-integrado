# Catálogo de permisos y roles

## Permisos

Todos los permisos siguen el formato `modulo.accion`.

| Permiso | Descripción |
|---|---|
| `rbac.roles.ver` | Ver roles y sus permisos |
| `rbac.roles.asignar` | Asignar o actualizar permisos de un rol |
| `rbac.autorizar` | Solicitar una decisión de autorización |
| `rbac.permisos.ver` | Ver el catálogo de permisos |
| `rbac.denegaciones.ver` | Ver el historial de denegaciones |
| `prescripciones.ver` | Ver prescripciones |
| `prescripciones.crear` | Crear prescripciones |
| `prescripciones.firmar` | Firmar prescripciones |
| `prescripciones.excepcion_alergia` | Registrar excepción de alergia |
| `muestras.recibir` | Recibir muestras |
| `muestras.ver` | Ver muestras |
| `muestras.etiquetar` | Etiquetar muestras |

## Matriz rol × permiso

`X` indica que el rol tiene el permiso.

| Rol | rbac.roles.ver | rbac.roles.asignar | rbac.autorizar | rbac.permisos.ver | rbac.denegaciones.ver | prescripciones.ver | prescripciones.crear | prescripciones.firmar | prescripciones.excepcion_alergia | muestras.recibir | muestras.ver | muestras.etiquetar |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| superadmin | X | X | X | X | X | X | X | X | X | X | X | X |
| admin_hospital | X | X |  | X | X |  |  |  |  |  |  |  |
| medico |  |  | X |  |  | X | X | X | X |  |  |  |
| enfermeria |  |  | X |  |  | X |  |  |  | X | X | X |
| recepcion |  |  | X |  |  |  |  |  |  | X | X |  |

## Detalle por rol

- **superadmin:** todos los permisos del catálogo.
- **admin_hospital:** `rbac.roles.ver`, `rbac.roles.asignar`, `rbac.permisos.ver`, `rbac.denegaciones.ver`.
- **medico:** `rbac.autorizar`, `prescripciones.ver`, `prescripciones.crear`, `prescripciones.firmar`, `prescripciones.excepcion_alergia`.
- **enfermeria:** `rbac.autorizar`, `prescripciones.ver`, `muestras.recibir`, `muestras.ver`, `muestras.etiquetar`.
- **recepcion:** `rbac.autorizar`, `muestras.recibir`, `muestras.ver`.

## Permisos RBAC por endpoint

| Permiso | Endpoint |
|---|---|
| `rbac.roles.ver` | `GET /api/v1/roles`, `GET /api/v1/roles/{role}` |
| `rbac.roles.asignar` | `POST /api/v1/roles/{role}/permissions` |
| `rbac.autorizar` | `POST /api/v1/authorize` |
| `rbac.permisos.ver` | `GET /api/v1/permissions` |
| `rbac.denegaciones.ver` | `GET /api/v1/denials` |

## Reglas

- Las reglas del dominio se combinan con **AND**: el permiso se concede solo si todas lo permiten; sin reglas, se deniega por defecto.
- El motor genera el `trace_id` al denegar y mantiene la **falla segura**: si la bitácora falla, la decisión sigue siendo denegar.

## Nota

La asignación real de roles a usuarios depende de Usuarios/Auth (Cindy); aquí solo se define el catálogo de permisos y la matriz de roles.
