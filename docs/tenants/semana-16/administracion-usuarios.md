# Tenants — Semana 16 (parte 6): administración de usuarios desde la aplicación

## Objetivo

La API de administración ya existía (asignar y retirar roles, revocar el acceso al hospital), pero la pantalla
`/admin/users` solo listaba usuarios. Esta parte conecta esas operaciones a la interfaz para que el módulo de
usuarios y tenants sea usable sin herramientas externas.

## Qué se agregó

| Acción en pantalla | Endpoint | Permiso | Regla del backend que se respeta |
|---|---|---|---|
| Asignar rol (selector con los 8 roles canónicos que el usuario aún no tiene) | `POST /api/v1/admin/users/{user}/roles` | `rbac.asignar` | No autoasignación; superadmin solo lo asigna otro superadmin; usuario inactivo no recibe roles |
| Retirar rol (botón × en cada rol, con confirmación) | `DELETE /api/v1/admin/users/{user}/roles/{role}` | `rbac.asignar` | No autorretiro; el rol debe estar activo |
| Revocar acceso al hospital (con confirmación) | `POST /api/v1/admin/users/{user}/deactivate` | `usuarios.desactivar` | No sobre uno mismo; solo membresías del hospital del contexto |

- No cambia el backend. Las acciones solo se muestran si el usuario de la sesión tiene el permiso, y nunca en su propia fila. Es una
  ayuda de navegación: la autoridad sigue siendo `RequirePermission` y `ManageHospitalMembership`.
- Después de cada cambio se recarga la página actual, para mostrar lo que guardó el servidor.
- Los errores se traducen por código (`SELF_ROLE_CHANGE`, `SUPERADMIN_REQUIRES_SUPERADMIN`, `ROLE_ALREADY_ASSIGNED`,
  `MEMBERSHIP_NOT_FOUND`, `USER_INACTIVE`…); el detalle interno del servidor nunca se muestra.
- Accesibilidad: el selector tiene etiqueta (oculta visualmente), el botón × tiene `aria-label` con rol y usuario,
  y los avisos usan `role="status"` / `role="alert"`.

## Validación

```bash
node --test resources/js/modules/tenants/hospitalUsers.test.mjs   # pass 1, fail 0 (19 aserciones)
npx vite build   # HospitalUsersPage-*.js sin errores
```

## Pendiente

- Revocar sesiones ajenas (`DELETE /api/v1/admin/sessions/{session}`, `sesiones.revocar_ajenas`) no tiene pantalla:
  la API no publica un listado de sesiones activas, así que no hay de dónde tomar el identificador. Requiere un
  endpoint de lectura acordado con Marielos (Auth) antes de agregar la acción.
- Registrar hospitales (`POST /api/v1/admin/tenants`, `tenants.crear`) sigue solo por API.
