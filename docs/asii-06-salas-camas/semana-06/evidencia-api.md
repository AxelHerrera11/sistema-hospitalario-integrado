# Evidencia API — Semana 6

`GET /api/v1/wards` lista `wards` por código e ID mediante `CatalogService` y
`WardRepository`. Permiso `camas.ver` (existente), middleware `api`, `tenant`,
`jwt.auth`, `rbac:camas.ver`. No se modifican camas ni datos clínicos.

Cada item solo expone `ward_id`, `code`, `name`, `location` y `status`; `meta`
contiene `count` y `request_id`. Sin registros: `200`, `data: []`, conteo cero.
Persistencia no disponible: `503`, `WARD_CATALOG_UNAVAILABLE`, sin detalles
internos. Auth/permiso denegado: `401`/`403`.

## Validación ejecutada

- `php artisan test tests/Feature/SalasCamas/WardCatalogEndpointTest.php` —
  6 escenarios, 23 aserciones; el wrapper reporta 6 advertencias
  `file_get_contents(...)` del entorno.
- `php vendor/phpunit/phpunit/phpunit tests/Feature/SalasCamas/WardCatalogEndpointTest.php`
  — PASS, 6 tests, 23 assertions.
- `php vendor/phpunit/phpunit/phpunit tests/Feature/SalasCamas/CatalogLaravelIntegrationTest.php`
  — FAIL por entorno: PostgreSQL rechazó la conexión a `127.0.0.1:55433`
  (`Connection refused`), antes de ejecutar aserciones.
- `php micro-his/salas-camas/tests/run.php` — PASS, 3 escenarios.
- `php artisan route:list --path=api` — PASS; figura `GET|HEAD api/v1/wards`.
- `php -l` en los PHP modificados — sin errores; `git diff --check` — PASS.

No se ejecutó `migrate:fresh`, otra migración ni se modificó `.env`.

## Riesgo y pendientes

`TenantMiddleware` valida el hospital, pero la conexión `hospital` permanece
fija; `wards`/`beds` no contienen `tenant_id` y no hay selección dinámica de
base por tenant. Por ello, no se afirma aislamiento multi-tenant completo.
Quedan fuera el listado de beds, CRUD y UI; resolver conexión por hospital
antes de declarar aislamiento entre hospitales.
