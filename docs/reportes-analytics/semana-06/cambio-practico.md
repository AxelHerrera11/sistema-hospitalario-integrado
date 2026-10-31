# Cambio práctico (ejercicio simulado)

**Naturaleza:** ejercicio propio basado en un defecto real hallado en la auditoría del 30/10/2026. **No lo pidió ningún docente y no se expuso.** Sustituirlo cuando exista el cambio real de la defensa.

| Campo | Contenido |
|---|---|
| Cambio | Rechazar fechas imposibles en el periodo |
| Antes | `2026-02-31` se desbordaba a marzo y el API respondía 200 |
| Después | 422 `periodo_invalido`, sin porcentaje |
| Archivo | `micro-his/reportes-analytics/src/Domain/Period.php` |
| Prueba | `tests/run.php` y `test_rechaza_fechas_imposibles_con_422_sin_porcentaje` |
| Impacto | Solo la regla de dominio; la pantalla ya usaba fechas nativas |
