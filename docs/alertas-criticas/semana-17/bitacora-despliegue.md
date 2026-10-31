# Bitácora de despliegue: alertas críticas (Semana 17)

## Despliegue local de verificación (28/10/2026)

Esto no es staging. El entorno es macOS con PHP 8.4.21, PostgreSQL 18.4 y Node 24.14.1. Se partió de bases vacías `his_central_e2e` y `his_hospital_e2e`, se aplicaron las migraciones de la rama, incluida `critical_alerts` de #1321, y los datos son ficticios.

| # | Paso | Comando | Resultado |
|---:|---|---|---|
| 1 | Código | `git switch feature/asii-21-semana-17-alertas-seguridad-axelherrera11` (incluye #1321 y #1322) | La migración 000660 viene de #1321 |
| 2 | Dependencias | `composer install` y `npm ci` | Desde los lock de `develop` |
| 3 | Variables | `APP_KEY`, `JWT_SECRET`, `CENTRAL_DB_*`, `HOSPITAL_DB_*` y `HOSPITAL_DB_TENANT_ID` fuera del repositorio; `APP_DEBUG=false` | Secretos generados para la sesión; no se imprimen |
| 4 | Esquema | `composer migrate:central`, `migrate:framework` y `migrate:hospital` | Migraciones aplicadas sobre bases vacías |
| 5 | Datos | `seed-e2e.php`; bioquímico, jefatura, superadmin y recepción ficticios con `local_user_refs`; perfil y asignación de cuidado de la médica, episodios y la prueba GLU (el mismo SQL de `docs/alertas-criticas/semana-16/evidencia/datos-e2e-alertas.sh`, en la validación de la Semana 16) | 6 usuarios, 2 episodios y 1 asignación |
| 6 | Rutas | `php artisan route:list --path=api/v1/critical-alerts` | Las 5 rutas |
| 7 | Interfaz | `VITE_API_URL=/api/v1 npm run build` | `✓ 142 modules transformed` |
| 8 | Servidor | `cd public && php -S 127.0.0.1:8095 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php` | Un segundo servidor en 8096 con `HOSPITAL_DB_PORT=5999` simula la base caída |
| 9 | Recorrido | `bash evidencia/recorrido-seguridad.sh …` | **16/16** ([salida](./evidencia/salida-recorrido-seguridad.txt), [psql](./evidencia/salida-psql.txt)) |

## Pruebas ejecutadas

```text
$ php vendor/bin/phpunit tests/Feature/Alertas
OK (10 tests, 163 assertions)            # 5 de #1322 + 5 de seguridad (CriticalAlertSecurityTest)
$ php micro-his/alertas-criticas/tests/{run,whitebox,blackbox,api}.php
30, 15, 12 y 14 comprobaciones OK         # blackbox suma el valor infinito
$ php vendor/bin/phpunit                   # suite completa con PostgreSQL local
Tests: 397, Assertions: 6640, Failures: 13
```

Las 13 fallas son las mismas que da `develop` en esta máquina: PatientPostgresTest y SalasCamas exigen el puerto 55433 de CI, más IndicatorHospitalIntegrationTest y PublishedContractsTest. Ninguna es de alertas.

## Reversión

Se revierte el PR y se ejecuta `php artisan config:clear`. Esta entrega no agrega migraciones. Revertir la tabla de #1321 requiere `migrate:rollback` de la 000660, y la tabla solo se puede borrar si no tiene alertas: el trigger impide borrar filas.

## Bloqueos del despliegue final

| Bloqueo | Responsable | Acción verificable |
|---|---|---|
| No hay staging ni on-premise autorizado | Coordinación del proyecto | Entorno, secretos y ventana de despliegue |
| El evento de resultado validado se publica por HTTP | Resultados (ASII-19/20) | Publicar desde la validación real o por la cola común |
| Los permisos de enfermería siguen en `pending` | ASII-02 | Aprobar o rechazar y probar la matriz integrada |
| `local_user_refs` y `care_assignments` sembradas a mano | Tenants (ASII-25) y Expediente | Sincronización y asignación desde sus módulos |
| No hay límite de tasa comprobado | API Gateway | Política y prueba común para `/api/v1` |
