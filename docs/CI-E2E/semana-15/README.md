# Semana 15 — Pruebas unitarias, caja blanca y caja negra

**Entregable:** matriz RF/RNF→prueba, mínimo 3 unitarias, 2 de caja blanca y 4 de caja negra, comandos/resultados, capturas y defectos registrados. Solo se prueba este módulo; los módulos ajenos se usan por su contrato público (C-01..C-05) sin probar su lógica interna.

## Resumen de la ejecución (21-oct-2026, Windows 11, PHP 8.2.12, Node 22.13.1, base `origin/develop` 5e6f0044)

| Suite | Tipo | Casos | Resultado | Evidencia |
|---|---|---:|---|---|
| `tests/unit.php` | Unitarias | 31 | 0 fallos | [junit/unit.xml](evidencias/junit/unit.xml) |
| `tests/whitebox.php` | Caja blanca | 18 | 0 fallos | [junit/whitebox.xml](evidencias/junit/whitebox.xml) |
| `tests/blackbox.php` | Caja negra (HTTP) | 23 | 0 fallos | [junit/blackbox.xml](evidencias/junit/blackbox.xml) |
| `tests/application.php` | Casos de uso + Gobernanza | 16 | 0 fallos | [junit/application.xml](evidencias/junit/application.xml) |
| `tests/pdo.php` | Persistencia PDO/SQLite | 15 | 0 fallos | [junit/pdo.xml](evidencias/junit/pdo.xml) |
| `tests/ci.php` | JUnit, evidencia, rutas, CLI | 21 | 0 fallos | [junit/ci.xml](evidencias/junit/ci.xml) |
| `tests/architecture.php` | Capas y tamaño | 8 | 0 fallos | [junit/architecture.xml](evidencias/junit/architecture.xml) |
| `tests/view-model.test.mjs` | Unitarias del prototipo | 7 | 0 fallos | [junit/view-model.xml](evidencias/junit/view-model.xml) |
| `tests/E2E/qa-calidad.e2e.mjs` | E2E HTTP aislado | 9 | 0 fallos | [junit/e2e-qa-calidad.xml](evidencias/junit/e2e-qa-calidad.xml) |
| `tests/E2E/auth-rbac.e2e.mjs` | E2E pila completa | 8 | **8 skipped** (sin PostgreSQL de prueba aquí) | [junit/e2e-auth-rbac-sin-ambiente.xml](evidencias/junit/e2e-auth-rbac-sin-ambiente.xml) |

Total ejecutado y aprobado: **148** casos. El E2E de pila completa **no se ejecutó**: necesita el job `e2e` de GitHub Actions.

## Comandos

```bash
QA_JUNIT_DIR=docs/CI-E2E/semana-15/evidencias/junit php micro-his/qa-calidad-versiones/tests/run.php
node --test micro-his/qa-calidad-versiones/tests/view-model.test.mjs
php -S 127.0.0.1:8093 -t micro-his/qa-calidad-versiones/public micro-his/qa-calidad-versiones/public/index.php
QA_E2E_BASE=http://127.0.0.1:8093 node --test tests/E2E/qa-calidad.e2e.mjs
bash micro-his/qa-calidad-versiones/bin/pipeline-local.sh qa-evidence
```

Salidas: [01-micro-his-run.txt](evidencias/01-micro-his-run.txt) · [02-view-model.txt](evidencias/02-view-model.txt) · [03-e2e-qa-calidad.txt](evidencias/03-e2e-qa-calidad.txt) · [04-e2e-auth-rbac-sin-ambiente.txt](evidencias/04-e2e-auth-rbac-sin-ambiente.txt) · [05-pipeline-local.txt](evidencias/05-pipeline-local.txt) · [06-gate-summary.md](evidencias/06-gate-summary.md) · [07-gate-decision.json](evidencias/07-gate-decision.json). Rutas locales reemplazadas por `/repo` o `qa-evidence`.

## Casos seleccionados para la semana

| Técnica | ID | Qué prueba | Requisito |
|---|---|---|---|
| Unitaria | U05 | Plan sin E2E obligatoria → `plan_incompleto` | RF-01 |
| Unitaria | U09 | Un byte alterado invalida la evidencia | RF-04, RNF-03 |
| Unitaria | U19 | Segundo resultado de la misma suite → 409 y el primero no cambia | RF-03, RNF-03 |
| Caja blanca | W05/W06 | Rama `executed() === 0` por total 0 y por todo omitido → `SUITE_EMPTY` | RF-05 |
| Caja blanca | W09/W10 | Condición `rate < min`: 0.95 < 1.0 rechaza; 0.95 = 0.95 tolera (valor límite exacto) | RF-05 |
| Caja blanca | W16 | Precedencia de `verdictFor`: blocker gana a rejection | RF-05 |
| Caja negra — equivalencia | BN06–BN10 | Partición válida completa (planificar → resultados → gate aprobado) | RF-01..RF-06 |
| Caja negra — límites | BN18–BN20, BN16 | 30 suites OK, 31 y 0 → 422; cuerpo 64 KiB + 1 → 413 | RF-01 |
| Caja negra — permisos | BN01–BN05, BN11 | 401, 400, 403 tenant, 403 RBAC, 404 de otro tenant | RNF-01, RF-06 |
| Caja negra — errores | BN12–BN15, BN22, BN23 | 409, 422 (`production`, `required:"false"`), JSON inválido, 503 RBAC y almacén | RNF-01, RNF-02 |

## Matriz RF/RNF → prueba

| Requisito | Unitarias | Caja blanca | Caja negra / E2E | Otras |
|---|---|---|---|---|
| RF-01 Planificar | U01–U07 | — | BN06, BN13, BN14, BN18–BN20 | VM01, VM02 |
| RF-02 Ciclo de vida | U17, U20–U24 | W01, W02 | BN12, E2E-QA-08 | A02 |
| RF-03 Resultado | U13–U19 | — | BN08 | P04 |
| RF-04 Evidencia | U08–U12 | W07, W08 | — | CI07–CI09, CI21, P02, VM03 |
| RF-05 Gate | U25–U28 | W01–W18 | BN09, E2E-QA-01..03 | A01, A04, CI14–CI18 |
| RF-06 Consulta por tenant | — | — | BN10, BN11, BN21, E2E-QA-05 | A08, P03, P06 |
| RF-07 Pipeline CI | — | — | — | CI14–CI21, `pipeline-local.sh` |
| RF-08 Contrato de rutas | — | — | — | CI10–CI13; 17/17 rutas de develop |
| RNF-01 Seguridad | — | — | BN01–BN05, BN22, E2E-QA-04, E2E-QA-07 | A05–A07 |
| RNF-02 Aislamiento | U06 | — | BN13, E2E-QA-06 | CI20, guardas del sembrado |
| RNF-03 Integridad | U09, U19 | — | BN17 | P04, P05, P14 |
| RNF-04 Auditoría | — | — | — | A02, A03, A06, A13–A16 |
| RNF-06 Mantenibilidad | — | — | — | AR01–AR08 |
| RNF-08 Accesibilidad | — | — | — | Mediciones de la [semana 9](../semana-09/evidencias/mediciones.md) |

## Capturas

Camino feliz y error crítico en [semana-11/capturas](../semana-11/capturas/) y móvil en [semana-10/capturas](../semana-10/capturas/).

## Defectos registrados

| ID | Defecto | Módulo / dueño | Severidad | Estado |
|---|---|---|---|---|
| D-01 | 4 de 14 módulos micro-HIS sin `tests/run.php` (`signos-vitales`; `ingreso-resultados-lab`, `validacion-resultados-bioquimico`, `nativephp-extension-prototype`); el gate local quedó **RECHAZADO** por `SKIPPED_IN_REQUIRED` | Signos vitales — Josué; resultados, validación y NativePHP — Gerson | Mayor para la integración | Abierto, comentario preparado en [pendientes](../pendientes-y-bloqueos.md#d-01) |
| D-02 | Gate terminaba con exit 3 si un log adicional estaba vacío | QA/CI — Cindy | Mayor | Corregido (CI21, [S6](../semana-06/cambio-practico.md)) |
| D-03 | Prototipo: doble evaluación, `bytes` inventado, aviso siempre visible, contraste de bordes, foco | QA/CI — Cindy | Mayor/menor | Corregidos H-01..H-08 ([S9](../semana-09/README.md)) |
| D-04 | Formatos de error distintos entre Tenants (`{error,message}`) y RBAC (`{code,trace_id}`) | Contratos — Lis / Tenants — Cindy | Menor | Abierto (acuerdo del grupo) |
| D-05 | 212 archivos PHP de `develop` no pasan `pint --test` | Varios | Observación | Abierto; Pint queda informativo y solo sobre archivos cambiados |
