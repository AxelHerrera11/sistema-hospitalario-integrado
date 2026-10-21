-- Indices propuestos para las consultas de ocupacion (Semana 11).
-- NO se aplican en este PR: el numero de archivo debe acordarse con el grupo,
-- igual que la migracion correctiva de la semana 7.
-- Medicion: PostgreSQL 16, 1 003 camas, 50 002 estancias sinteticas.
--   Consulta original (EXISTS correlado): 4.764 ms con Seq Scan sobre 50 001 filas descartadas.
--   Consulta preagregada con este indice: 1.523 ms con Bitmap Index Scan.
--   Consulta con semi-join:                0.241 ms.

-- Indice 1: filtro temporal por corte de ocupacion.
CREATE INDEX bed_stays_started_at_index ON bed_stays (started_at, ended_at);

-- Indice 2: historial de estancias por cama.
CREATE INDEX bed_stays_bed_started_at_index ON bed_stays (bed_id, started_at, ended_at);

-- Nota de despliegue: en produccion se recomienda CREATE INDEX CONCURRENTLY, que no
-- puede ejecutarse dentro de una transaccion de migracion; aplicar en ventana de
-- mantenimiento o como comando posterior a la migracion.

-- Reversion:
-- DROP INDEX bed_stays_started_at_index;
-- DROP INDEX bed_stays_bed_started_at_index;

-- Pruebas que debe cubrir la migracion cuando se aplique:
--   1. Las dos variantes de la consulta de ocupacion devuelven el mismo conjunto de filas.
--   2. EXPLAIN ANALYZE usa bed_stays_started_at_index en la consulta preagregada.
--   3. El indice no cambia el resultado de ninguna prueba de integridad del repositorio.
--   4. El down elimina ambos indices y el esquema vuelve a su estado previo.
