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
| 6 | Permiso | `config/rbac.php` | `ocupacion.ver` ya está en el catálogo de `develop` (superadmin, admin_hospital, enfermeria y recepcion); este módulo no lo modifica |
| 7 | Ruta | `php artisan route:list --path=occupancy` | `GET api/v1/occupancy  Api\V1\OccupancyController@index` |
| 8 | Pruebas | `php vendor/bin/phpunit --testsuite Feature` | `OK` |
| 9 | Datos de prueba | SQL de un hospital, un superadmin y una sala con camas y una estancia | Entorno de evidencia en bases `*_staging` |
| 10 | Servidor | `php artisan serve --host=127.0.0.1 --port=8099` | `Server running on [http://127.0.0.1:8099]` |
| 11 | Reversión | `git revert` del PR y `php artisan config:clear` | El módulo no deja migraciones aplicadas |

## Evidencia ejecutada

```bash
# comando: php artisan route:list --path=occupancy
# resultado: GET|HEAD  api/v1/occupancy .... Api\V1\OccupancyController@index

# comando: php vendor/bin/phpunit tests/Feature/Ocupacion
# resultado: OK (16 tests, 66 assertions)
#   (9 del contrato HTTP del PR #574 + 7 de seguridad de la semana 17)

# comando: php vendor/bin/phpunit --testsuite Feature
# resultado: OK (85 tests, 1334 assertions)

# comando: php vendor/bin/phpunit --testsuite Database
# resultado: OK (73 tests, 862 assertions) sobre PostgreSQL 16

# comando: node resources/js/modules/ocupacion/*.test.mjs
#   (se ejecuta en la rama de la pantalla Vue, PR #575, que es donde vive ese archivo)
# resultado: OK: 14 aserciones de la pantalla de ocupación aprobadas

# comando: npm run build
# resultado: ✓ 95 modules transformed

# comando: php artisan tinker --execute="echo config('app.debug') ? 'debug on' : 'debug off';"
# resultado (con APP_DEBUG=false): debug off
```

## Bloqueo resuelto: modelos de Tenants en `develop`

El 20 de octubre de 2026, este era el bloqueo real del despliegue en un entorno con hospital:

```bash
# comando: php artisan serve --host=0.0.0.0 --port=8099
#   y luego: curl -i http://127.0.0.1:8099/api/v1/occupancy -H 'X-Tenant-ID: hospital-a'
# resultado: HTTP 500
#   {"message": "Class \"App\\Models\\Tenant\" not found",
#    "exception": "Error",
#    "file": "/app/app/Http/Middleware/TenantMiddleware.php", "line": 23}
```

La causa no era de este módulo: el middleware `tenant` de `develop` resolvía `App\Models\Tenant`, y
ese modelo no existía todavía. **Quedó resuelto** el 21 de octubre de 2026: el middleware ya usa
`App\Modules\Tenants\Infrastructure\Persistence\Hospital` y los modelos de Tenants están
integrados en `develop` (PR #657). La evidencia de despliegue real está en la sección siguiente.

El módulo no deja migraciones aplicadas: la reversión es un `git revert` del PR y un
`php artisan config:clear`. No hay cambios de esquema que revertir.

## Evidencia de despliegue real (ejecutada el 21 de octubre de 2026)

El bloqueo anterior (`Class "App\Models\Tenant" not found`) **quedó resuelto**: el middleware `tenant`
de `develop` ya resuelve `App\Modules\Tenants\Infrastructure\Persistence\Hospital`, y los modelos de
Tenants están integrados en `develop` (PR #657). Por eso la evidencia en *staging/on-premise* ya no
está pendiente y se ejecutó de punta a punta contra las bases reales.

**Entorno:** PostgreSQL 16 en los contenedores `bd-central` (55431) y `bd-hospital` (55433), bases
`his_central_staging` y `his_hospital_staging` migradas con `develop`, aplicación servida con
`php artisan serve --host=127.0.0.1 --port=8099` en la imagen PHP 8.5 con `pdo_pgsql`, con
`APP_DEBUG=false` y `JWT_SECRET` fuera del repositorio.

**Datos del entorno:** red, hospital y superadmin de evidencia en CENTRAL; en HOSPITAL, una sala
(`UCI`) con tres camas (`occupied`, `available`, `maintenance`), un paciente, su episodio de
cuidado y una estancia vigente.

```bash
# comando: php artisan migrate --database=central --path=database/migrations/central --realpath --force
# resultado: 000000, 000100, 000200, 000300, 000400, 000500, 000600, 000700 y 000800 DONE

# comando: php artisan migrate --database=hospital --path=database/migrations/hospital --realpath --force
# resultado: 000000, 000100, 000200, 000300, 000400, 000401 y 000700 DONE

# comando: php artisan serve --host=127.0.0.1 --port=8099
# resultado: Server running on [http://127.0.0.1:8099]

# comando: curl -s -i -X POST http://127.0.0.1:8099/api/v1/auth/login \
#   -H 'X-Tenant-ID: 11111111-1111-4111-8111-111111111111' \
#   -H 'Content-Type: application/json' \
#   -d '{"email":"evidencia@ejemplo.test","password":"..."}'
# resultado: HTTP/1.1 200 OK
#   {"access_token":"eyJ0eXAiOiJKV1Qi...","token_type":"bearer","expires_in":3600,
#    "user":{...,"permissions":[...,"ocupacion.ver",...]},
#    "hospital":{"hospital_id":"11111111-1111-4111-8111-111111111111","name":"Hospital de evidencia G3"},
#    "roles":["superadmin"],"roles_version":"186cf774c97b..."}

# comando: curl -s -i "http://127.0.0.1:8099/api/v1/occupancy?cutoff_at=2026-10-21T04:00:00-06:00" \
#   -H "Authorization: Bearer $TOKEN" \
#   -H 'X-Tenant-ID: 11111111-1111-4111-8111-111111111111' \
#   -H 'X-Request-ID: evidencia-17-001'
# resultado: HTTP/1.1 200 OK
#   Cache-Control: no-store, private
#   {"data":{"cutoff_at":"2026-10-21T04:00:00-06:00",
#     "global":{"occupied":1,"available":1,"maintenance":1,"inactive":0,"unverified_occupied":0,
#               "total_beds":3,"inconsistencies":0,"occupancy_rate":50,
#               "distribution_percentages":{"occupied":33.33,"available":33.33,"maintenance":33.33,
#                                           "inactive":0,"unverified_occupied":0}},
#     "wards":[{"ward_id":"66666666-...","ward_code":"UCI","occupied":1,"available":1,"maintenance":1,
#               "total_beds":3,"inconsistencies":0,"occupancy_rate":50}]},
#    "meta":{"request_id":"evidencia-17-001"}}
```

La tasa sale **50 %** porque el denominador son las camas operativas: tres camas menos una en
mantenimiento. La cama con estancia vigente cuenta como ocupada y la de mantenimiento queda fuera
del denominador, tal como define el contrato.

**Casos de seguridad en el mismo entorno real:**

```bash
# comando: curl -s -i "http://127.0.0.1:8099/api/v1/occupancy" -H 'X-Tenant-ID: 11111111-...'
#   (sin token)
# resultado: HTTP/1.1 401 Unauthorized
#   {"error":"TOKEN_MISSING","message":"Debe enviar un token Bearer."}

# comando: curl -s -i "http://127.0.0.1:8099/api/v1/occupancy" -H "Authorization: Bearer $TOKEN" \
#   -H 'X-Tenant-ID: 12121212-1212-4121-8121-121212121212'   (hospital distinto)
# resultado: HTTP/1.1 403 Forbidden
#   {"error":"TENANT_MISMATCH","message":"El hospital indicado no coincide con el token."}

# comando: curl -s -i "http://127.0.0.1:8099/api/v1/occupancy" -H "Authorization: Bearer $TOKEN_MEDICO" \
#   -H 'X-Tenant-ID: 11111111-1111-4111-8111-111111111111'   (rol medico, sin ocupacion.ver)
# resultado: HTTP/1.1 403 Forbidden
#   {"code":"RBAC_DENIED","trace_id":"trace-b103c8c8a742"}

# comando: curl -s -i "http://127.0.0.1:8099/api/v1/occupancy" -H "Authorization: Bearer $TOKEN"
#   (con HOSPITAL_DB_DATABASE apuntando a una base inexistente)
# resultado: HTTP/1.1 503 Service Unavailable
#   {"error":{"code":"OCCUPANCY_UNAVAILABLE","message":"La base de ocupacion no esta disponible."},
#    "meta":{"request_id":"req-2df23bcf24dca844"}}
```

El último caso confirma, en un servidor real y con `APP_DEBUG=false`, que **el 503 ocurre de
verdad**: la respuesta es el sobre del contrato, **sin traza, sin archivo y sin SQLSTATE**. Es la
corrección que pidió la revisión del PR #574.

**Log estructurado de la misma ejecución** (`storage/logs/laravel.log`), sin datos de pacientes:

```
occupancy.snapshot {"request_id":"evidencia-17-001","hospital_id":"11111111-1111-4111-8111-111111111111","cutoff_at":"2026-10-21T04:00:00-06:00","inconsistencies":0,"duration_ms":14.245}
occupancy.unavailable {"request_id":"req-2df23bcf24dca844","hospital_id":"11111111-1111-4111-8111-111111111111","duration_ms":5.842}
```

Con esto quedan resueltos los tres pendientes de despliegue de la semana 17 (modelos de Tenants,
evidencia en staging y respuesta sin trazas con `APP_DEBUG=false`).

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
| 1 | Modelos `Tenant` y `User` en `develop` | ASII-25 (PR #657) | `RESUELTO` |
| 2 | Evidencia en staging/on-premise | Javier | `RESUELTO` (21-oct-2026, ver arriba) |
| 3 | Confirmación de la respuesta sin trazas con `APP_DEBUG=false` en el entorno final | Javier | `RESUELTO` (el 503 real no expone traza ni SQLSTATE) |
| 4 | Rate limiting del grupo para `/api/v1` | ASII-30 (API Gateway) | `PENDIENTE` |
| 5 | Lista blanca de hospitales permitidos | ASII-25 (Tenants) | `PENDIENTE` |
| 6 | Formato común de errores entre endpoint y middleware RBAC | Lis (contratos) | `PENDIENTE` |
