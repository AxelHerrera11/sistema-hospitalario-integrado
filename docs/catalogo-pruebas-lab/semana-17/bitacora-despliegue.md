# Bitácora de despliegue: catálogo de pruebas de laboratorio (Semana 17)

## Despliegue local de verificación (28/10/2026)

Esto no es staging. El entorno es macOS con PHP 8.4.21, PostgreSQL 18.4 y Node 24.14.1. Se partió de bases vacías `his_central_e2e` y `his_hospital_e2e`, se aplicaron las migraciones de la rama y los datos son ficticios.

| # | Paso | Comando | Resultado |
|---:|---|---|---|
| 1 | Código | `git switch feature/asii-17-semana-17-catalogo-lab-seguridad-axelherrera11` (incluye la API de #1320) | Sin migraciones nuevas; `lab_tests` ya está en `develop` (#1244) |
| 2 | Dependencias | `composer install` y `npm ci` | Desde los lock de `develop` |
| 3 | Variables | `APP_KEY`, `JWT_SECRET`, `CENTRAL_DB_*`, `HOSPITAL_DB_*` y `HOSPITAL_DB_TENANT_ID` fuera del repositorio; `APP_DEBUG=false` | Secretos generados para la sesión; no se imprimen |
| 4 | Esquema | `composer migrate:central`, `migrate:framework` y `migrate:hospital` | Migraciones aplicadas sobre bases vacías |
| 5 | Datos | `E2E_ALLOW_SEED=1 php tests/E2E/fixtures/seed-e2e.php`; superadmin y recepción ficticios, con sus `local_user_refs` | 2 hospitales y 6 usuarios |
| 6 | Rutas | `php artisan route:list --path=api/v1/lab-tests` | `GET` y `POST lab-tests` |
| 7 | Interfaz | `VITE_API_URL=/api/v1 npm run build` | `✓ 142 modules transformed` |
| 8 | Servidor | `cd public && php -S 127.0.0.1:8095 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php` | Un segundo servidor en 8096 con `HOSPITAL_DB_PORT=5999` simula la base caída |
| 9 | Recorrido | `bash evidencia/recorrido-seguridad.sh …` | **12/12** ([salida](./evidencia/salida-recorrido-seguridad.txt), [psql](./evidencia/salida-psql.txt)) |

## Pruebas ejecutadas

```text
$ php vendor/bin/phpunit tests/Feature/CatalogoLab
OK (9 tests, 107 assertions)             # 5 de #1320 + 4 de seguridad (LabTestSecurityTest)
$ php micro-his/catalogo-pruebas-lab/tests/{run,whitebox,blackbox,api}.php
30, 20, 13 y 15 comprobaciones OK         # whitebox suma B10 y B11: longitud, unidad que no es texto e infinito
$ php vendor/bin/phpunit                   # suite completa con PostgreSQL local
Tests: 391, Assertions: 6533, Failures: 13
```

Las 13 fallas son las mismas que da `develop` en esta máquina: PatientPostgresTest y SalasCamas exigen el puerto 55433 de CI, más IndicatorHospitalIntegrationTest y PublishedContractsTest. Ninguna es del catálogo.

## Reversión

Se revierte el PR y se ejecuta `php artisan config:clear`. No hay migración que revertir. Las reglas nuevas solo rechazan altas nuevas: no tocan versiones existentes.

## Bloqueos del despliegue final

| Bloqueo | Responsable | Acción verificable |
|---|---|---|
| No hay staging ni on-premise autorizado | Coordinación del proyecto | Entorno, secretos y ventana de despliegue |
| `laboratorio.catalogo.administrar` para admin_hospital en `pending` | ASII-02 | Aprobar o rechazar y probar la matriz integrada |
| `local_user_refs` sembradas a mano | Tenants (ASII-25) | Sincronizar las membresías CENTRAL por hospital |
| No hay límite de tasa comprobado | API Gateway | Política y prueba común para `/api/v1` |
