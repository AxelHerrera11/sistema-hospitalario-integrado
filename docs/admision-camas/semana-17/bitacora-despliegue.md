# Bitácora de despliegue: admisión y camas (Semana 17)

## Despliegue local de verificación (28/10/2026)

Esto no es staging. El entorno es macOS con PHP 8.4.21, PostgreSQL 18.4 y Node 24.14.1. Se partió de bases vacías `his_central_e2e` y `his_hospital_e2e`, se aplicaron las migraciones de la rama y los datos son ficticios.

| # | Paso | Comando | Resultado |
|---:|---|---|---|
| 1 | Código | `git switch feature/asii-07-semana-17-admision-seguridad-axelherrera11` (base: `develop`) | Rama sin migraciones nuevas |
| 2 | Dependencias | `composer install` y `npm ci` | Desde los lock de `develop` |
| 3 | Variables | `APP_KEY`, `JWT_SECRET`, `CENTRAL_DB_*`, `HOSPITAL_DB_*` y `HOSPITAL_DB_TENANT_ID` fuera del repositorio; `APP_DEBUG=false` | Secretos generados para la sesión; no se imprimen |
| 4 | Esquema | `composer migrate:central`, `migrate:framework` y `migrate:hospital` | Migraciones aplicadas sobre bases vacías |
| 5 | Datos | `E2E_ALLOW_SEED=1 php tests/E2E/fixtures/seed-e2e.php`; recepción, `local_user_refs`, dos pacientes y las camas M-201 y M-202 en HOSPITAL | 2 hospitales, 3 usuarios y 2 camas libres |
| 6 | Rutas | `php artisan route:list --path=api/v1/admissions` y `--path=api/v1/beds/available` | `POST admissions` y `GET beds/available` |
| 7 | Interfaz | `VITE_API_URL=/api/v1 npm run build` | `✓ 142 modules transformed` |
| 8 | Servidor | `cd public && php -S 127.0.0.1:8095 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php` | Un segundo servidor en 8096 con `HOSPITAL_DB_PORT=5999` simula la base caída |
| 9 | Recorrido | `bash evidencia/recorrido-seguridad.sh …` | **11/11** ([salida](./evidencia/salida-recorrido-seguridad.txt), [psql](./evidencia/salida-psql.txt)) |

`artisan serve` no se usa porque no pasa las variables de entorno al proceso hijo.

## Pruebas ejecutadas

```text
$ php vendor/bin/phpunit tests/Feature/Admision
OK (10 tests, 103 assertions)            # 5 de S16 + 5 de seguridad (AdmissionSecurityTest)
$ php micro-his/admision-camas/tests/{run,whitebox,blackbox,api}.php
32, 12, 12 y 15 comprobaciones OK         # whitebox suma el límite del motivo
$ node --test resources/js/modules/admision/*.test.mjs
pass 1, fail 0
$ php vendor/bin/phpunit                   # suite completa con PostgreSQL local
Tests: 392, Assertions: 6557, Failures: 13
```

Las 13 fallas son las mismas que da `develop` en esta máquina, con o sin esta rama: PatientPostgresTest y SalasCamas exigen el puerto 55433 de CI, más IndicatorHospitalIntegrationTest y PublishedContractsTest. Ninguna es de admisión.

## Reversión

Se revierte el PR y se ejecuta `php artisan config:clear`. No hay migración que revertir. Las reglas nuevas solo rechazan entradas nuevas: no tocan episodios ni estancias existentes.

## Bloqueos del despliegue final

| Bloqueo | Responsable | Acción verificable |
|---|---|---|
| No hay staging ni on-premise autorizado | Coordinación del proyecto | Entorno, secretos y ventana de despliegue |
| `local_user_refs` sembradas a mano | Tenants (ASII-25) | Sincronizar las membresías CENTRAL por hospital |
| Una sola conexión HOSPITAL | Plataforma/Tenants | Elegir la base por tenant y probar el aislamiento físico |
| No hay límite de tasa comprobado | API Gateway | Política y prueba común para `/api/v1` |
