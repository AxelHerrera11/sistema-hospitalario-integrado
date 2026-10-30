# ASII-23 Semana 14 — SQA del módulo de reportes

**Issue:** `Refs #152`. Aseguramiento de la calidad con política y decisiones
de auditoría aplicadas durante la construcción (no "QA al final").

## Política de alcance

| Objetivo | Decisión de auditoría |
|---|---|
| El código RAZONABLEmente claramente expresado | Las reglas viven en nombres de dominio; sin comentarios de ornamento; `php -l` limpio en cada commit. |
| "Hecho" (Definition of Done) | Zonas + casos límite probados ejecutando el escenario (no solo lint) + docs semana con evidencia. |
| Tamaño de los entregables | PR ≤ 400 líneas (objetivo 350) y ≥ 3 commits significativos por PR; promoción a `main` ≤ 400 tras cada entrega. |
| Evitar datos personales | Consultas agregadas y cuerpo de respuesta sin nombres ni IDs clínicos; test que lo demuestra. |
| Autoridad del servidor | RBAC/tenant obligatorios; el SPA nunca decide permisos. |

## Defectos y cómo se detectaron (rastreo)

| Defecto | Detección | Cierre |
|---|---|---|
| `Strict types` no primera línea (BOM de Escritura PowerShell) | Fatal PHP al ejecutar `run.php` | Corregido antes del PR de S3. |
| Expectativa de prueba con `open` en el conteo | Execute del test (33.33/25.00) | Dominio excluye `open`; pruebas ajustadas a fórmula. |
| TypeError en `IndicatorViolation` (code string) | Execute de escenarios 422 | Contrato usa mensaje como código. |
| GET rango POST-405 vista con fallback web | phpunit 405 | Prueba con DELETE; registro en S13. |
| Base caída → 500 en lugar de 503 | Test de base inexistente | Resolución perezosa dentro del `try` (#1269). |

## Reporte estado baцій y acciones de calidad

- Suite completa ejecutada localmente antes de integrar: **361 pruebas
  OK** (backendpoint + regresiones ajenas al módulo intactas).
- Cero reglas/rescisiones (findings) sin seguimiento; todo hallazgo tiene
  commit/PR o registro en S13/S14.

## Garantía comparativa de "no habrá regresión"

Los comandos de calidad quedan documentados y son reproducibles: si
qualquier cambio del módulo rompe el contrato, `run.php` y la suite de
Reportes fallan antes de un merge a `develop`.
