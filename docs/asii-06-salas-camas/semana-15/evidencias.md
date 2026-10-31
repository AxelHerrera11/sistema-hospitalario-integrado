# Evidencias de ejecución — 28/10/2026

Resultados nuevos en la rama Semana 15, base `3ea30faa`, con los dos archivos
de prueba de esta entrega. PHP 8.2.12 y PHPUnit 11.5.55; configuración phpunit.xml.
Desde raíz, dependencias instaladas, PostgreSQL de test disponible en 55433.
No se ejecutaron migraciones, cambios .env ni operaciones sobre datos reales.

| Comando exacto | Resultado observado | Duración reportada |
|---|---|---|
| `php vendor/phpunit/phpunit/phpunit tests/Unit/SalasCamas/CatalogRulesTest.php` | PASS, 5 tests, 17 aserciones (3 U + 2 CB) | 0.061 s |
| `php artisan test tests/Feature/SalasCamas/CatalogConstraintsTest.php` | PASS, 5 tests, 40 aserciones | 1.08 s |
| `php artisan test tests/Feature/SalasCamas/WardCatalogEndpointTest.php` | PASS, 13 tests, 54 aserciones (incluye los 4 CN) | 0.95 s |
| `php artisan test tests/Feature/SalasCamas/CatalogHospitalIsolationTest.php` | PASS, 3 tests, 37 aserciones | 1.28 s |
| `php artisan test tests/Feature/SalasCamas/CatalogLaravelIntegrationTest.php` | PASS, 1 test, 23 aserciones | 0.64 s |
| `node --test tests/Frontend/WardCatalogNavigationTest.mjs` | PASS, 2 tests, 0 fallos/skipped/cancelled | 280.5608 ms |
| `php micro-his/salas-camas/tests/run.php` | 4 mensajes PASS: consulta, creación/default, estado inválido y fallo fake | No reportada |
| `npm run build` | PASS, Vite 7.3.2, 142 módulos | 3.97 s |
| `php artisan route:list --path=api` | PASS, 30 rutas; wards/beds solo GET | No reportada |
| `php -l tests/Unit/SalasCamas/CatalogRulesTest.php` | Sin errores de sintaxis | No reportada |
| `php -l tests/Feature/SalasCamas/CatalogConstraintsTest.php` | Sin errores de sintaxis | No reportada |
| `git diff --check` y `git diff --cached --check` | PASS antes del commit | No reportada |

PHPUnit suma 27 tests y 171 aserciones, sin errores/fallos/skips reportados.
Node y mensajes standalone se informan aparte; no se suman como aserciones PHP.
No son resultados heredados de Semanas 13/14 ni checks de GitHub Actions.

La integración existente crea dos wards y camas mediante servicio/PDO, verifica
relaciones, filtrado y rollback. La nueva suite de restricciones añade negativos
que aquella no cubre: SQLSTATE 23503 (FK), 23505 (unicidad), 23514 (CHECK).
Se verifica estado original y ausencia del ward ficticio tras rollback.
El aislamiento usa dos esquemas en una base de test, no dos servidores hospitalarios.
Su CENTRAL es SQLite en memoria; no se presenta como prueba de CENTRAL PostgreSQL.

No se hizo login visual ni evaluación de teclado/reflow/lector de pantalla.
Build comprueba compilación; Node comprueba navegación/permisos, no renderizado.
Los errores de persistencia se cubren mediante fake y conexión inexistente en
la suite endpoint; no se simula una caída productiva ni se mide disponibilidad.
