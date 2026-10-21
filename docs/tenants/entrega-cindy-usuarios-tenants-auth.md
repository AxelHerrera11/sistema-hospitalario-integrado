# Entrega local de Cindy para integración con RBAC

Fecha: 20 de octubre de 2026. Estado: preparada para revisión, sin push ni PR.
Esta entrega no declara completo el módulo de usuarios, tenants y autenticación.

## Alcance implementado

- Identidad UUID CENTRAL con modelos de usuario, credencial, hospital y sesión.
- Roles canónicos literales desde membresías activas y vigentes por hospital.
- Login, usuario actual y logout con JWT de 15 minutos y contexto hospitalario.
- Refresh opaco, hash SHA-256, cookie HttpOnly, TTL de siete días, rotación y
  detección de reutilización con revocación de familia.
- Listado paginado de usuarios, asignación/revocación de membresías, desactivación
  local, revocación de sesiones ajenas y alta de hospital CENTRAL inactivo.
- Auditoría transaccional y migraciones de cadena de hashes/append-only.
- Comando interactivo para la primera cuenta superadmin, sin contraseña por defecto.
- Vue: login, recuperación/renovación de sesión, logout, hospital activo y usuarios.

## Separación y base de las ramas

Base común: develop `0544d5f591643ec5993123a9bb4b8cbdafc7b97e`.
Todas las ramas se crean directamente desde esa base en worktrees separados.
Cada PR futuro irá a develop y tendrá un máximo de 400 líneas agregadas más
eliminadas mientras esté abierto; no se cuentan contra otra rama de la pila.
Los conjuntos de archivos son disjuntos: se complementan sin repetir archivos.
Los contratos y dependencias se revisan en conjunto; cada rama no es una aplicación
independiente ni una prueba de que auth ya esté integrada.

Prefijo de todas las ramas: `feature/asii-25-semana-` (sin nombre personal al final).

| Semana | Sufijo después del prefijo | Contenido |
|---|---|---|
| 16 | `16-tenants-identidad` | Identidad, modelos, roles y prueba unitaria |
| 16 | `16-tenants-persistencia` | Membresías, refresh y migraciones iniciales |
| 16 | `16-tenants-sesiones-jwt` | Repositorio de sesiones, emisor JWT y configuración |
| 16 | `16-tenants-login-http` | Caso de login, controller y tenant |
| 16 | `16-tenants-refresh-contexto` | Refresh y contexto autenticado |
| 16 | `16-tenants-rutas-provision` | Provider, rutas y primera cuenta administrativa |
| 16 | `16-tenants-auditoria` | Contrato, repositorio y migraciones de auditoría |
| 16 | `16-tenants-membresias` | Asignación, revocación y errores administrativos |
| 16 | `16-tenants-administracion` | Listado/alta y controllers administrativos |
| 17 | `17-tenants-interfaz` | Cliente Vue, sesión y listado de usuarios |
| 17 | `17-tenants-soporte-pruebas` | Esquema SQLite de pruebas y arranque de TestCase |
| 17 | `17-tenants-pruebas-identidad` | Identidad y ciclo de sesión |
| 17 | `17-tenants-pruebas-roles` | Autorización y límites de asignación |
| 17 | `17-tenants-pruebas-admin` | Administración hospitalaria y aislamiento |
| 17 | `17-tenants-entrega` | Documentación del módulo y contrato consumido |

## Dependencia del RBAC existente

No se incluyen cambios en app/Rbac, config/rbac.php, el provider RBAC, sus pruebas,
catálogo Vue ni motor micro-HIS. Tampoco se copia el commit administrativo e199c22.
La implementación consume los permisos del backend RBAC del PR #495 y el adaptador
AuditDenials de ese módulo. TenantsServiceProvider registra el binding de su contrato
público de auditoría. Los archivos compartidos de registro solo añaden Tenants y
/admin/users; no incorporan el registro del provider ni la ruta /rbac.

Al integrar #495 y la interfaz de #490 habrá que reconciliar bootstrap/providers.php,
JwtAuth.php y resources/js/router/index.js: conservar los providers y rutas de ambos
módulos y usar el contexto autenticado de Cindy. Esa integración todavía no se hizo
sobre las ramas publicadas de RBAC y no se presenta como resuelta en develop.

## Validación real

En un worktree de validación que reúne el contenido de Cindy y lee el RBAC existente:

- PHPUnit Feature/Unit de Cindy: 34 pruebas, 218 aserciones, OK.
- npm run build: OK, 95 módulos. Advertencia de deprecación de Node, sin error.
- Cada rama: git diff --check y conteo real contra develop inferior o igual a 400.
- La lectura externa del provider/configuración de RBAC y las dependencias compartidas
  son soporte local de validación; no se incorporan a ninguna rama de Cindy.

Las pruebas usan SQLite con un esquema creado por TenantAuthTestCase. No ejecutan
las migraciones PostgreSQL ni acreditan sus triggers, locks o concurrencia real.
La suite conjunta debe reejecutarse después de fusionar las dependencias y CI sobre
los SHA efectivamente publicados. No se ha hecho push, PR, merge ni despliegue.

## Instalación y orden funcional

1. Revisar el contrato y el esquema CENTRAL existente de Javier; no renombrar tablas.
2. Integrar identidad, persistencia, sesiones/JWT, auditoría y membresías.
3. Integrar casos de uso/controllers y finalmente provider/rutas/provisión.
4. Integrar soporte de pruebas, pruebas funcionales y cliente Vue.
5. Con RBAC disponible, verificar los bindings, roles, rutas y contexto compartidos.
6. En bases PostgreSQL desechables, ejecutar migraciones y rollback de Cindy,
   pruebas de aislamiento, concurrencia del refresh e integridad de auditoría.
7. Ejecutar build, pruebas HTTP, E2E de navegador, CI y smoke de staging.

Configurar desde .env.example: conexiones CENTRAL/HOSPITAL, JWT_SECRET, emisor,
audiencia, TTL y cookie segura de refresh. No versionar .env ni claves reales.
Las credenciales y JWT actuales conservan el proveedor configurado; no se acredita
RS256/JWKS ni rotación de claves. Hospital CENTRAL nuevo permanece inactivo hasta
su aprovisionamiento clínico externo.

## Pendientes y límites

- Alta/activación general de usuarios y recuperación de contraseña.
- MFA TOTP/WebAuthn y recuperación, lockout progresivo e historial completo.
- SSO/JIT y RS256/JWKS si se conservan en los requisitos finales.
- Edición/aprovisionamiento clínico de hospitales y pantallas administrativas faltantes.
- Retención de auditoría y validación PostgreSQL de cadena/trigger/concurrencia.
- PostgreSQL, correo/Redis/SSO reales, E2E, CI y staging.

Docker no tiene daemon disponible durante esta preparación. No se ejecutaron
migraciones sobre bases existentes ni se alteraron datos clínicos.
No declarar terminado todo el módulo por la existencia de estas entregas.