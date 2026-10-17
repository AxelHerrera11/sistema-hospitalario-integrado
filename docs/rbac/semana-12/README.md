# Semana 12 — Segunda evaluación parcial (RBAC)

## Objetivo

Defender las decisiones de componentes, UX, accesibilidad y movilidad de RBAC (roles, permisos y protección de rutas). Demostrar con el prototipo navegable cómo el flujo «asignación de rol, autorización de una operación y denegación trazable» trata roles, estados, errores y riesgos asociados con roles, permisos, rutas protegidas, decisiones de autorización y denegaciones auditables.

**Asignación:** Semana 12
**Estudiante:** LÓPEZ FAJARDO, MARÍA DE LOS ANGELES (Mar-03)
**Rama:** `feature/asii-02-semana-12-rbac-mar-03`
**Base PR:** `develop` (HEAD `4aafa77`)
**Refs:** #4

## Alcance

Este documento describe el trabajo de la Semana 12, basado en la auditoría de las semanas 1–11. Se limita a **documentación, análisis y planificación** (no hay cambios de código ni implementación del cambio solicitado).

## Archivos de la semana 12

| Archivo | Descripción |
|---|---|
| `presentacion.md` | Presentación breve (5–7 diapositivas). |
| `recorrido-navegable.md` | Recorrido paso a paso basado exclusivamente en el prototipo funcional existente. |
| `matriz-decision-rf-rnf.md` | Matriz decisión → justificación → RF/RNF → riesgo → evidencia → estado. |
| `cambio-solicitado.md` | Análisis del cambio solicitado (confirmación adicional para roles privilegiados). Solo análisis, no implementación. |
| `evidencias/README.md` | Ubicación real de las evidencias (reutilizadas de Semana 11). |

## Prototipo de referencia

El prototipo navegable de **Semana 11** (`docs/rbac/semana-11/`) es la fuente de demostración utilizada:

- `docs/rbac/semana-11/prototipo.html` (209 líneas)
- `docs/rbac/semana-11/app.js` (346 líneas)
- `docs/rbac/semana-11/styles.css` (225 líneas)
- `docs/rbac/semana-11/escenarios.md`, `mapa-navegacion.mmd`, `README.md`, `evidencias/`

**Validación del prototipo (Semana 11):** 20 comprobaciones funcionales instrumentadas **PASS**, sin errores. Mejoras de accesibilidad aplicadas y verificadas.

## Distinción entre implementado, simulación y producción

- **Implementado (funcional):** Flujo completo, validaciones (permisos, duplicidad, anti-escalamiento), estados, ocultamiento según permisos, trazas `trace-`+12 hex, navegación por hash, responsive, anuncios `aria-live`, regiones accesibles.
- **Simulación (explícita):** Sin backend/HTTP, datos ficticios, memoria volátil, fechas locales, no persiste, no autentica, no valida tenant real. Documentado en Semana 11.
- **Producción (no implementado):** Autorización server-side obligatoria, persistencia, auditoría real, idempotencia, rate limiting, integración con Laravel/micro-HIS. **Nunca** se sustituye autorización visual por autorización backend.

## Fuentes consultadas (solo lectura)

- Semana 3: `micro-his/rbac/src/Application/AssignRole.php:25`, `AssignerPrivilegeRule.php` (niveles, `rol_supera_privilegio`), `tests/run.php:19,41`
- Semana 5: `docs/rbac/semana-05/permisos.md:10,47`, `contrato-api.md:19`
- Semana 10: `docs/rbac/semana-10/breakpoints.md:16`, diseño móvil
- Semana 11: todos los archivos (prototipo + docs + evidencias)

## Limitaciones

Este entregable es **solo documentación y análisis**. No se modifica código, no se implementa el cambio solicitado y no se asume integración de los PRs de Semana 11 en `develop`. Las evidencias citadas existen en las ramas/worktrees de Semana 11; su ubicación se documenta en `evidencias/README.md`.
