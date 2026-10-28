# Evidencia — Semana 5: persistencia clínica

Ejecución local del 23 de octubre de 2026, PHP 8.2.12 y PostgreSQL 16. Base hospital de pruebas en 127.0.0.1:55433.

- php artisan test --filter=SpecimenIntegrityTest --no-ansi: 5 passed (74 assertions), 24.22s.
- php micro-his/recepcion-muestras/tests/run.php: 39 comprobaciones aprobadas.
- php micro-his/recepcion-muestras/tests/lsp.php: 2 adaptadores, 6 comprobaciones aprobadas.
- RECEPTION_TEST_PORT=55433; php micro-his/recepcion-muestras/tests/postgres.php: 13 comprobaciones PostgreSQL aprobadas.
- Pint sobre micro, adaptador, migración y SpecimenIntegrityTest: passed.
- migrate:status confirma 000601 antes de 000700 y 000900. Los pasos de rollback son 9/5/4/7/1.

php artisan test --group=database-integrity --no-ansi: 130 passed (1466 assertions), 678.70s. La suite incluye los cinco tests de rollback y los cinco tests nuevos de specimens. Las rutas HTTP y sus pruebas se completan en la parte siguiente; esta entrega no acredita todavía un módulo íntegro.

Los fallos de conexión por restricciones del sandbox se resolvieron ejecutando las pruebas con acceso autorizado al PostgreSQL local. El puerto de órdenes real falla explícitamente con LAB_ORDER_UNAVAILABLE (503), sin inventar órdenes ni tablas externas.
