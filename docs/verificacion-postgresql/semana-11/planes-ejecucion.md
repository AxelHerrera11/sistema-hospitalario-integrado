# Planes de ejecución antes y después (Semana 11)

Extractos reales de `EXPLAIN (ANALYZE, BUFFERS)` sobre PostgreSQL 16 con 1 003 camas
y 50 002 estancias sintéticas. Corte: `2026-10-19 12:00:00+00`.

## Variante A · Consulta original, sin índice de fecha

```text
Incremental Sort  (actual time=3.589..4.672 rows=1003)
  ->  Nested Loop  (actual time=3.479..3.683 rows=1003)
        ->  Index Scan using wards_code_unique on wards w
        ->  Index Scan using beds_ward_id_index on beds b
        SubPlan 2
          ->  Seq Scan on bed_stays s  (actual time=0.017..3.454 rows=1 loops=1)
                Filter: ((started_at <= '2026-10-19 12:00:00+00') AND ((ended_at IS NULL) OR (ended_at > '2026-10-19 12:00:00+00')))
                Rows Removed by Filter: 50001
Execution Time: 4.764 ms
```

Lectura: el `EXISTS` correlado obliga a leer `bed_stays` una vez por cama; se
descartan 50 001 filas por cada cama evaluada.

## Variante B · Consulta original con índice `(bed_id, started_at, ended_at)`

```text
        SubPlan 2
          ->  Seq Scan on bed_stays s  (actual time=0.004..2.877 rows=1 loops=1)
                Rows Removed by Filter: 50001
Execution Time: 4.180 ms
```

Lectura: el índice compuesto **no se usó**. El planificador estimations que el filtro
deja pocas filas y prefiere la lectura secuencial. El índice correcto existe, pero la
forma de la consulta no lo aprovecha.

## Variante C · Preagregado con índice `(started_at, ended_at)`

```text
Hash Left Join  (actual time=0.184..0.380 rows=1003)
  ->  Hash Join  (actual time=0.013..0.146 rows=1003)
        ->  Seq Scan on beds b
        ->  Seq Scan on wards w
  ->  Hash  (actual time=0.169..0.170 rows=1)
        ->  Unique  (actual time=0.168..0.169 rows=1)
              ->  Sort  (actual time=0.167..0.168 rows=1)
                    ->  Bitmap Heap Scan on bed_stays s
                          ->  BitmapOr
                                ->  Bitmap Index Scan on bed_stays_open_care_episode_unique
                                ->  Bitmap Index Scan on bed_stays_started_at_index
Execution Time: 1.523 ms
```

Lectura: el `bed_stays` se lee una sola vez y el filtro temporal se resuelve con
índice.

## Variante C' · Preagregado sin índice de fecha

```text
  ->  Hash  (actual time=4.091..4.092 rows=1)
        ->  Unique  (actual time=4.091..4.092 rows=1)
              ->  Sort  (actual time=4.081..4.082 rows=1)
                    ->  Seq Scan on bed_stays  (actual time=0.004..4.077 rows=1)
Execution Time: 5.496 ms
```

Lectura: sin el índice, el preagregado es **más lento** que la versión original
(5,496 ms frente a 4,764 ms). El índice y la reescritura son un conjunto: por separado
no rinden.

## Variante D · Semi-join con índice `(started_at, ended_at)`

```text
  ->  Nested Loop  (actual time=0.128..0.129 rows=2)
        ->  Nested Loop  (actual time=0.100..0.101 rows=2)
              ->  Unique  (actual time=0.096..0.097 rows=2)
                    ->  Sort  (actual time=0.095..0.096 rows=2)
                          ->  Bitmap Heap Scan on bed_stays s
                                ->  BitmapOr
                                      ->  Bitmap Index Scan on bed_stays_open_care_episode_unique
                                      ->  Bitmap Index Scan on bed_stays_started_at_index
        ->  Index Scan using beds_pkey on beds b
        ->  Index Scan using wards_pkey on wards w
        ->  Index Scan using bed_stays_bed_id_index on bed_stays s_1
Execution Time: 0.241 ms
```

Lectura: el filtro temporal se resuelve por índice y solo se recorren las camas
afectadas.

## Conclusión

| Variante | Índice | Tiempo | Cambio contra la original |
|---|---|---:|---:|
| A · Original | ninguno adicional | 4,764 ms | — |
| B · Original | `(bed_id, started_at, ended_at)` | 4,180 ms | −12 % (índice no usado) |
| C · Preagregado | `(started_at, ended_at)` | 1,523 ms | −68 % |
| C' · Preagregado | ninguno adicional | 5,496 ms | +15 % (peor) |
| D · Semi-join | `(started_at, ended_at)` | 0,241 ms | **−95 %** |

La recomendación es reescribir la consulta en forma de semi-join o preagregado **y**
crear `bed_stays_started_at_index`. La semi-join devuelve solo las camas con estancia
activa; si la pantalla necesita todas las camas con un indicador, la variante C es la
adecuada.
