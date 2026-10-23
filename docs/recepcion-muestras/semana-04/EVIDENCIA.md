# Evidencia ejecutada — Semana 4

Fecha: 23 de octubre de 2026. PHP 8.2.12, PostgreSQL 16, base exclusiva his_reception_test en 127.0.0.1:55433.

| Comando | Resultado real |
|---|---|
| php micro-his/recepcion-muestras/tests/run.php | OK 39 comprobaciones |
| php micro-his/recepcion-muestras/tests/lsp.php | OK 2 adaptadores, 6 comprobaciones |
| php micro-his/recepcion-muestras/tests/postgres.php | OK 13 comprobaciones PostgreSQL reales |
| php micro-his/recepcion-muestras/demo.php | status 201, accepted, código generado |
| php micro-his/recepcion-muestras/demo.php --pdo | status 201, accepted, código generado |
| php vendor/bin/pint --test micro-his/recepcion-muestras app/Modules/Reception | passed |
| php artisan test --filter=Reception --no-ansi | No tests found: los Feature llegan en S05; no se cuenta como prueba aprobada |

Ambas demos usan JsonView y publican solo id, status, reason, code y received_at. La versión PDO crea y elimina un esquema temporal propio; no modifica las bases clínicas. El puerto de órdenes sigue siendo un doble explícito.

Mermaid CLI regeneró mvc.svg desde fuentes/mvc.mmd. No se afirma cobertura, aceptación clínica ni integración con ASII-16. Los logs completos de esta ejecución se adjuntan al cuerpo del PR.
