# Trazabilidad requisito–caso — `PROPUESTO`

| Regla | Casos |
|---|---|
| SD-PRE-01: `mild` → advertencia | U-02 |
| SD-PRE-01: `moderate`/`unknown` → 422 con `exception_allowed: true` | U-03, U-04 |
| SD-PRE-01: `severe`/`life_threatening` → 422 sin excepción | U-05, U-06 |
| Ningún 422 persiste | U-03 a U-06 |
| La falla de alergias no equivale a ausencia de alergias | U-07 |
| La falla de persistencia no equivale a éxito | U-08 |
| No se modifica `isGrave()` | Revisar cambio del dominio al ejecutar la suite |
| `excepcion_alergia` sigue pendiente, sin endpoint | Revisar contrato/rutas; no crear caso de endpoint |

Los casos siguen pendientes de implementación y ejecución; esta tabla no
representa cobertura ni resultado de CI.
