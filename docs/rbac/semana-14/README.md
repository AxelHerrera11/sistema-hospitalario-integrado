# Semana 14 — Plan de aseguramiento de calidad (SQA) del módulo RBAC

**Asignación:** Semana 14 — Plan de aseguramiento de calidad. **Módulo:** ASII-02 — RBAC: roles, permisos y protección de rutas. **Estudiante:** López Fajardo, María de los Ángeles (`Mar-03`).
**Rama:** `feature/asii-02-semana-14-rbac-mar-03`. **Base:** `origin/develop` (HEAD `255472f`). **Refs:** #4.

## 1. Objetivo

Definir cómo se asegura la calidad del módulo RBAC antes de su integración y entrega: alcance, métricas medibles, riesgos, estrategia de pruebas y regresión, criterios de aceptación con *quality gates* y gestión de defectos. El plan aplica al flujo «asignación de rol, autorización de una operación y denegación trazable».

Este documento es **planificación**. No reporta cobertura, campañas, despliegues ni métricas nuevas: cada dato observado se marca como tal y con su fuente.

## 2. Alcance

**Dentro del alcance:** el motor RBAC (`micro-his/rbac/`), el middleware de autorización (`app/Http/Middleware/EnsurePermission.php`, rama Semana 7; PR #136, pendiente de merge), los helpers de permisos del frontend (`resources/js/router/permissions.js`, PR #136, pendiente de merge), el catálogo de permisos (`docs/rbac/semana-05/permisos.md`, PR #134, pendiente de merge) y las cinco dimensiones exigidas: **permisos, autenticación, tenant, trazabilidad y fallos de persistencia**.

**Fuera del alcance:** autenticación JWT y gestión de usuarios/tenants (ASII-01), bitácora central real (Gobernanza), endpoints clínicos protegidos, infraestructura de producción, pruebas de carga/estrés y accesibilidad con lector de pantalla (solo se auditaron contrastes y teclado en Semana 11).

## 3. Referencias normativas y del repositorio

- ISO/IEC 25010 (características de calidad) e IEEE 730 (SQA) como marco conceptual.
- Plan semanal: `README.md` (fila Semana 14) y `.github/ISSUE_TEMPLATE/module_task.md` (Semana 14: plan SQA).
- Requisitos y criterios: `docs/rbac/semana-02/requerimientos.md` (`RF-RBAC-01..08`, `RNF-RBAC-01..04`).
- Catálogo de permisos y matriz rol×permiso: `docs/rbac/semana-05/permisos.md` (PR #134, pendiente de merge).
- Prototipo y escenarios verificados: `docs/rbac/semana-11/escenarios.md`.
- Plantilla de PR y *checklist* obligatorio: `.github/pull_request_template.md`.

## 4. Entregables de esta semana

| Archivo | Contenido |
|---|---|
| `README.md` | Plan SQA, alcance, objetivos vs. resultados observados. |
| `metricas-calidad.md` | Métricas medibles y cómo obtenerlas. |
| `matriz-riesgos.md` | Matriz de riesgos de RBAC. |
| `estrategia-pruebas-regresion.md` | Estrategia de pruebas y regresión. |
| `criterios-aceptacion-quality-gates.md` | Criterios de aceptación, *quality gates* y responsables. |
| `gestion-defectos.md` | Procedimiento de defectos y verificación de correcciones. |

## 5. Objetivos vs. resultados observados

| Elemento | Estado | Fuente |
|---|---|---|
| Suite del micro-HIS `micro-his/rbac/tests/run.php` | **Observado:** 9/9 `OK` y `Todas las pruebas pasaron.` | Ejecución local en este worktree (`255472f`, 2026-10-18). Reproducible con `php micro-his/rbac/tests/run.php`. |
| Prototipo navegable (20 comprobaciones funcionales) | **Observado en Semana 11**, no re-ejecutado en esta semana | `docs/rbac/semana-11/escenarios.md` |
| Cobertura de líneas/ramas del motor | **Objetivo, no medido** | Requiere Xdebug/PCOV; ver `metricas-calidad.md` |
| Pruebas de integración con PostgreSQL federado | **Objetivo, no ejecutado** | Planificado en `estrategia-pruebas-regresion.md` |
| *Quality gates* en CI | **Objetivo, no verificado** | `.github/workflows/` existente; alcance en el gate G1 |

## 6. Responsabilidades (resumen)

El responsable del módulo (`Mar-03`) define y ejecuta el plan; el revisor designado en `.github/CODEOWNERS` (`@rortizs`, `@Yamilet1235`) valida evidencias y aprueba el PR. El detalle por *gate* está en `criterios-aceptacion-quality-gates.md`.
