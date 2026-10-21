# Integración RBAC y auditoría persistente

## DenialRecorder

`App\Rbac\AuditDenials` implementa `MicroHis\Rbac\Domain\DenialRecorder` y sustituye
al adaptador provisional `LogDenials` mediante el contenedor de Laravel. El motor
RBAC no se modifica: sigue generando el mismo arreglo de denegación con
`user_id`, `hospital_id`, `permission`, `reason` y `trace_id`.

Cada denegación se persiste como evento `rbac.access_denied` en `audit_log`. Si la
escritura falla, `TrackedDenialRecorder` lo detecta y la petición termina en
`503 RBAC_UNAVAILABLE`; nunca se autoriza la operación.

## Contrato de `AuditLogRepositoryInterface::logRaw`

```php
public function logRaw(string $eventName, array $payload, ?string $severity = null): void;
```

La implementación usa la conexión `central` y escribe las columnas del contrato
de `micro-his/rbac/database/schema.sql`:

- `event_id` y `occurred_at`: identificador y fecha/hora del evento.
- `event`: nombre del evento, por ejemplo `rbac.access_denied`.
- `actor_id`: `payload.user_id` o, en su defecto, `payload.actor_id`.
- `hospital_id`: `payload.hospital_id` o, en su defecto, `payload.tenant_id`.
- `detail`: JSON con el payload completo, conservando `user_id`, `hospital_id`,
  `tenant_id`, `permission`, `reason` y `trace_id`.
- `trace_id`: identificador de correlación de la decisión.
- `result`, `previous_hash` y `event_hash`: resultado y encadenamiento SHA-256.

La migración incremental agrega una cadena protegida por bloqueo transaccional
del estado de la cadena. Un trigger PostgreSQL rechaza `UPDATE` y `DELETE` en
`audit_log`. La aplicación omite del detalle claves que parezcan contraseñas,
tokens, secretos, cookies, credenciales o códigos MFA. La política temporal de
retención sigue pendiente de aprobación de cumplimiento; hasta entonces no hay
purga automática ni se deben borrar eventos. Esta protección detecta cambios y
bloquea modificaciones ordinarias; no protege frente a un superusuario de la
base capaz de alterar triggers o tablas.

Los eventos de administración usan `user.role_assigned`, `user.role_revoked` y
`user.hospital_memberships_deactivated`; el actor queda en `actor_id` y el
usuario afectado en `detail.target_user_id`.
Login exitoso/fallido y renovación/reutilización de refresh también dejan eventos.
Los eventos de MFA, bloqueo progresivo y recuperación no existen todavía porque
esos flujos no están implementados.

## Permisos administrativos aprobados

Se aprobaron estas concesiones administrativas explícitas:

| Operación | Permiso requerido |
| --- | --- |
| Listar usuarios | `usuarios.ver` (`admin_hospital`, `superadmin`) |
| Revocar acceso hospitalario | `usuarios.desactivar` (`admin_hospital`, `superadmin`) |
| Crear tenant | `tenants.crear` (`superadmin`) |
| Revocar sesión ajena | `sesiones.revocar_ajenas` (`admin_hospital`, `superadmin`) |

Los permisos se conceden sólo en la matriz declarativa; no se cambia ningún rol
canónico. El listado de usuarios pagina y filtra membresías activas del hospital
actual y devuelve sólo identidad, estado y roles; no consulta credenciales. La
creación de hospital requiere `tenants.crear`, crea el registro CENTRAL inactivo
y requiere aprovisionamiento externo antes de habilitar el hospital. La
revocación de sesiones ajenas sólo selecciona sesiones vigentes de otro usuario
del hospital actual. La desactivación local revoca las membresías y sesiones del
hospital actual; no cambia el estado global del usuario ni sus otros hospitales.
No existe todavía un endpoint de alta/activación de usuarios. No se concede
ningún permiso por herencia ni comodín.

## Reglas de administración de roles

- La asignación y revocación requieren `rbac.asignar` y se ejecutan contra el
  hospital activo del token.
- Para revocar acceso hospitalario, el administrador debe tener una membresía
  activa en ese mismo hospital; la operación no afecta otros tenants.
- Un actor no puede modificarse sus propios roles.
- Solo un actor con rol vivo `superadmin` puede asignar o retirar `superadmin`.
- Se valida que el usuario objetivo exista y esté activo.
- Toda asignación o revocación revoca las sesiones del usuario objetivo en ese
  hospital (`roles_changed`), de modo que el token anterior deja de ser válido.
- La desactivación local marca inactivas las membresías del usuario en el
  hospital actual y revoca allí las sesiones con motivo `hospital_access_revoked`;
  no desactiva la cuenta CENTRAL ni afecta otros hospitales.
- El cambio de membresía y su auditoría se confirman en la misma transacción.
