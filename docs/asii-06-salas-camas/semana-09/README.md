# ASII-06 — Semana 9: evaluación de usabilidad y accesibilidad

## Objetivo académico identificado

El issue #11 define el objetivo y Definition of Done generales del módulo,
pero no detalla las semanas. La plantilla oficial
`.github/ISSUE_TEMPLATE/module_task.md` asigna a Semana 9
**“usabilidad/accesibilidad”**. No se encontró un brief ASII-06 más específico.
Por ello, esta entrega evalúa el flujo UX documentado en Semana 8 y registra
hallazgos y mejoras; no adelanta la construcción de la UI, asociada a semanas
posteriores del curso.

## Alcance

La revisión es estática sobre el flujo UX de ASII-06 de Semana 8, sus
respuestas API documentadas y el frontend real de Pacientes/HIS como patrón
de integración. Evalúa la consulta de wards y camas, los permisos, los estados
de respuesta y los requisitos de accesibilidad que se pueden comprobar en el
diseño.

La aplicación ya tiene frontend Vue 3, Vue Router, Pinia y Axios, pero ASII-06
aún no tiene página, ruta ni entrada de navegación. No se agregan componentes,
API, permisos, migraciones ni cambios de código. Por tanto, los criterios que
dependen de una pantalla renderizada quedan como **no verificables**; no se
afirma conformidad WCAG ni se sustituyen pruebas con usuarios.

## Entregables

- [Evaluación y checklist](./evaluacion-usabilidad-accesibilidad.md): estado
  de heurísticas de usabilidad y criterios de accesibilidad aplicables.
- [Hallazgos](./hallazgos.md): problemas, evidencia y recomendaciones.
- [Backlog priorizado](./backlog-priorizado.md): acciones previas a prototipo/UI.
- [Evidencia de integración y pruebas](./evidencia-integracion.md): sistema
  revisado, comandos y resultados.

## Relación con las semanas previas

Semana 8 definió el flujo de consulta, incluyendo estados vacíos, errores,
permisos y anuncios accesibles como intención. Semana 9 verifica qué decisiones
están especificadas y cuáles requieren definición visual o implementación.
Los endpoints existentes siguen siendo `GET /api/v1/wards` y
`GET /api/v1/wards/{ward}/beds`; no se amplía su comportamiento.

## Limitaciones

La conexión `hospital` permanece fija. `TenantMiddleware` valida el hospital,
pero no existe selección dinámica de base por tenant; las tablas `wards` y
`beds` no tienen `tenant_id`. No se afirma aislamiento multi-tenant completo.
El flujo se apoya en la base de Semana 8 y en las rutas de Semanas 6–7,
pendientes de sus PR correspondientes.
