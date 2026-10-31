# Rendimiento de la disponibilidad (RNF-MED-04)

**Requisito (Semana 2):** consulta de disponibilidad ágil, **≤ 500 ms (p95) para 20 médicos y 14 días**.

**Interpretación.** La API recibe una sola fecha, así que «14 días» admite dos lecturas. Se midieron las dos y se informan por separado: **(a)** una consulta de un día con 20 médicos, y **(b)** una ventana de 14 días como 14 consultas seguidas del cliente. No se eligió la lectura más cómoda.

## Cómo se midió

[`evidencia/medir-rendimiento.php`](./evidencia/medir-rendimiento.php) siembra datos **ficticios** en una base HOSPITAL vacía y migrada (se niega a correr si el nombre de la base no termina en `_test` o `_e2e`) y ejecuta el `Router` del micro-HIS contra PostgreSQL 16:

- 20 médicos con la misma especialidad, horario de mañana y tarde los siete días (bloques de 30 minutos), 1120 citas (4 por médico y día durante 14 días), cada una con su paciente ficticio.
- 30 repeticiones × 14 días = 420 consultas de un día; las 30 ventanas de 14 consultas seguidas se miden aparte.
- Hay un calentamiento previo (abrir la conexión) que no se cuenta.

Entorno: PostgreSQL 16 en un contenedor de Docker Desktop sobre Windows 11, PHP 8.3.33. **Mide la consulta y el cálculo de franjas, no la red ni HTTP ni Laravel.** Cifras de un solo equipo; sirven para comparar antes y después, no como garantía en otro entorno.

## Resultados

| Medida | Antes | Después |
|---|---:|---:|
| (a) Un día, 20 médicos: mediana | 104,6 ms | 8,9 a 9,3 ms |
| (a) Un día: **p95** | **119,7 ms** | **11,5 a 13,6 ms** |
| (b) Ventana de 14 días: mediana | 1482,5 ms | 127,0 a 130,6 ms |
| (b) Ventana de 14 días: **p95** | **1561,5 ms** | **145,1 a 193,6 ms** |
| Límite | 500 ms (p95) | 500 ms (p95) |
| Resultado | (a) cumple · (b) **no cumple** | (a) cumple · (b) cumple |

«Después» muestra el rango de dos corridas completas sobre la misma base sembrada de nuevo.

## Qué lo causaba y qué cambió

`QueryAvailability` hacía **tres consultas por médico** (¿tiene horario?, ¿qué franjas tiene hoy?, ¿qué citas tiene?): unas 60 idas y vueltas para 20 médicos. Ahora hace tres lecturas en total: los médicos vigentes, sus horarios y sus citas.

- Los puertos `DoctorScheduleProvider` y `AppointmentReader` ganan `schedulesFor()` y `busyIntervalsFor()`, que reciben todos los identificadores. Los métodos de uno en uno se conservan y delegan en los de lote, para no duplicar lógica.
- El comportamiento no cambia: una prueba compara el resultado del lote con el de uno en uno, incluidos el médico sin horario (`null`), el que tiene horario pero no ese día (`[]`) y el médico desconocido; las pruebas de PostgreSQL (25 tests) y las de Laravel (62 tests) siguen en verde.

## Lo que esta medición no cubre

- No hay medición con HTTP, JWT ni RBAC: el límite de 500 ms se midió sobre el núcleo.
- No se midió con más de 20 médicos ni con carga concurrente.
- Los datos son uniformes (4 citas por médico y día); una agenda real puede ser más irregular.
- El plan de ejecución no se revisó con `EXPLAIN`; los índices de `appointments` y `doctor_schedules` son los de las migraciones.
