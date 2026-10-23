# Propuesta de esquema para Javier (NO implementada)

> **Estado:** propuesta pendiente de validación. No se creó ninguna migración ni se tocó CENTRAL u hospital. El módulo funciona hoy sin estas tablas (opción A: SQLite de la ejecución subido como artefacto). Solo hacen falta si el grupo quiere consultar el historial de ejecuciones desde el HIS.

## Qué se guardaría

| Tabla propuesta | Datos | Módulos que la usarían |
|---|---|---|
| `qa_test_runs` | id, hospital de pruebas (tenant), versión SemVer, commit SHA, ambiente (`local`/`ci`/`staging`), estado, plan (JSON de suites), quién planificó, fechas, motivo de aborto | QA/CI (escritura); dashboard o reportes (lectura, si se pide) |
| `qa_suite_results` | ejecución, suite, total, fallidas, omitidas, duración, evidencias (ruta + SHA-256 + bytes) | QA/CI |
| `qa_gate_decisions` | ejecución, tenant, veredicto, versión de política, hallazgos (JSON), quién evaluó, cuándo | QA/CI; Gobernanza (referencia cruzada por `resource_id`) |

No contienen datos clínicos ni personales de pacientes. Solo identificadores de usuario del HIS (quién planificó/evaluó).

## Relaciones e índices propuestos

- `qa_suite_results.run_id` → `qa_test_runs.run_id` (PK compuesta `run_id, suite`; inserción única, sin `UPDATE`).
- `qa_gate_decisions.run_id` → `qa_test_runs.run_id` (solo inserción; la última por `decision_id`).
- `qa_test_runs.tenant_id` → `hospitals.hospital_id` si se ubica en CENTRAL.
- Índice `(tenant_id, created_at)` para el listado por hospital.
- `CHECK` de estados, ambientes y veredictos (igual que el esquema de prueba [`qa_runs.sqlite.sql`](../../../micro-his/qa-calidad-versiones/database/qa_runs.sqlite.sql)).

## Comentario listo para enviar a Javier

> Hola Javier. Para el módulo de QA/CI (fila 25) **no necesito** tablas nuevas para entregar: el registro de cada ejecución se guarda en un SQLite propio de la ejecución de CI y se sube como artefacto de GitHub. Te dejo la propuesta por si el grupo decide consultar el historial desde el HIS: tres tablas `qa_test_runs`, `qa_suite_results` y `qa_gate_decisions` (detalle en `docs/CI-E2E/semana-04/propuesta-bd-javier.md`), sin datos clínicos, de solo inserción, y con FK a `hospitals` si van en CENTRAL. ¿Te parece que irían en CENTRAL o preferís que se queden fuera del HIS? No voy a crear ninguna migración sin tu aprobación. Además, el workflow nuevo levanta PostgreSQL 16 efímero y corre `composer migrate:central/framework/hospital` y la suite `Database` en bases `*_test`/`*_e2e` del propio job; si alguna migración necesita otro orden o extensión, avisame. — Cindy
