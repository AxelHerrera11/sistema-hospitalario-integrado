# Evidencia del entorno — 28/10/2026

Ambiente: Windows local de desarrollo/pruebas aisladas, base `3ea30faa` más los
cambios de esta entrega. PHP 8.2.12, PHPUnit 11.5.55, Node 24.15.0, Vite 7.3.2.
No se levantó un servidor de staging/on-premise. La guía
[de despliegue](../../CI-E2E/guia-despliegue/README.md) propone procedimientos,
pero deja host y procesos pendientes. No se ejecutaron sus migraciones ni semillas.
La estudiante confirmó usar solo el entorno de pruebas aislado, sin acceso visual real.

`docker ps --format '{{.Names}} {{.Ports}}'` confirmó his-central-test:55431 e
his-hospital-test:55433 activos. Una sonda PHP temporal cargó las variables de
phpunit.xml en el proceso, arrancó Laravel, exigió testing/bases *_test y ejecutó
`DB::connection('central')->select('SELECT 1')` y lo mismo en hospital: ambos PASS.
No leyó filas de usuarios ni secretos y se eliminó al finalizar. CENTRAL PostgreSQL
solo se comprueba con esa sonda; la identidad de los tests usa SQLite en memoria.

## Comandos y resultados nuevos desde la raíz

| Comando | Resultado observado | Duración reportada |
|---|---|---|
| `php artisan test tests/Feature/SalasCamas/CatalogAuthenticatedIntegrationTest.php` | PASS: 1 test, 28 aserciones | 1.17 s |
| `php artisan test tests/Feature/SalasCamas/WardCatalogEndpointTest.php` | PASS: 13 tests, 54 aserciones | 0.99 s |
| `php artisan test tests/Feature/SalasCamas/CatalogHospitalIsolationTest.php` | PASS: 3 tests, 37 aserciones | 1.16 s |
| `php artisan test tests/Feature/SalasCamas/CatalogLaravelIntegrationTest.php` | PASS: 1 test, 23 aserciones | 0.65 s |
| `node --test tests/Frontend/WardCatalogNavigationTest.mjs tests/Frontend/WardCatalogFlowTest.mjs` | PASS: 6 tests, 0 fallos/skips/cancelados | 417.5981 ms |
| `php micro-his/salas-camas/tests/run.php` | 4 mensajes PASS: consulta, creación/default, estado inválido y fallo fake | No reportada |
| `npm run build` | PASS: 142 módulos | 3.88 s |
| `php artisan route:list --path=api` | PASS: 30 rutas; ambas GET de catálogo | No reportada |
| `php -l tests/Feature/SalasCamas/CatalogAuthenticatedIntegrationTest.php` | Sin errores de sintaxis | No reportada |
| `git diff --check`, `git diff --cached --check` | PASS antes del commit | No reportada |

## Ejecución segura de Semana 15 no integrada

Se extrajeron con `git show` desde
`origin/feature/asii-06-semana-15-salas-camas-madelinceron` (36d1509d) los archivos
`tests/Unit/SalasCamas/CatalogRulesTest.php` y
`tests/Feature/SalasCamas/CatalogConstraintsTest.php` a una carpeta temporal única.
En la copia unitaria se sustituyó solo el require relativo por la ruta absoluta
al bootstrap actual; no se alteró lógica ni rama anterior. Comando ejecutado:

```text
php vendor/phpunit/phpunit/phpunit --configuration phpunit.xml <temporal>/CatalogRulesTest.php <temporal>/CatalogConstraintsTest.php
```

PASS: 10 tests, 57 aserciones, 0.775 s, sin fallos/errores; copias eliminadas.
Sumando las cinco ejecuciones PHP: **28 tests, 199 aserciones**; Node y standalone
se informan aparte. No se presentan estos resultados como checks de Actions.
No se cambió .env ni se eliminaron bases, volúmenes o contenedores.
