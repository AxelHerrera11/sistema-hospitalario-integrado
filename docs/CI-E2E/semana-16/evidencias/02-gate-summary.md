## Quality gate: ❌ RECHAZADO

Versión `0.0.0-local` · commit `6e841849f99a` · ambiente `local` · política `qg-0.1-propuesta`

| Suite | Tipo | Obligatoria | Total | Aprobadas | Fallidas | Omitidas | ms | Evidencias |
|---|---|---|---:|---:|---:|---:|---:|---:|
| sintaxis-php | syntax | sí | 1 | 1 | 0 | 0 | 83949 | 2 |
| pint-archivos-cambiados | lint | no | 1 | 0 | 1 | 0 | 2419 | 2 |
| phpunit-unit | unit | sí | 72 | 72 | 0 | 0 | 1513 | 1 |
| micro-his | unit | sí | 25 | 25 | 0 | 0 | 3789 | 1 |
| frontend-node | unit | sí | 47 | 47 | 0 | 0 | 1122 | 1 |
| phpunit-feature | integration | sí | 263 | 216 | 47 | 0 | 36142 | 1 |
| phpunit-integration | integration | sí | 4 | 4 | 0 | 0 | 369 | 1 |
| contrato-rutas-rbac | contract | sí | 51 | 51 | 0 | 0 | 0 | 2 |
| build-frontend | build | sí | 1 | 1 | 0 | 0 | 7348 | 2 |
| e2e-qa-calidad | e2e | sí | 9 | 9 | 0 | 0 | 594 | 1 |
| phpunit-database-pg | integration | no | — | — | — | — | — | — |
| e2e-auth-rbac | e2e | no | — | — | — | — | — | — |

### Hallazgos
- **warning** `SUITE_BELOW_THRESHOLD` (pint-archivos-cambiados): Aprobó 0.0 % (0 de 1 ejecutadas); mínimo 100.0 %.
- **rejection** `SUITE_BELOW_THRESHOLD` (phpunit-feature): Aprobó 82.1 % (216 de 263 ejecutadas); mínimo 100.0 %.
- **warning** `SUITE_MISSING` (phpunit-database-pg): No se registró resultado para la suite.
- **warning** `SUITE_MISSING` (e2e-auth-rbac): No se registró resultado para la suite.

### Notas de recolección
- phpunit-database-pg: no se encontró phpunit/database.xml (la etapa no corrió o no publicó su reporte).
- e2e-auth-rbac: no se encontró e2e/auth-rbac.xml (la etapa no corrió o no publicó su reporte).
