# ASII-28 - QA Funcional - Semana 5 - Cliente-servidor, API REST e integración

Madelin Jazmín Cerón Molina (`MadelinCeron`). Refs #12.
Rama: `feature/asii-28-semana-05-qa-regresion-madelinceron`; base `develop`.
HEAD inicial: `52097752`; árbol limpio y HEAD igual a origin/develop tras fetch
(0/0). Upstream inicial origin/develop: sin pull a ciegas ni worktrees.

## Objetivo y estado verificable

Entregable oficial: contrato API preliminar y plan de integración. Se diseña
la frontera del Micro-HIS existente, sin CRUD ni servidor nuevo en esta entrega.
GitHub confirmó #1367 y #1368 fusionados; git ls-tree confirmó sus archivos
en origin/develop. Se revisaron las narrativas de Semana 1, RF/RNF y LSP de
Semana 2, arquitectura/PlantUML de Semana 3 y código/contratos de Semana 4.

Implementado: `micro-his/qa-regresion/`, PHP vanilla; CaseController → DesignCase
→ ManualCase/CaseRepository → InMemoryCaseRepository o PdoCaseRepository.
CaseView produce JSON; demo.php recibe STDIN y usa SQLite en memoria.
Solo RF-01 tiene operación vertical. Los status 201/400/422/503 son datos del
resultado CLI, no respuestas de rutas HTTP. La demo pierde sus datos al finalizar.

Propuesto: POST `/api/v1/qa/manual-cases` y GET `/api/v1/qa/manual-cases/{id}`.
**Endpoints QA efectivamente implementados: ninguno.** CaseRepository::find
existe, pero falta caso de uso/controlador de consulta y adaptación HTTP.
Campañas, ejecuciones, resultados, evidencias, defectos y trazabilidad siguen pendientes.

- [Arquitectura cliente-servidor](./arquitectura-cliente-servidor.md).
- [Contrato preliminar](./contrato-api.md): rutas, ejemplos y errores propuestos.
- [Plan de integración](./plan-integracion.md): responsables, dependencias y pruebas.
- Fuentes: [requisitos](../semana-02/rf-rnf-criterios.md),
  [LSP](../semana-02/solid-lsp.md), [capas](../semana-04/arquitectura-capas.md)
  y [Repository](../semana-04/contratos-repositorio.md).

## Validaciones nuevas — 29/10/2026

| Comando / comprobación | Resultado observado |
|---|---|
| `git status --short --branch`; `git branch --show-current`; `git log -1 --oneline`; `git fetch origin` | Rama correcta, base 52097752 y árbol inicialmente limpio. |
| `git ls-tree -r --name-only origin/develop docs/asii-28-qa-regresion micro-his/qa-regresion` | Semanas 1–4 y núcleo presentes, no solo ramas de promoción. |
| `php micro-his/qa-regresion/tests/run.php` | 17 aprobadas, 0 fallidas; pruebas existentes ejecutadas nuevamente, no pruebas nuevas. |
| `php artisan route:list --path=api` | 47 rutas, ninguna QA manual; no prueba endpoints propuestos. |
| Lectura de CaseController, DesignCase, ManualCase, repositorios y bootstrap/app.php | Formatos/errores CLI y fronteras reales contrastados; no se atribuye seguridad compartida al Micro-HIS. |
| Enlaces locales, JSON contractuales y matriz de requisitos | Comprobados antes de publicar; ejemplos estáticos, no respuestas HTTP obtenidas. |
| `git diff --check`; `git diff --cached --check` | Sin errores antes del commit. |

Sin código funcional nuevo ni pruebas HTTP nuevas. No se tocó PostgreSQL hospitalario,
.env, CI ni semanas anteriores. Riesgos: no hay propiedad tenant en el esquema actual,
permisos QA registrados, almacenamiento durable, API ni UI. Antes de exponer rutas,
resolver aislamiento y seguridad. Semana 6 debe implementar/testear el primer adaptador
HTTP tras esas decisiones; el plan identifica condiciones y responsables pendientes.
