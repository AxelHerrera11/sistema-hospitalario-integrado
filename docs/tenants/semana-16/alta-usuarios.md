# Semana 16 — Alta de usuario por tenant (parte 9)

Completa el proceso del módulo, «alta de usuario por tenant, autenticación segura y
revocación de sesión», en la aplicación Laravel. Hasta ahora el alta solo existía en el
micro-HIS vanilla en memoria (Semanas 3–5); `/admin/users` permitía ver y administrar
usuarios existentes, pero no crear nuevos.

## Contrato

| Método | Ruta | Permiso | Respuesta |
|---|---|---|---|
| POST | `/api/v1/admin/users` | `usuarios.crear` (superadmin, admin_hospital) | 201 con `user_id`, `email`, `display_name`, `status`, `hospital_id`, `roles` |

Cuerpo: `email`, `display_name`, `password`, `role_code` (primer rol en el hospital de la sesión).

| Error | Código | Causa |
|---|---|---|
| 422 | validación `password` | menos de 12 caracteres o sin mayúscula, minúscula, número y símbolo |
| 422 | `ROLE_INVALID` | rol no canónico |
| 403 | `RBAC_DENIED` | el rol del actor en este hospital no tiene `usuarios.crear` |
| 403 | `SUPERADMIN_REQUIRES_SUPERADMIN` | un admin_hospital intenta crear un superadmin |
| 409 | `EMAIL_TAKEN` | el correo ya existe (sin distinguir mayúsculas) |

## Reglas

- Una sola transacción CENTRAL: `central_users`, `auth_credentials` (hash con `Hash::make`) y
  `hospital_memberships`. Si algo falla, no queda un usuario a medias.
- La membresía se crea solo en el hospital de la sesión (`X-Tenant-ID`); el usuario no obtiene
  acceso a otros hospitales.
- El correo se normaliza (minúsculas, sin espacios); una carrera entre dos altas la resuelve la
  restricción única de `auth_credentials.email_normalized`.
- Auditoría `user.created` con actor, usuario creado, hospital, rol y `trace_id`; sin correo ni
  nombre en el contexto.
- No hay migraciones: usa las tablas existentes.

## Validación

`php vendor/bin/phpunit tests/Feature/Tenants tests/Feature/Rbac` → `OK (112 tests, 4614 assertions)`.
`CreateHospitalUserTest` cubre camino feliz con inicio de sesión del usuario nuevo, correo
repetido, contraseña débil, superadmin, rol sin permiso y administrador de otro hospital.

## Pendiente

- Verificación de contraseñas filtradas (HIBP) en el alta: hoy solo existe en el micro-HIS.
- Pantalla para revocar sesiones ajenas: falta un endpoint de listado de sesiones.
