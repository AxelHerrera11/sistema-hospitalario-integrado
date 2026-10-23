# Evidencia de revisión — Semana 13

## Base y estado

`ASII06_SEMANA13_BASE`:
`d22eb9e84e7a7148500d5f93445af6c970de8d42`.

Rama de entrega original: `feature/asii-06-semana-13-salas-camas-madelinceron`.
Se actualizó mediante merge normal de `origin/develop`, que incluye #1064,
#1072 y la contención de #1154. La plantilla oficial identifica Semana 13 como
revisión técnica formal; issue #11 no amplía ese alcance.

## Pruebas ejecutadas en esta rama

- `npm run build` — **PASS**, Vite transformó 134 módulos.
- `node --test tests/Frontend/WardCatalogNavigationTest.mjs` — **PASS**,
  2 tests, 0 fallos.
- Suite frontend seleccionada por QA CI (`node --test --test-reporter=spec`
  sobre los `*.test.mjs` y `*Test.mjs` de `resources/js`, `tests/Frontend` y
  `micro-his`, excluyendo `node_modules`) — **PASS**, 27 tests, 27 aprobados.
  `SessionExpiryTest.mjs` ahora carga en el contexto de VM sus dependencias
  `formRules.js` y `logoutPolicy.js` antes de `auth.js`; el fallo previo era
  del harness, que eliminaba imports pero no evaluaba esas dependencias.
- `php vendor/phpunit/phpunit/phpunit --configuration phpunit.xml tests/Feature/SalasCamas/WardCatalogEndpointTest.php tests/Feature/SalasCamas/CatalogHospitalIsolationTest.php tests/Feature/SalasCamas/CatalogLaravelIntegrationTest.php tests/Feature/Tenants/IdentityContextTest.php`
  — **PASS**, 28 tests, 189 assertions: endpoint (13/54), aislamiento
  (3/37), integración PostgreSQL (1/23) e identidad tenant (11/75).
- `php micro-his/salas-camas/tests/run.php` — **PASS**, 4 escenarios.
- `php artisan route:list --path=api` — **PASS**, 25 rutas; incluye ambas rutas
  GET del catálogo.
- `git diff --check` — **PASS**.

### GitHub Actions

Para el SHA validado `beb184edcedfb3c72b6b230028100d9637bd9774`, los runs
`ASII PR Guard` (40026740719) y `QA CI y E2E` (40026740736) terminaron como
failure antes de iniciar los jobs. Las anotaciones de los checks fallidos indican:
“The job was not started because recent account payments have failed or your
spending limit needs to be increased.” Esto confirma el bloqueo de facturación
en esos runs; no se infiere únicamente de la respuesta 404 al descargar logs.
Los jobs no ejecutaron pruebas en Actions; las validaciones locales posteriores
se consignan separadamente y no se presentan como checks de Actions exitosos.

La prueba de aislamiento reprodujo primero la condición anterior a #1154:
Hospital B, con sesión y `camas.ver` válidos, recibió HTTP 200 y el catálogo
de la conexión asignada a A. Con el guard, cada tenant solo puede consultar
el esquema asignado; los cruces reciben 403 y una asignación sin configurar
recibe 503. Los esquemas se crean dentro de una transacción en la base de test
y se revierten; no se ejecutan migraciones destructivas.

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
- Selección dinámica de base de datos por tenant; la contención temporal limita
  la instancia a `HOSPITAL_DB_TENANT_ID`, pero no resuelve el mapeo central.

No se ejecutaron migraciones, `migrate:fresh`, cambios a `.env` ni operaciones
de escritura sobre bases reales fuera del test PostgreSQL protegido por
transacción y rollback.
