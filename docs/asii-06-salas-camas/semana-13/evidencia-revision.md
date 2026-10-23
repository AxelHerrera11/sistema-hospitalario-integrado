# Evidencia de revisión — Semana 13

## Base y estado

`ASII06_SEMANA13_BASE`:
`d22eb9e84e7a7148500d5f93445af6c970de8d42`.

Rama: `feature/asii-06-semana-13-salas-camas-madelinceron`. Worktree limpio
al inicio. La plantilla oficial identifica Semana 13 como revisión técnica
formal; issue #11 no amplía ese alcance.

## Pruebas ejecutadas en esta rama

- `npm run build` — **PASS**, Vite transformó 129 módulos.
- `node --test tests/Frontend/WardCatalogNavigationTest.mjs` — **PASS**,
  2 tests, 0 fallos.
- `php vendor/phpunit/phpunit/phpunit tests/Feature/SalasCamas/WardCatalogEndpointTest.php`
  — **PASS**, 11 tests, 48 assertions.
- `php vendor/phpunit/phpunit/phpunit tests/Feature/SalasCamas/CatalogLaravelIntegrationTest.php`
  — **PASS**, 1 test, 23 assertions; transacción y rollback.
- `php vendor/phpunit/phpunit/phpunit tests/Feature/Tenants/IdentityContextTest.php`
  — **PASS**, 11 tests, 75 assertions; central de pruebas SQLite en memoria.
- `php micro-his/salas-camas/tests/run.php` — **PASS**, 4 escenarios.
- `php artisan route:list --path=api` — **PASS**, 25 rutas; incluye ambas rutas
  GET del catálogo.
- `git diff --check` — **PASS**.

La integración PostgreSQL usa la configuración de prueba del repositorio y
revierte datos ficticios creados dentro de la transacción. El test de identidad
no autentica contra cuentas locales reales. `npm run build` y la prueba de
permisos Node no son validación visual del componente.

## Revisión de autenticación local

No se proporcionaron credenciales ni un hospital de pruebas autorizado y no se
hizo login manual. No se leyó `.env`, `localStorage` o filas de usuarios ni se
modificaron registros. El contrato inspeccionado exige `X-Tenant-ID` con UUID
de hospital registrado y activo; JWT debe corresponder al mismo hospital; la
cuenta necesita membresía activa/vigente y `camas.ver`.

Un ping de solo lectura `SELECT 1` en CENTRAL y HOSPITAL pasó en la validación
anterior de Semana 12; no prueba que el registro contenga un hospital o cuenta
autorizados. No se repitió aquí porque Semana 13 no cambió conexión ni
configuración.

## Alcance no verificado

- Login y recorrido autenticado completo en navegador.
- Existencia/estado de un hospital concreto o asignación RBAC de una cuenta
  concreta.
- Inspección de lector de pantalla, contraste, zoom/reflow o conformidad WCAG.
- Selección de base de datos del catálogo por tenant; sigue fija.

No se ejecutaron migraciones, `migrate:fresh`, cambios a `.env` ni operaciones
de escritura sobre bases reales fuera del test PostgreSQL protegido por
transacción y rollback.
