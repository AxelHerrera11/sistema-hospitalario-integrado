## Quality gate: ❌ RECHAZADO

Versión `0.0.0-local` · commit `5e6f0044beb0` · ambiente `local` · política `qg-0.1-propuesta`

| Suite | Tipo | Obligatoria | Total | Aprobadas | Fallidas | Omitidas | ms | Evidencias |
|---|---|---|---:|---:|---:|---:|---:|---:|
| sintaxis-php | syntax | sí | 1 | 1 | 0 | 0 | 62585 | 2 |
| pint-archivos-cambiados | lint | no | 1 | 1 | 0 | 0 | 1989 | 2 |
| phpunit-unit | unit | sí | 5 | 5 | 0 | 0 | 13 | 1 |
| micro-his | unit | sí | 14 | 10 | 0 | 4 | 3610 | 1 |
| frontend-node | unit | sí | 11 | 11 | 0 | 0 | 188 | 1 |
| phpunit-feature | integration | sí | 102 | 102 | 0 | 0 | 7868 | 1 |
| phpunit-integration | integration | sí | 4 | 4 | 0 | 0 | 406 | 1 |
| contrato-rutas-rbac | contract | sí | 17 | 17 | 0 | 0 | 0 | 2 |
| build-frontend | build | sí | 1 | 1 | 0 | 0 | 3435 | 2 |
| e2e-qa-calidad | e2e | sí | 9 | 9 | 0 | 0 | 733 | 1 |
| phpunit-database-pg | integration | no | — | — | — | — | — | — |
| e2e-auth-rbac | e2e | no | — | — | — | — | — | — |

### Hallazgos
- **rejection** `SKIPPED_IN_REQUIRED` (micro-his): 4 pruebas omitidas en una suite obligatoria.
- **warning** `SUITE_MISSING` (phpunit-database-pg): No se registró resultado para la suite.
- **warning** `SUITE_MISSING` (e2e-auth-rbac): No se registró resultado para la suite.

### Notas de recolección
- phpunit-database-pg: no se encontró phpunit/database.xml (la etapa no corrió o no publicó su reporte).
- e2e-auth-rbac: no se encontró e2e/auth-rbac.xml (la etapa no corrió o no publicó su reporte).
