# Guía de despliegue final — Sistema Hospitalario Integrado

> **Alcance y estado.** Procedimiento manual y verificable para promover `develop` a un ambiente (staging o la entrega final). Se basa en lo que existe en el repositorio (`composer.json`, `package.json`, `database/migrations/{central,framework,hospital}`, `.env.example`). No hay infraestructura de producción en el repositorio: los pasos de servidor son **propuestos** y deben adaptarse al ambiente que el grupo use. Nunca se ejecutan pruebas E2E ni semillas contra la base del ambiente desplegado.

## 1. Comprobaciones previas (no avanzar si alguna falla)

| # | Comprobación | Comando / fuente | Criterio |
|---|---|---|---|
| P1 | Quality gate del commit a desplegar | Artefacto `qa-gate-decision` del workflow `QA CI y E2E` | `verdict: approved` para **ese** `commit_sha` |
| P2 | Rama y commit exactos | `git fetch origin && git rev-parse origin/develop` | Igual al `commit_sha` de P1 |
| P3 | Versiones de plataforma | `php -v`, `node -v`, `psql --version` | PHP ≥ 8.2 con `pdo_pgsql`; Node 22; PostgreSQL 16 |
| P4 | Variables de entorno completas | Comparar `.env` con `.env.example` | `APP_KEY`, `JWT_SECRET`, `CENTRAL_DB_*`, `HOSPITAL_DB_*` definidos; `APP_ENV=production`, `APP_DEBUG=false`, `TENANTS_REFRESH_COOKIE_SECURE=true` |
| P5 | Respaldo de bases | `pg_dump -Fc his_central > central-<fecha>.dump` (y hospital) | Archivo creado y `pg_restore --list` lo lee |
| P6 | Migraciones pendientes revisadas por Javier | `php artisan migrate:status --database=central` (y hospital) | Lista aprobada; ninguna destructiva sin respaldo |
| P7 | Ventana y responsables | Grupo | Quién ejecuta, quién valida, quién decide rollback |

## 2. Despliegue (pasos reproducibles)

```bash
git fetch origin
git checkout --detach <commit_sha aprobado en P1>
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci --no-audit --no-fund
npm run build
php artisan down --render="errors::503"          # modo mantenimiento
composer migrate:central
composer migrate:framework
composer migrate:hospital
php artisan config:cache
php artisan route:cache
php artisan up
```

- Los scripts `migrate:*` son los del `composer.json`; en `APP_ENV=production` Laravel pide confirmación: agregar `--force` solo con la aprobación de P6.
- Un solo commit se despliega por vez; no se compila en el servidor nada que no venga del lockfile.

## 3. Validaciones posteriores

| # | Validación | Comando | Esperado |
|---|---|---|---|
| V1 | Salud | `curl -s -o /dev/null -w '%{http_code}' https://<host>/up` | `200` |
| V2 | Tenant obligatorio | `curl -s -X POST https://<host>/api/v1/auth/login` | `400` con `TENANT_REQUIRED` |
| V3 | Rutas protegidas sin token | `curl -s -H 'X-Tenant-ID: <uuid real>' https://<host>/api/v1/auth/me` | `401 TOKEN_MISSING` |
| V4 | Contrato de rutas en el ambiente | `php artisan route:list --json --path=api > routes.json && php micro-his/qa-calidad-versiones/bin/route-guard.php routes.json config/rbac.php route-guard.xml` | exit 0 |
| V5 | Login real (cuenta operativa, no de prueba) | Iniciar sesión en la UI | `200` y menú por rol |
| V6 | Logs | `storage/logs/laravel.log` | Sin errores nuevos en 15 minutos |
| V7 | Registro del despliegue | Anotar commit, hora, responsable y resultado de V1–V6 | Acta guardada junto al artefacto del gate |

V2 y V3 no crean datos. No se ejecutan `seed-e2e.php` ni pruebas E2E contra este ambiente.

## 4. Recuperación (rollback)

| Situación | Acción |
|---|---|
| Falla V1–V4 sin migraciones nuevas | `php artisan down`; `git checkout --detach <commit anterior>`; repetir `composer install`, `npm ci`, `npm run build`, `config:cache`, `route:cache`; `php artisan up`; repetir V1–V6 |
| Falla con migraciones aplicadas | Con Javier: `php artisan migrate:rollback --database=<conexión> --path=database/migrations/<carpeta> --realpath --step=<n>` si la migración tiene `down()` seguro; si no, restaurar el respaldo P5 con `pg_restore --clean -d <base> <archivo>.dump` |
| Error de configuración | Corregir `.env`, `php artisan config:clear && php artisan config:cache` |
| Cualquier rollback | Registrar causa y avisar al dueño del módulo el mismo día (regla del plan de integración) |

## 5. Puntos pendientes de validar

- Host, servidor web y gestor de procesos del ambiente final (no están en el repositorio).
- Si la entrega del viernes 23 usa un ambiente desplegado o solo `develop` + evidencias del CI.
- Política de respaldo y retención de las bases (Javier).
