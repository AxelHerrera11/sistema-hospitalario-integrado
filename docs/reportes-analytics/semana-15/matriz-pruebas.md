# ASII-23 Semana 15 — Matriz de trazabilidad de pruebas

**Issue:** `Refs #152`. Cada requisito del módulo con su prueba, PR y
resultado real de ejecución (todos ejecutados localmente el 2026-10-23/24).

## Matriz

| Requisito | Prueba (ubicación) | PR | Resultado |
|---|---|---|---|
| R1 Fórmula TCI-1.0 (25.00 % con 4/1) | `micro-his/reportes-analytics/tests/run.php` esc. 1 | #1237/#1239 | OK (11 escenarios) |
| R2 Filtro por sala | `run.php` esc. 2 (33.33 % con HOSP-UCI) | #1237/#1239 | OK |
| R3 Trazabilidad sin datos personales | `run.php` esc. 3 + `IndicatorHospitalIntegrationTest` (no-leak) | #1237/#1239, #1269 | OK |
| R4 Periodo invertido → 422 | `run.php` esc. 4 + `IndicatorEndpointTest` | #1237, #1245 | OK |
| R5 Entrada faltante → 422 | `IndicatorEndpointTest` (sin `from`) | #1245 | OK |
| R6 Offset de zona explícito → 422 | `run.php` esc. 6 | #1237/#1239 | OK |
| R7 Falla de persistencia → 503 sin cifras | `run.php` esc. 7 + `IndicatorEndpointTest` (PDOException) | #1237, #1245 | OK |
| R8 Sin datos en periodo → estado | `run.php` esc. 8 + base real 2020 | #1237, #1269 | OK |
| R9 Lectura incoherente → 503 | `run.php` esc. 9 + `IndicatorEndpointTest` | #1237/#1239, #1245 | OK |
| R10 Método no permitido → 405 (o 404 GET) | `run.php` esc. 10 + `IndicatorEndpointTest` | #1237, #1245 | OK |
| R11 Cero válido 0.00 | `run.php` esc. 11 | #1237/#1239 | OK |
| R12 401 sin sesión | `IndicatorEndpointTest` | #1245 | OK |
| R13 403 sin permiso, tenant distinto | `IndicatorEndpointTest` | #1245 | OK |
| R14 Base real HOSPITAL (55433) | `IndicatorHospitalIntegrationTest` (33.33 % con rollback) | #1269 | OK |

Nota de trazabilidad (2026-10-26): R14 se re-ejecutó en un entorno aislado
reproducible (PostgreSQL efímero 55431/55433, bases `*_test` migradas desde
cero) y reveló dependencia ambiental de filas preexistentes de
`local_patients`; corregida con semilla sintética propia dentro de la
transacción (14 pruebas, 64 aserciones OK).
| R15 Base inexistente → 503 no 500 | `IndicatorHospitalIntegrationTest` | #1269 | OK |
| R16 Accesibilidad ARIA/teclado persistente | `reportes.a11y.test.mjs` (11 comprobaciones) | #1283 | OK |
| R17 Mensajes seguros de UI | `reportes.test.mjs` (15 aserciones) | #1248 | OK |
| R18 Navegación y permisos del router | `navigation.test.mjs` + `permissions.test.mjs` | #1248 | OK |
| R19 LSP (contrato del lector) | `tests/example-lsp.php` (7 comprobaciones) | #1233 | OK |
| R20 Regresión global del sistema | `vendor/bin/phpunit` completa | #1245+ | OK (361 pruebas, 6357 aserciones) |

## Cómo reproducir la matriz

```bash
php micro-his/reportes-analytics/tests/run.php
php micro-his/reportes-analytics/tests/example-lsp.php
vendor/bin/phpunit tests/Feature/Reportes
node resources/js/modules/reportes/reportes.test.mjs
node resources/js/modules/reportes/reportes.a11y.test.mjs
node resources/js/router/navigation.test.mjs
node resources/js/router/permissions.test.mjs
vendor/bin/phpunit   # suite completa
```
