# Evidencia — S01, 23 de octubre de 2026

Se revisó la matriz ASII-18 del README y el comentario de Javier en #692. Esta semana aporta análisis UML; el micro ejecutable se incorpora en S03 y la API en S05.

- Mermaid CLI: actividad y secuencia regeneradas correctamente con Microsoft Edge local y las fuentes de fuentes/.
- Los tres SVG se analizaron correctamente como XML.
- `php artisan test --filter=PublishedContractsTest --no-ansi`: 8 passed (2218 assertions), 55.80s; salida completa pegada en el PR.
- `php artisan test --group=database-integrity --no-ansi`: 125 passed (1392 assertions), 115.83s; PostgreSQL 16 en 55431/55433.
- Línea base de develop 80ab28de: 346 passed (6247 assertions), 177.40s; no fallos previos.
- Pint: no aplica, esta rama modifica exclusivamente Markdown, Mermaid y SVG.
- Micro `run.php`, `postgres.php`, `http.php` y filtro Reception: aún no existen en S01; no se atribuyen resultados de otra rama.
- La evidencia verifica generación y contratos; no acredita validación clínica, defensa oral ni ejecución funcional de futuras semanas.

La respuesta 422 termina antes del servicio; la ausencia del puerto de órdenes termina en 503 sin escritura. Solo la colisión reintenta y el primer guardado exitoso sale del ciclo. La impresión sucede tras el 201 y permite terminar dejando la etiqueta pendiente.
