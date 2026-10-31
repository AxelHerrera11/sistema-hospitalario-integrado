# Matriz decisión → evidencia (Semana 6)

| Decisión | Requisito | Código | Evidencia ejecutable |
|---|---|---|---|
| Fórmula TCI-1.0 con dos decimales | Calcular el indicador | `CalculateCancellationRate` | `run.php`: 25.00 y 33.33 |
| `SIN_DATOS_EN_PERIODO` sin porcentaje | No mostrar 0 % falso | `IndicatorReading::withoutValue` | `run.php`: sin datos |
| Cero real como 0.00 | Distinguir cero de vacío | `CalculateCancellationRate` | `run.php`: cero válido |
| Periodo `[desde, hasta)` con offset | Periodo sin ambigüedad | `Period` | `run.php`: invertido, sin offset, fecha imposible |
| Puerto de una operación | Sustituir el lector (LSP) | `EpisodeSummaryReader` | `example-lsp.php` |
| Base caída: 503 sin cifra | No inventar datos | `ReportesIndicatorController` | `IndicatorHospitalIntegrationTest` |
| Solo `count` y `SUM` | Privacidad | `PdoEpisodeSummaryReader` | `test_la_respuesta_y_los_errores_no_exponen_datos_de_pacientes` |
| Permiso y guard en el servidor | Autoridad y aislamiento | `routes/api.php` | `IndicatorEndpointTest` |
