# Declaración de uso de IA — ASII-26 Contrato UI base

**Tarea Odoo:** contrato de UI base (publicación previa al código, plan de integración 17–23 oct).
**Fecha:** 18/10/2026.

## Herramienta

- OpenAI Codex / opencode (agente `big-pickle`), asistente de desarrollo en el repositorio.

## Propósito

Redactar el contrato de la base UI transversal (layout, menú por rol, componentes reutilizables, estados de página y manejo de errores 401/403) a partir del README del proyecto, el plan de integración del grupo y el código existente de `resources/js`.

## Prompts relevantes

1. «Analiza el README del proyecto, el cuadro de tareas semanales y el plan de integración; define qué debo hacer en mis módulos (reportes exportables, citas, dashboard base, contratos API/docs) con PRs de ≤400 líneas por tarea de Odoo.»
2. «Redacta el contrato UI base de ASII-26: layout, menú filtrado por permisos `modulo.accion`, router/guards, componentes de estados, interceptor de errores, con fuente en el código real de `AppLayout.vue`, `guards.js`, `stores/auth.js` y `plugins/axios.js`.»

## Contenido generado y validación

- **Generado por IA:** primer borrador de `contrato-ui-base.md` y de este índice.
- **Modificado por la estudiante:** tabla de accesos por rol, permisos del §9 (`citas.*`, `reportes.*`, `documentacion.ver`) propuestos al catálogo de María, y la sección de pendientes frente a Cindy/María.
- **Verificación humana:** nombres de archivos, rutas y campos citados (`auth_token`, `auth_user`, `meta.requiresAuth`) cotejados contra el código de `resources/js` en `origin/develop`. El contrato es una propuesta pendiente de validar con las estudiantes de auth y RBAC; ninguna política visual se declara oficial.
