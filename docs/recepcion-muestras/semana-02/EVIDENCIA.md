# Evidencia — S02, 23 de octubre de 2026

- `php micro-his/recepcion-muestras/tests/lsp.php`: 2 adaptadores, 6 comprobaciones en memoria; aceptación, volumen insuficiente y tipo incorrecto conservan el contrato.
- `php vendor/bin/pint --test app/Modules/Reception/bootstrap.php micro-his/recepcion-muestras --no-ansi`: result passed.
- `php artisan test --filter=Reception --no-ansi`: No tests found; no se presenta como una suite aprobada.
- `php artisan test --group=database-integrity --no-ansi`: 125 passed (1392 assertions), 80.88s.
- Mermaid CLI regeneró contratos.svg desde fuentes/contratos.mmd.
- La fuente de dominio es exclusivamente micro-his; el cargador Laravel usa require_once. No hay dependencia del dominio hacia Laravel.
- Persistencia PDO, run.php, postgres.php y http.php se incorporan en S03/S05; no se atribuyen resultados futuros.
- Salida completa de los comandos en PR #689. La validación humana y clínica está pendiente.
