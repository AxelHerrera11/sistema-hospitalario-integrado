# Bitácora de despliegue — ASII-09 Dashboard de ocupación (semana 17)

Registro de la entrega de la **semana 17: seguridad y despliegue final**. Contiene el índice de la
semana, los pasos de despliegue, la evidencia ejecutada y los bloqueos reales que quedan.

## Contenido de la semana 17

| Archivo | Qué contiene |
|---|---|
| `matriz-threats.md` | 16 amenazas con control, estado y evidencia |
| `bitacora-despliegue.md` | Este documento: pasos, evidencia y bloqueos |
| `DECLARACION_IA.md` | Declaración de uso de IA de la entrega |

Las pruebas de permisos y datos sensibles están en `tests/Feature/Ocupacion/OccupancySecurityTest.php`.

## Pasos de despliegue

| # | Paso | Comando | Resultado |
|---:|---|---|---|
| 1 | Recepción del código | `git fetch origin && git switch develop` | Rama `develop` vigente |
| 2 | Dependencias de PHP | `composer install` | `vendor/` con el `composer.lock` de `develop` |
| 3 | Dependencias de JavaScript | `npm ci && npm run build` | `✓ 95 modules transformed` |
| 4 | Variables de entorno | `CENTRAL_DB_*`, `HOSPITAL_DB_*`, `APP_KEY`, `JWT_SECRET` | Fuera del repositorio; `APP_DEBUG=false` |
| 5 | Esquema | `php artisan migrate --database=central` y `--database=hospital` | Sin migraciones nuevas de este módulo |
| 6 | Permiso | `config/rbac.php` | `ocupacion.ver` en `superadmin` y `admin_hospital` |
| 7 | Ruta | `php artisan route:list --path=occupancy` | `GET api/v1/occupancy  Api\V1\OccupancyController@index` |
| 8 | Pruebas | `php vendor/bin/phpunit --testsuite Feature` | `OK` |
| 9 | Reversión | `git revert` del PR y `php artisan config:clear` | El módulo no deja migraciones aplicadas |

## Evidencia ejecutada

```bash
# comando: php artisan route:list --path=occupancy
# resultado: GET|HEAD  api/v1/occupancy .... Api\V1\OccupancyController@index

# comando: php vendor/bin/phpunit tests/Feature/Ocupacion
# resultado: OK (15 tests, 64 assertions)
#   (8 del contrato HTTP del PR #574 + 7 de seguridad de la semana 17)

# comando: php vendor/bin/phpunit --testsuite Database
# resultado: OK (59 tests, 738 assertions) sobre PostgreSQL 16

# comando: node resources/js/modules/ocupacion/ocupacion.test.mjs
# resultado: OK: 14 aserciones de la pantalla de ocupación aprobadas

# comando: npm run build
# resultado: ✓ 95 modules transformed

# comando: php artisan tinker --execute="echo config('app.debug') ? 'debug on' : 'debug off';"
# resultado (con APP_DEBUG=false): debug off
```

## Bloqueo real del despliegue en un entorno con hospital

El intento de levantar el endpoint en un servidor real da:

```bash
# comando: php artisan serve --host=0.0.0.0 --port=8099
#   y luego: curl -i http://127.0.0.1:8099/api/v1/occupancy -H 'X-Tenant-ID: hospital-a'
# resultado: HTTP 500
#   {"message": "Class \"App\\Models\\Tenant\" not found",
#    "exception": "Error",
#    "file": "/app/app/Http/Middleware/TenantMiddleware.php", "line": 23}
```

La causa no es de este módulo: el middleware `tenant` de `develop` resuelve `App\Models\Tenant`, y
ese modelo **no existe todavía en `develop`**. Es el PR #501 «Modelos CENTRAL y roles canónicos»
de ASII-25, que sigue abierto. Hasta que ASII-25 integre sus modelos, el endpoint no puede
ejecutarse contra un hospital real y la evidencia de la semana 16 en *staging/on-premise* queda
pendiente, tal como está declarado en `semana-16/README.md`.

Dos observaciones de esta misma prueba, que quedan como verificación pendiente en el entorno final:

1. Con `APP_DEBUG=true` (valor del `.env` de desarrollo) la respuesta de Laravel incluye traza y
   archivo. `config('app.debug')` devuelve `off` cuando se fija la variable de entorno, pero falta
   confirmar en el entorno final que la respuesta no incluye trazas.
2. El módulo no deja migraciones aplicadas: la reversión es un `git revert` del PR y un
   `php artisan config:clear`. No hay cambios de esquema que revertir.

## Reglas de despliegue

1. `APP_DEBUG=false` y secretos fuera del repositorio.
2. `Cache-Control: no-store` en las respuestas del módulo: la fotografía no se guarda en caché
   compartida.
3. El endpoint es de solo lectura: no requiere migración, ni ventana que revertir, ni plan de datos.
4. Si ASII-25 cambia el formato de `X-Tenant-ID`, el adaptador de la semana 5 se ajusta antes de
   conectar el adaptador real.

## Pendientes de la semana 17

| # | Pendiente | Responsable | Estado |
|---:|---|---|---|
| 1 | Modelos `Tenant` y `User` en `develop` | ASII-25 (PR #501, #502) | `PENDIENTE` |
| 2 | Evidencia en staging/on-premise | Javier, tras el punto 1 | `PENDIENTE` |
| 3 | Confirmación de la respuesta sin trazas con `APP_DEBUG=false` en el entorno final | Javier | `PENDIENTE` |
| 4 | Rate limiting del grupo para `/api/v1` | ASII-30 (API Gateway) | `PENDIENTE` |
| 5 | Lista blanca de hospitales permitidos | ASII-25 (Tenants) | `PENDIENTE` |
| 6 | Formato común de errores entre endpoint y middleware RBAC | Lis (contratos) | `PENDIENTE` |
