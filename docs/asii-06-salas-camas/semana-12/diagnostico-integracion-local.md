# Diagnóstico de integración local — autenticación y tenant

## Qué se verificó

- `TenantMiddleware` lee `X-Tenant-ID` y usa el modelo `Hospital` de la conexión
  CENTRAL.
- `JwtAuth` valida el token de acceso, que el hospital del token coincida con
  el hospital resuelto por middleware y que exista usuario, sesión y
  membresía activa.
- La pantalla de login obtiene el ID de tenant del usuario y llama al cliente
  Axios. `setTenantId` lo guarda en `localStorage` con la clave `tenant_id`;
  Axios envía ese valor como `X-Tenant-ID`.
- `AuthenticateUser` deriva `user.permissions` de roles activos de la
  membresía. La matriz RBAC activa concede `camas.ver` a `superadmin` y
  `recepcion`.
- El guard del frontend vuelve a consultar `/api/v1/auth/me`; el router no
  sustituye la autenticación ni la autorización del servidor.
- `SELECT 1` fue exitoso en `central` y `hospital`. La integración del catálogo
  usó PostgreSQL HOSPITAL configurado para pruebas y revirtió las filas
  ficticias por transacción.

No se leyó `.env`, no se consultaron ni publicaron IDs/emails de usuarios
reales, no se inspeccionó `localStorage`, no se modificó ninguna credencial y
no se creó ni editó registro de hospital/membresía.

## Respuestas precisas del middleware de tenant

| Condición de `X-Tenant-ID` | Respuesta del middleware |
|---|---|
| Cabecera ausente o vacía | `400 TENANT_REQUIRED` — “La cabecera X-Tenant-ID es obligatoria.” |
| Valor no UUID | `400 TENANT_INVALID` — “El hospital indicado no es válido.” |
| UUID no registrado como `hospitals.hospital_id` en CENTRAL | `404 HOSPITAL_NOT_FOUND` — “Hospital no encontrado.” |
| Hospital registrado con estado distinto de `active` | `403 HOSPITAL_INACTIVE` — “El hospital no está activo.” |

Por tanto, si el mensaje local observado coincide exactamente con “El hospital
indicado no es válido.”, el código indica que el encabezado recibido no supera
la validación de formato UUID. No se inspeccionó el valor concreto y no se
atribuye el error a una fila determinada.

## Requisitos para una prueba manual autorizada

Solicitar a una persona administradora, por canal seguro y fuera del
repositorio:

1. El `hospital_id` UUID de una fila existente y activa en la tabla central
   `hospitals`. No usar un nombre/código ni generar un UUID arbitrario.
2. Una cuenta de pruebas autorizada: usuario central activo, credencial
   entregada de forma privada y membresía activa y vigente
   (`valid_from <= ahora`, `valid_to` nulo o futuro) para ese mismo hospital.
3. Confirmación de que uno de los roles activos asignados a esa membresía tiene
   `camas.ver` en la matriz RBAC vigente.

En la matriz actual, los roles `superadmin` y `recepcion` incluyen
`camas.ver`; no se debe asignar un rol por conveniencia si no está autorizado
por el responsable del entorno. Los roles se resuelven por la membresía del
tenant, no por una selección de rol en el cliente.

### Pasos de validación, sin omitir controles

1. Abrir el login del HIS y escribir el `hospital_id` confirmado en el campo
   **ID de tenant (X-Tenant-ID)**. Si el formulario conserva un valor anterior,
   reemplazarlo; no compartir su contenido.
2. Iniciar sesión con las credenciales proporcionadas por la persona
   administradora.
3. Verificar que `/api/v1/auth/me` devuelve identidad del hospital y que
   `user.permissions` contiene `camas.ver`.
4. Abrir `/wards`; confirmar `GET /api/v1/wards` con respuesta autorizada.
5. Seleccionar una ward y confirmar `GET /api/v1/wards/{ward}/beds`.
6. Si la API responde `403`, no intentar cambiar roles localmente: pedir al
   administrador que verifique la membresía y concesión RBAC.

No poner el ID del tenant, correo, contraseña, JWT o refresh token en
capturas, commits, documentación o mensajes públicos.

## Lo que no se puede concluir de las pruebas automatizadas

- `IdentityContextTest` prueba autenticación, membresías y permisos en SQLite
  en memoria; no valida cuentas reales de CENTRAL.
- `CatalogLaravelIntegrationTest` valida persistencia y filtro de camas en
  PostgreSQL HOSPITAL dentro de transacción; no hace login ni llama a la ruta
  desde un navegador.
- El `SELECT 1` confirma conectividad, no que haya hospital activo, usuario de
  prueba, membresía o permiso en las bases.

## Si faltan datos de prueba

No insertar datos manualmente ni usar migraciones durante la revisión local.
La persona administradora debe verificar o preparar en su procedimiento
controlado el hospital activo, el usuario de prueba y la membresía/rol
autorizados. Si no se dispone de esos datos, la comprobación manual de login y
UI queda pendiente; las pruebas automatizadas aisladas no se presentan como
sustituto de esa validación.
