# Declaración de uso de IA — ASII-05 Contrato API de citas

**Tarea Odoo:** contrato API de citas (publicación previa al código, plan de integración 17–23 oct).
**Fecha:** 18/10/2026.

## Herramienta

- OpenAI Codex / opencode (agente `big-pickle`), asistente de desarrollo en el repositorio.

## Propósito

Redactar el contrato API del módulo de citas (endpoints, respuestas, errores y permisos) a partir de la migración real de `appointments`, el `routes/api.php`, el plugin de axios del frontend y el plan de integración del grupo.

## Prompts relevantes

1. «Analiza el README del proyecto, el cuadro de tareas semanales y el plan de integración; define qué debo hacer en mis módulos con PRs de ≤400 líneas por tarea de Odoo.»
2. «Redacta el contrato API de ASII-05 usando la migración real de `appointments` (exclusiones GiST, triggers de inmutabilidad y protección de borrado), con los cuatro permisos `citas.*` propuestos y el envoltorio de errores `{message, code, trace_id}`.»

## Contenido generado y validación

- **Generado por IA:** primer borrador de `contrato-api.md` y de este índice.
- **Modificado por la estudiante:** tabla de campos extraída y cotejada a mano contra `2026_08_11_000300_create_scheduling_and_bed_tables.php`, mapeo `SQLSTATE → HTTP` y decisión de `DELETE → 405`.
- **Verificación humana:** nombres de constraints (`appointments_overlap` propuesto sobre `23P01`, `appointments_specialty_trigger`, `appointments_finalized_immutable`, `appointments_delete_protection`) leídos del archivo de migración; rutas de `routes/api.php` y cabeceras de `resources/js/plugins/axios.js` verificadas en `origin/develop`. El contrato es propuesta pendiente de acuerdos con Yamilet/Javier (formato común) y María (permisos).
