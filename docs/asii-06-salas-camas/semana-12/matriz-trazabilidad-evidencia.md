# Matriz de trazabilidad y evidencia — Semana 12

**Estados:** Implementado = existe en código de la rama; Probado = validación
ejecutada y con salida observada; Documentado = descrito pero no verificado
operativamente; Pendiente = no hay evidencia ejecutada de esa capacidad.

| Requisito/afirmación | Evidencia | Estado |
|---|---|---|
| Semana 12 tiene alcance oficial de segunda evaluación parcial. | La plantilla enumera Semana 11 y luego 13; issue #11 no especifica Semana 12. La entrega de Pacientes es referencia de formato únicamente. | No especificado oficialmente |
| Consulta de wards. | `GET /api/v1/wards`; `WardCatalogController::index`; Feature test valida campos y respuesta. | Implementado / probado |
| Consulta de camas por ward. | `GET /api/v1/wards/{ward}/beds`; consulta de beds relacionada por `ward_id`; Feature test verifica relación y exclusión entre wards. | Implementado / probado |
| Pantalla Vue del catálogo. | Ruta `/wards`; componente `resources/js/modules/salas-camas/pages/WardCatalogPage.vue`; cliente Axios compartido. | Implementado |
| Menú y ruta restringidos a `camas.ver`. | `ROUTE_PERMISSIONS.wards`, `NAVIGATION`, auth guard; prueba Node comprueba permiso y visibilidad. | Implementado / probado |
| Autorización HTTP. | Provider registra `api`, `tenant`, `jwt.auth` y `rbac:camas.ver`; Feature test valida middleware, 401/403 y respuestas. | Implementado / probado |
| Permisos efectivos del cliente. | `AuthenticateUser::userPayload` deriva `user.permissions` de roles activos; `IdentityContextTest` lo valida con fixture aislada. | Implementado / probado en suite aislada |
| Adaptación y estados de UI. | Vista tiene carga, vacío, error, reintento y foco/roles semánticos; build Vite pasa. | Implementado / compilado |
| Experiencia visual en navegador. | No se ejecutó navegador autenticado ni inspección visual de la ruta. | Pendiente |
| PostgreSQL HOSPITAL para catálogo. | `CatalogLaravelIntegrationTest`: conexión de test, repositorios PDO, creación/consulta/filtro; transacción y rollback. | Probado: 1 test, 23 assertions |
| Conexión PostgreSQL central. | Script local de solo lectura ejecutó `SELECT 1`; no consultó ni divulgó registros. | Probado: ping de conexión solamente |
| Login real con tenant válido y usuario de pruebas. | No se proporcionó ni verificó una identidad autorizada; `IdentityContextTest` utiliza SQLite en memoria. | Pendiente |
| Error “El hospital indicado no es válido.” | `TenantMiddleware` devuelve `TENANT_INVALID` si `X-Tenant-ID` no es UUID. No se inspeccionó el valor guardado en navegador. | Causa del mensaje explicada; valor local no diagnosticado |
| Selección de hospital → base hospitalaria dinámica. | Provider obtiene PDO fijo `hospital`; no selecciona conexión a partir de tenant. | Pendiente; no afirmar aislamiento completo |
| Cambios de código en Semana 12. | Solo se crearon materiales de defensa/diagnóstico; no se alteraron Vue, API, middleware, migraciones o `.env`. | Documentado |

## Comandos y resultados

- `npm run build` — **PASS**, Vite transformó 129 módulos.
- `node --test tests/Frontend/WardCatalogNavigationTest.mjs` — **PASS**,
  2 tests, 0 fallos.
- `php vendor/phpunit/phpunit/phpunit tests/Feature/SalasCamas/WardCatalogEndpointTest.php`
  — **PASS**, 11 tests, 48 assertions.
- `php vendor/phpunit/phpunit/phpunit tests/Feature/SalasCamas/CatalogLaravelIntegrationTest.php`
  — **PASS**, 1 test, 23 assertions; utiliza transacción y rollback.
- `php vendor/phpunit/phpunit/phpunit tests/Feature/Tenants/IdentityContextTest.php`
  — **PASS**, 11 tests, 75 assertions; base SQLite en memoria configurada por test.
- `php micro-his/salas-camas/tests/run.php` — **PASS**, 4 escenarios.
- `php artisan route:list --path=api` — **PASS**, 25 rutas; ambas rutas del
  catálogo presentes.
- `git diff --check` — **PASS**.
- Ping de solo lectura `SELECT 1` — **PASS** en `central` y `hospital`.

Estas pruebas no son un login extremo a extremo ni una comprobación visual en
un navegador autenticado.
