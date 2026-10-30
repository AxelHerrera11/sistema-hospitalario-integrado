# Semana 15 — Pruebas unitarias, caja blanca y caja negra

**Tarea:** diseñar y ejecutar pruebas trazadas a RF/RNF: unitarias de las reglas del expediente; caja blanca con
ramas/condiciones del flujo; caja negra con equivalencia, límites, permisos y errores, sin probar módulos ajenos.

## Entregables

| Archivo | Contenido |
|---|---|
| [`matriz-rf-rnf-pruebas.md`](matriz-rf-rnf-pruebas.md) | Cada RF/RNF con sus pruebas y estado |
| [`diseno-pruebas.md`](diseno-pruebas.md) | 24 unitarias, 16 de caja blanca (tabla de ramas y condiciones), 21 de caja negra por adaptador (clases, límites, permisos y errores) |
| [`defectos.md`](defectos.md) | 10 defectos reales: 9 cerrados con verificación, 1 abierto |
| [`evidencias/`](evidencias/) | Salidas reales de cada comando (tabla abajo) |

Capturas del prototipo: `../semana-11/evidencias/` (camino feliz y error crítico, escritorio y móvil).

## Comandos y resultados (2026-10-23, PHP 8.2.12, Node 22.13.1)

| Comando | Resultado | Evidencia |
|---|---|---|
| `php micro-his/expediente-medico/tests/run.php` | 89 OK, 0 FALLO (código 0) | [`01-micro-his-run.txt`](evidencias/01-micro-his-run.txt) |
| `node --test micro-his/expediente-medico/tests/ui-estados.test.mjs` | 11 pass, 0 fail | [`02-ui-node.txt`](evidencias/02-ui-node.txt) |
| `php vendor/bin/phpunit tests/Feature/ExpedienteMedico --testdox` | OK (4 tests, 24 assertions) | [`03-feature-laravel.txt`](evidencias/03-feature-laravel.txt), [`03-feature-laravel.junit.xml`](evidencias/03-feature-laravel.junit.xml) |
| `php micro-his/qa-calidad-versiones/bin/micro-his-suites.php <dir>` (ejecutor del CI) | `OK expediente-medico`, 21 módulos OK | [`04-ci-micro-his-suites.txt`](evidencias/04-ci-micro-his-suites.txt) |
| `php vendor/bin/phpunit tests/Feature/Rbac/PublishedContractsTest.php` | OK (8 tests) | [`05-contratos-rbac.txt`](evidencias/05-contratos-rbac.txt) |
| `php vendor/bin/phpunit --testsuite Unit,Feature` (todo el repositorio) | 217 pruebas, 1 error ajeno: Salas y camas exige PostgreSQL (no disponible) | [`06-suite-repositorio.txt`](evidencias/06-suite-repositorio.txt) |

## Ubicación de las pruebas

`micro-his/expediente-medico/tests/{harness, unitarias, caja-blanca, caja-negra, integracion-pdo, arquitectura, run}.php`,
`micro-his/expediente-medico/tests/ui-estados.test.mjs` y `tests/Feature/ExpedienteMedico/`.

## Estado

Todas las pruebas automáticas pasan. No ejecutado: PostgreSQL real, lector de pantalla y teléfono físico.
