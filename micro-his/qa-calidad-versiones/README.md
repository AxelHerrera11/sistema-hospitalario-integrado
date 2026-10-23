# micro-his/qa-calidad-versiones

Núcleo del módulo **QA, pruebas E2E, CI y guía de despliegue final** (fila 25 del README; adaptación: gestión de ejecuciones automatizadas y calidad de versiones). PHP 8.2 vanilla, igual que `micro-his/gobernanza-auditoria`: autocarga propia en `bootstrap.php`, sin Composer ni framework. Documentación semanal: [`docs/CI-E2E`](../../docs/CI-E2E/README.md).

## Estructura

```
src/
  Domain/        TestPlan, PlannedSuite, TestRun, SuiteResult, EvidenceRef, RunStatus, TestEnvironment,
                 SuiteKind, QualityGate, QualityGatePolicy, GateDecision, GateFinding, GateVerdict,
                 TestRunRepository «i», InvalidQaInput, RunStateConflict, QaStorageFailure
  Application/   PlanTestRun, AdvanceTestRun, RecordSuiteResult, EvaluateQualityGate, ViewTestRuns,
                 Authorizer, RunInputParser, RunLoader, AccessGate «i», AuditTrail «i», Clock «i», Actor
  Persistence/   InMemoryTestRunRepository, PdoTestRunRepository, RunRowMapper, Uuid
  Presentation/  QaHttpHandler, QaController, RunPresenter, ErrorMapper, HttpResponse
  Integration/   GobernanzaAuditTrail, StaticAccessGate, SystemClock
  Ci/            GateRunner, JUnitReportReader, JUnitWriter, EvidenceCollector, RouteGuard,
                 GateReportWriter, ModuleFactory
  QaModule.php   raíz de composición
bin/             quality-gate.php, junit-command.php, micro-his-suites.php, route-guard.php, pipeline-local.sh
config/          quality-gate.json (política PROPUESTA), ci-manifest.json, route-guard.json
database/        qa_runs.sqlite.sql — esquema de PRUEBA (no es una migración del HIS)
public/          index.php (servidor de demo y E2E) + prototipo/
tests/           unit, whitebox, blackbox, application, pdo, ci, architecture, view-model.test.mjs, run.php
```

## Uso

```bash
php micro-his/qa-calidad-versiones/tests/run.php                       # 7 suites PHP
node --test micro-his/qa-calidad-versiones/tests/view-model.test.mjs   # lógica del prototipo
php -S 127.0.0.1:8093 -t micro-his/qa-calidad-versiones/public micro-his/qa-calidad-versiones/public/index.php
QA_E2E_BASE=http://127.0.0.1:8093 node --test tests/E2E/qa-calidad.e2e.mjs
bash micro-his/qa-calidad-versiones/bin/pipeline-local.sh qa-evidence  # etapas del CI sin PostgreSQL + gate
```

`bin/quality-gate.php` sale con 0 (aprobado), 1 (rechazado), 2 (bloqueado) o 3 (error de configuración). Lo usa el job `quality-gate` de [`.github/workflows/qa-ci-e2e.yml`](../../.github/workflows/qa-ci-e2e.yml).

## Límites conocidos

- Los permisos `qa.*` son una **propuesta** (no están en `config/rbac.php`); por eso la API no está montada en Laravel y la demo usa `StaticAccessGate`.
- El registro de ejecuciones se guarda en un SQLite por ejecución (artefacto de CI). Tablas en PostgreSQL solo con aprobación de Javier.
- El E2E de pila completa (`tests/E2E/auth-rbac.e2e.mjs`) solo corre en el job `e2e` de GitHub Actions.
