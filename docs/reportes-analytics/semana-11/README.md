# ASII-23 Semana 11 — Prototipo navegable (parcial 2 en marcha)

**Issue:** `Refs #152`. El "prototipo" de esta cohorte es el propio módulo
integrado: no se entregan maquetas sueltas sino la pantalla real con datos
reales de la base HOSPITAL.

## Checklist del prototipo (todo integrado en develop/main)

| Pasos del prototipo | Estado | Evidencia |
|---|---|---|
| Regla del indicador (Semana 1) | INTEGRADO | VCs y fórmula en `docs/reportes-analytics/semana-01` (SVG+PlantUML). |
| Ejemplo LSP ejecutable (Semana 2) | INTEGRADO | `tests/example-lsp.php` — 7 comprobaciones. |
| Micro-HIS 4 capas (Semana 3) | INTEGRADO | `micro-his/reportes-analytics/**` — `run.php` con 11 escenarios OK. |
| MVC/Repository (Semana 4) | INTEGRADO | Mapeo por archivo en `docs/reportes-analytics/semana-04`. |
| API REST (Semana 5) | INTEGRADO | `POST /api/v1/reports/indicators/IND-RA-01/calculate`. |
| Pantalla "Reportes" (Semanas 8–10) | INTEGRADO | Ruta `/reportes` + navegación con `reportes.ver`. |
| Datos reales (Semana 16) | INTEGRADO | `IndicatorHospitalIntegrationTest` contra `his_hospital_test`. |

## Cómo ensayarlo en 5 pasos (con local PostgreSQL)

1. `php artisan serve` y `npm run dev` en dos terminales del repo.
2. Login con el usuario ficticio creado con
   `php artisan auth:provision-first-superadmin` (rol con `reportes.ver`).
3. Menú "Reportes" → elegir `Desde 2026-01-01`, `Hasta 2026-02-01`.
4. "Calcular" → porcentaje con dos decimales y trazabilidad.
5. Repetir con `Hasta 2026-01-01` → mensaje "Revisa el periodo indicado."

Los datos del demo se siembran con el bloque SQL del manual del módulo
(`docs/reportes-analytics/README.md`), que inserta un expediente sintético
evidente y tres episodios en la base HOSPITAL del entorno local.
