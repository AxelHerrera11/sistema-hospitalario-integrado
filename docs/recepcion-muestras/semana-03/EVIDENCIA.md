# Evidencia S03 — 23 oct 2026

- `php micro-his/recepcion-muestras/tests/run.php`: 39 comprobaciones; aceptación, rechazo, replay, doble aceptación, límites, lectura y rollback por fallo de escritura.
- `php micro-his/recepcion-muestras/tests/lsp.php`: 2 adaptadores, 6 comprobaciones.
- Pint: passed. `php artisan test --filter=Reception`: No tests found; se declara sin atribuir aprobación.
- `php artisan test --group=database-integrity --no-ansi`: 125 passed (1392 assertions), 156.76s.
- Mermaid regeneró capas.svg; fuentes editables en fuentes/. Salidas completas en PR #690.
- PostgreSQL real del micro se verifica en #840; no se afirma concurrencia ni cobertura clínica. Generar 1000 códigos aleatorios no prueba unicidad y se sustituyó por el índice SQL verificable.

## Complemento #840 — PostgreSQL real

RECEPTION_TEST_PORT=55433; tests/postgres.php: 13 comprobaciones reales. Pint: passed; run.php: 39; lsp.php: 6 comprobaciones con 2 adaptadores. Filtro Reception: No tests found. database-integrity: 125 passed (1392 assertions), 164.18s. Diagrama postgres.svg regenerado desde fuentes/postgres.mmd. Salidas completas en #840; esquema temporal aislado, con doble de órdenes y sin atribuir una integración clínica no ejecutada.
