# Semana 14 — Confiabilidad y aseguramiento de calidad (plan SQA)

**Entregable:** plan SQA con 5–7 métricas, matriz de riesgos, controles preventivos/detectivos, responsables y calendario de seguimiento.

> Todos los **umbrales** son la propuesta `qg-0.1-propuesta` (`micro-his/qa-calidad-versiones/config/quality-gate.json`) y están **pendientes de aprobación del grupo**. Cambiar uno exige subir `version` en ese archivo.

## Métricas

| # | Métrica | Fuente | Umbral propuesto | Responsable | Acción si no se cumple |
|---|---|---|---|---|---|
| M1 | Tasa de aprobación de suites obligatorias (sobre ejecutadas) | `gate-decision.json` → `suites[].result` | 100 % en todos los tipos | Dueño del módulo que falla | PR rechazado (exit 1); corregir el mismo día |
| M2 | Suites obligatorias con reporte y evidencia | Hallazgos `SUITE_MISSING`, `SUITE_EMPTY`, `EVIDENCE_MISSING` | 0 por ejecución | Cindy (pipeline) | Gate bloqueado (exit 2); revisar la etapa que no publicó |
| M3 | Rutas API que cumplen el contrato tenant + JWT + RBAC del catálogo | `contract/route-guard.xml` | 100 % (hoy 17/17) | María (RBAC) y dueño de la ruta | Gate rechaza; agregar `rbac:` o pedir permiso a María |
| M4 | Pruebas omitidas en suites obligatorias | `SKIPPED_IN_REQUIRED` | 0 | Dueño del módulo | Rechazo; quitar el `skip` o mover la suite a opcional con aprobación |
| M5 | Duración del job E2E | `e2e/*.xml` (`duration_ms`) | ≤ 300 000 ms (aviso) | Cindy | Aviso `E2E_SLOW`; revisar arranque de servidores |
| M6 | Archivos PHP cambiados sin formato Pint | `lint/pint.xml` | 0 (informativo hoy) | Autor del PR | Aviso; volver obligatorio cuando el grupo apruebe |
| M7 | Decisiones del gate sin auditoría | Eventos `qa.gate.evaluado` vs. decisiones | 0 | Cindy (Gobernanza) | 503 y ejecución repetida |

## Matriz de riesgos

| ID | Riesgo | Prob. | Impacto | Nivel | Control preventivo | Control detectivo |
|---|---|---|---|---|---|---|
| R1 | Una versión se aprueba sin haber corrido la E2E | Media | Alto | **Alto** | Plan exige E2E obligatoria (U05) | `SUITE_MISSING`/`SUITE_EMPTY` bloquean (CI18) |
| R2 | E2E contra una base compartida o productiva | Baja | Muy alto | **Alto** | `production` no existe; guardas de `seed-e2e.php`; PostgreSQL efímero del job | exit 3 del sembrado; revisión del YAML (checklist S13 ítem 10) |
| R3 | Evidencia alterada o inventada | Baja | Alto | Medio | SHA-256 y tamaño calculados por el gate; resultados de solo inserción | `EvidenceRef::matches()`; P04 |
| R4 | El gate bloquea a todo el grupo el día de cierre por deuda ajena (Pint, 4 micro-HIS sin pruebas) | **Alta** | Alto | **Alto** | Pint y Database informativos; suites opcionales en el manifiesto | Notas del resumen; D-01 avisado a Josué y Gerson |
| R5 | Secretos en logs o artefactos | Baja | Alto | Medio | Secretos efímeros enmascarados; `permissions: contents: read` | Revisión de artefactos en S13 |
| R6 | RBAC o almacén caídos aprueban por error | Baja | Alto | Medio | Falla cerrado (BN22, BN23) | E2E-QA-07 |
| R7 | El E2E de pila completa falla por el ambiente (migraciones, puertos) y no por el código | Media | Medio | Medio | Suite opcional hasta su primera corrida verde | `laravel-server.log` como evidencia |

## Controles

| Tipo | Control | Dónde |
|---|---|---|
| Preventivo | Lockfiles (`composer install`, `npm ci`) | Todos los jobs |
| Preventivo | Validación del plan y de cada resultado en el dominio | `TestPlan`, `SuiteResult`, `EvidenceRef` |
| Preventivo | Permiso exigido en cada caso de uso | `Authorizer` |
| Preventivo | Archivos ≤ 400 líneas, capas separadas | `tests/architecture.php` |
| Detectivo | Quality gate con tres veredictos y hallazgos | job `quality-gate` |
| Detectivo | Contrato de rutas de todo el HIS | `route-guard.php` |
| Detectivo | Auditoría de cada acción y denegación | Gobernanza |
| Correctivo | Guía de recuperación y rollback | [guia-despliegue](../guia-despliegue/README.md) |

## Propuestas pendientes de aprobación

1. Volver **obligatorias** `pint-archivos-cambiados`, `phpunit-database-pg` y `e2e-auth-rbac` después de su primera ejecución verde en GitHub.
2. Agregar análisis estático (Larastan nivel 5 sobre `app/` como punto de partida) — requiere nueva dependencia de desarrollo; decisión del grupo.
3. Política para módulos sin `tests/run.php`: hoy rechaza; alternativa, aviso con fecha límite.

## Calendario de seguimiento

| Fecha | Actividad | Responsable |
|---|---|---|
| Jue 22-oct, mañana | Abrir PR del pipeline antes del corte de las 12:00; primera corrida en GitHub | Cindy |
| Jue 22-oct, tarde | E2E y CI sobre `develop` (plan de integración); revisar M1–M5; avisar defectos al dueño | Cindy (E2E/CI), Madelin (regresión manual) |
| Vie 23-oct | Solo correcciones; reevaluar el gate tras cada merge; revisión técnica formal (S13) | Cindy, Yamilet |
| Semanal, después de la entrega | Revisar umbrales con el grupo y subir la versión de la política | Grupo |
