# Evidencia de integración — Semana 5

Base `ASII06_SEMANA05_BASE`: `517e48b72da41e47380ab7365d4c9727852ce519`.

## Trabajo previo revisado

- Semanas 1–3 están en la base: requisitos/casos de uso, micro-HIS por capas,
  entidades, contratos, adaptadores PDO, controlador y prueba standalone.
- No se encontró `docs/asii-06-salas-camas/semana-04/` en esta base; no se
  reconstruyó ni duplicó esa entrega.
- No había provider Laravel, bindings del catálogo ni rutas HTTP ASII-06.
- La migración hospitalaria existente define `wards` y `beds`; no define
  `rooms` ni una columna `tenant_id`.
- La conexión Laravel `hospital` está configurada en `config/database.php`.
  `TenantMiddleware` valida el hospital activo en CENTRAL, pero no cambia la
  conexión hospitalaria que usa este provider.
- `config/rbac.php` ya incluye `camas.ver` y `camas.asignar`; no se alteraron.
- Ocupación, admisión y traslados/altas consultan las tablas existentes o
  gestionan `bed_stays`; no se modificaron ni se trasladó lógica a ASII-06.

## Archivos creados/modificados

- `app/Providers/SalasCamasServiceProvider.php` — carga el bootstrap y registra
  bindings scoped para las interfaces y el servicio de aplicación.
- `bootstrap/providers.php` — registra el provider.
- `tests/Feature/SalasCamas/CatalogLaravelIntegrationTest.php` — resuelve los
  bindings y crea ward/cama vía `CatalogService` en PostgreSQL.
- `docs/asii-06-salas-camas/semana-05/README.md`.
- `docs/asii-06-salas-camas/semana-05/evidencia-integracion.md`.

El test exige `APP_ENV=testing`, base `his_hospital_test` y puerto `55433`,
inserta valores ficticios únicos, verifica ID, estado y relación `ward_id`, y
revierte la transacción. Confirma además que las tablas no tienen
`tenant_id`; esto no demuestra aislamiento por tenant.

## Validaciones y resultados

- `php artisan test tests/Feature/SalasCamas/CatalogLaravelIntegrationTest.php`
  — 1 test, 12 assertions, sin fallos; el wrapper Laravel mostró un warning de
  `file_get_contents(...)` truncado.
- `php vendor/phpunit/phpunit/phpunit tests/Feature/SalasCamas/CatalogLaravelIntegrationTest.php`
  — PASS, 1 test y 12 assertions, sin warnings.
- `php micro-his/salas-camas/tests/run.php` — PASS, 3 escenarios.
- `php -l` sobre el provider, `bootstrap/providers.php`, test nuevo, todos los
  PHP del micro-HIS `salas-camas` y la migración hospitalaria — sin errores.
- `php artisan route:list --path=api` — PASS; no hay rutas HTTP para wards o
  beds.
- `git diff --check` — PASS.
- No se ejecutaron migraciones ni `migrate:fresh`; no se alteró `.env`.

## Pendientes y riesgos

1. El esquema y provider actuales usan una conexión `hospital` fija y no tienen
   `tenant_id`: falta definir/usar la selección real de base por hospital
   antes de exponer operaciones del catálogo a una petición multi-tenant.
2. No hay API ni UI de wards/beds; intencionalmente fuera de esta integración
   mínima.
3. Permisos `camas.ver` y `camas.asignar` existentes no autorizan por sí solos
   un endpoint de administración del catálogo.
4. La integración prueba PostgreSQL mediante el repositorio PDO existente; el
   micro-HIS standalone continúa usando un fake para sus pruebas de flujo.
