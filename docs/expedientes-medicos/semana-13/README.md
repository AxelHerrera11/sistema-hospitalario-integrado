# Semana 13 — Calidad del software y revisiones técnicas formales

**Tarea:** preparar una revisión técnica formal enfocada en reglas de dominio, contrato API, permisos/tenant,
integridad del expediente longitudinal, episodios clínicos, acceso autorizado, datos sensibles y dependencias
estrictamente necesarias.

## Entregables

| Archivo | Contenido |
|---|---|
| [`plan-revision.md`](plan-revision.md) | Objetivo, material, roles (autor/moderador/revisores/registrador), criterios de entrada/salida y agenda |
| [`checklist-revision.md`](checklist-revision.md) | 15 ítems RT-01…RT-15 con dónde mirar |
| [`formato-hallazgos.md`](formato-hallazgos.md) | Campos, severidades, plantilla y reglas |
| [`01-proceso-revision.svg`](01-proceso-revision.svg) · [`fuentes/01-proceso-revision.mmd`](fuentes/01-proceso-revision.mmd) | Proceso de inspección |

## Cómo verificar los criterios de entrada

```bash
php micro-his/expediente-medico/tests/run.php
php vendor/bin/phpunit tests/Feature/ExpedienteMedico tests/Feature/Rbac/PublishedContractsTest.php
```

## Estado

Plan preparado. La reunión **no** se realizó: los revisores propuestos deben confirmarse con el grupo.
