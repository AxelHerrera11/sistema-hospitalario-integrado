# Declaración de uso de IA — ASII-24 OpenAPI base

**Tarea Odoo:** especificación OpenAPI base (publicación previa al código, plan de integración 17–23 oct).
**Fecha:** 18/10/2026.

## Herramienta

- OpenAI Codex / opencode (agente `big-pickle`), asistente de desarrollo en el repositorio.

## Propósito

Redactar la especificación OpenAPI base del API `/api/v1` (seguridad JWT + `X-Tenant-ID`, paths de autenticación reales, esquemas de usuario/sesión y envoltorio de errores) a partir de `routes/api.php`, `AuthController`, `TenantMiddleware`, `JwtAuth` y el plugin de axios del frontend.

## Prompts relevantes

1. «Analiza el README del proyecto, el cuadro de tareas semanales y el plan de integración; define qué debo hacer en mis módulos con PRs de ≤400 líneas por tarea de Odoo.»
2. «Redacta la especificación OpenAPI 3.0.3 base de ASII-24 con seguridad `bearerAuth` + `tenantHeader`, paths de auth tomados del código real y aplicación de ISP (paths de módulos aparte).»

## Contenido generado y validación

- **Generado por IA:** primer borrador de `openapi.yaml` y de este índice.
- **Modificado por la estudiante:** corrección de la seguridad (los `security` deben referenciar `securitySchemes`, no parámetros): se eliminó el parámetro suelto `TenantId` y se declaró el esquema `apiKey` `tenantHeader`.
- **Verificación humana:** rutas y respuestas cotejadas contra `routes/api.php` y `AuthController.php` en `origin/develop`; `roles`/`tenant` confirmados en la carga de `login`/`me`. Sintaxis validada con `npx js-yaml`. Pendiente: paths de citas/reportes tras la fusión de sus contratos.
