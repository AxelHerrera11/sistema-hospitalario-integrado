# Métricas medidas de paginación móvil (Semana 10)

Valores obtenidos sobre PostgreSQL 16 con el volumen sintético de medición
(1 003 camas, 22 salas, 50 002 estancias). Todos los datos son ficticios.

| Métrica | Consulta | Resultado |
|---|---|---|
| Filas de la fotografía completa | `SELECT count(*) FROM beds` | 1 003 |
| Filas por página | `LIMIT 25` sobre la consulta por clave | 25 |
| Tamaño del JSON de una página | `pg_column_size(json_agg(page)::text)` | 2 999 bytes (2,9 KiB) |
| Tamaño estimado de la lista completa | proporcional a 1 003 filas | ~120 KiB |
| Tiempo de la página por clave | `EXPLAIN (ANALYZE)` | 0,130 ms |
| Plan de la página | `EXPLAIN (ANALYZE)` | Index Scan `wards_code_unique` + Index Scan `beds_ward_id_index` |
| Consulta completa con `EXISTS` correlado | `EXPLAIN (ANALYZE)` | 4,764 ms y Seq Scan sobre 50 002 estancias |

## Lectura de las cifras

1. La diferencia entre 2,9 KiB y 120 KiB por consulta es la que decide si la
   pantalla funciona con 3G en una guardia.
2. La página por clave no necesita leer la lista previa: el plan usa índices sobre
   `wards` y `beds`.
3. La fotografía completa con `EXISTS` correlado es la consulta más cara del módulo y
   por eso la semana 11 mide cómo mejorarla.

## Advertencia sobre el volumen

El volumen es sintético y está sobredimensionado respecto de un hospital pequeño
para poder observar diferencias de plan. Las cifras absolutas sirven para comparar
variantes entre sí, no como objetivo de rendimiento absoluto. Cuando exista el seeder
común con datos realistas, la medición debe repetirse con ese volumen.
