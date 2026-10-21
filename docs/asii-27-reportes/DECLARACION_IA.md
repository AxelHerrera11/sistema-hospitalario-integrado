# Declaración de uso de IA — ASII-27 Contrato de filtros y exportación

**Tarea Odoo:** contrato de filtros y exportación (publicación previa al código, plan de integración 17–23 oct).
**Fecha:** 18/10/2026.

## Herramienta

- OpenAI Codex / opencode (agente `big-pickle`), asistente de desarrollo en el repositorio.

## Propósito

Redactar el contrato de la capa de filtros y exportación del módulo de reportes operativos (endpoints, filtros comunes, formato CSV, arquitectura OCP de definiciones/exportadores) a partir del README, el cuadro de tareas, el plan de integración y el `composer.json`/esquema `hospital` reales.

## Prompts relevantes

1. «Analiza el README del proyecto, el cuadro de tareas semanales y el plan de integración; define qué debo hacer en mis módulos con PRs de ≤400 líneas por tarea de Odoo.»
2. «Redacta el contrato de filtros y exportación de ASII-27 con OCP: `ReportDefinition`/`ReportExporter`, CSV UTF-8 reproducible, PDF condicionado a aprobación de dependencia, sin tablas nuevas, citando fuentes reales del esquema `hospital`.»

## Contenido generado y validación

- **Generado por IA:** primer borrador de `contrato-filtros-export.md` y de este índice.
- **Modificado por la estudiante:** verificación de que `composer.json` no contiene librería de PDF (la condición de `format=pdf`), límite de 366 días del rango de fechas y diferenciación frente a ASII-23.
- **Verificación humana:** tablas citadas (`appointments`, `wards`, `beds`, `bed_stays`, `care_episodes`, `local_patients`) cotejadas contra las migraciones de `database/migrations/hospital` en `origin/develop`; permisos `reportes.ver`/`reportes.exportar` coherentes con el contrato UI base de ASII-26. Pendiente de los acuerdos del grupo (formato común, PDF, middleware de permisos).
