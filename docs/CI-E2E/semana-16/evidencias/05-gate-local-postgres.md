# Quality gate — pipeline local con PostgreSQL en 55431/55433 (2026-10-31, develop 37c48d80)

## Quality gate: ❌ RECHAZADO

Versión `0.0.0-local` · commit `37c48d805431` · ambiente `local` · política `qg-0.1-propuesta`

| Suite | Tipo | Obligatoria | Total | Aprobadas | Fallidas | Omitidas | ms | Evidencias |
|---|---|---|---:|---:|---:|---:|---:|---:|
| sintaxis-php | syntax | sí | 1 | 1 | 0 | 0 | 181367 | 2 |
| phpunit-unit | unit | sí | 72 | 72 | 0 | 0 | 3758 | 1 |
| micro-his | unit | sí | 27 | 27 | 0 | 0 | 8132 | 1 |
| frontend-node | unit | sí | 56 | 56 | 0 | 0 | 3272 | 1 |
| phpunit-feature | integration | sí | 352 | 352 | 0 | 0 | 93652 | 1 |
| phpunit-integration | integration | sí | 4 | 4 | 0 | 0 | 798 | 1 |
| contrato-rutas-rbac | contract | sí | 82 | 81 | 1 | 0 | 0 | 2 |
| build-frontend | build | sí | 1 | 1 | 0 | 0 | 11922 | 2 |
| e2e-qa-calidad | e2e | sí | 9 | 9 | 0 | 0 | 1174 | 1 |
| phpunit-database-pg | integration | no | 224 | 224 | 0 | 0 | 301527 | 1 |
| e2e-auth-rbac | e2e | no | 8 | 8 | 0 | 0 | 5895 | 2 |

### Hallazgos
- **rejection** `SUITE_BELOW_THRESHOLD` (contrato-rutas-rbac): Aprobó 98.8 % (81 de 82 ejecutadas); mínimo 100.0 %.
