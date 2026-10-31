# Semana 14 — Confiabilidad y aseguramiento de calidad

**Tarea:** definir el plan SQA: calidad del flujo, confiabilidad, seguridad, trazabilidad y riesgos; responsable,
umbral, fuente y acción para cada métrica y control.

## Entregables

| Archivo | Contenido |
|---|---|
| [`plan-sqa.md`](plan-sqa.md) | 7 métricas con valor actual, matriz de 8 riesgos, controles preventivos/detectivos, calendario |
| [`01-matriz-riesgos.svg`](01-matriz-riesgos.svg) · [`fuentes/01-matriz-riesgos.mmd`](fuentes/01-matriz-riesgos.mmd) | Riesgos R-01…R-08 en el plano probabilidad × impacto |
| [`evidencias/auditoria-prototipo.txt`](evidencias/auditoria-prototipo.txt) | Eventos reales que el prototipo dejó en `audit_log` (fuente de M-4) |

## Cómo verificar

```bash
php micro-his/expediente-medico/tests/run.php | tail -3
php vendor/bin/phpunit tests/Feature/ExpedienteMedico
php docs/expedientes-medicos/semana-05/herramientas/medir-consulta.php 500
```

## Estado

Plan definido con valores medidos hoy. Pendientes: validar en PostgreSQL (R-04) y la
política de inactividad (R-06).
