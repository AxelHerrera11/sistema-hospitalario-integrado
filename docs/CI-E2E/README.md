# QA, pruebas E2E, CI y guía de despliegue final — Cindy Ruano

**Fila 25 del README (ASII-25)** · **Adaptación:** gestión de ejecuciones automatizadas y calidad de versiones · **Proceso:** planificación de pruebas → ejecución E2E → conservación de evidencia → aplicación de quality gates.

Integrado en `develop` con los PR #812–#838 (ramas `feature/asii-25-semana-NN-ci-e2e-cindyruano`). Estado al 29-oct-2026 en [semana-16](semana-16/README.md).

## Qué hay y dónde

| Pieza | Ubicación | Estado |
|---|---|---|
| Micro-HIS del módulo (dominio, casos de uso, PDO/InMemory, HTTP, CI) | [`micro-his/qa-calidad-versiones`](../../micro-his/qa-calidad-versiones/README.md) | [Implementado] 148 casos en verde |
| Pipeline CI por etapas + quality gate | [`.github/workflows/qa-ci-e2e.yml`](../../.github/workflows/qa-ci-e2e.yml) | [Implementado] YAML válido; **sin ejecución en GitHub todavía** |
| Pruebas E2E | [`tests/E2E`](../../tests/E2E/) | Módulo QA: 9/9 en local. Pila completa: preparado, solo corre en el job `e2e` |
| Prototipo navegable | `micro-his/qa-calidad-versiones/public/prototipo` | [Implementado] |
| Guía de despliegue | [guia-despliegue](guia-despliegue/README.md) | Procedimiento; pasos de servidor propuestos |
| Bloqueos y comentarios para el equipo | [pendientes-y-bloqueos.md](pendientes-y-bloqueos.md) | Módulos ajenos sin pruebas (D-01: Josué y Gerson) y 8 decisiones pendientes |

## Semanas

| Semana | Entregable | Carpeta |
|---|---|---|
| 1 | UML de casos de uso, actividad y secuencia + narrativa de alcance | [semana-01](semana-01/README.md) |
| 2 | RF/RNF, criterios de aceptación y DIP antes/después | [semana-02](semana-02/README.md) |
| 3 | Vista C4/capas; inicio del micro-HIS con camino feliz, regla y error de persistencia | [semana-03](semana-03/README.md) |
| 4 | MVC, Repository con PDO e InMemory, repositorio compartido y propuesta a Javier | [semana-04](semana-04/README.md) |
| 5 | Contrato API, OpenAPI, contratos con otros módulos, frontera de microservicio, plan de PR | [semana-05](semana-05/README.md) |
| 6 | Presentación, matriz decisión→evidencia y cambio práctico | [semana-06](semana-06/README.md) |
| 7 | Componentes, contratos E/S y refactor del punto más acoplado | [semana-07](semana-07/README.md) |
| 8 | User flow por rol y 6 wireframes | [semana-08](semana-08/README.md) |
| 9 | Checklist heurístico/WCAG, 9 hallazgos medidos y backlog | [semana-09](semana-09/README.md) |
| 10 | Propuesta móvil 320–430 px con capturas y 2 escenarios | [semana-10](semana-10/README.md) |
| 11 | Prototipo, mapa de navegación y capturas desktop/móvil | [semana-11](semana-11/README.md) |
| 12 | Defensa, recorrido, matriz y cambio solicitado | [semana-12](semana-12/README.md) |
| 13 | Revisión técnica formal | [semana-13](semana-13/README.md) |
| 14 | Plan SQA | [semana-14](semana-14/README.md) |
| 15 | Unitarias, caja blanca, caja negra, evidencias y defectos | [semana-15](semana-15/README.md) |
| 16 | Integración en `develop`, pipeline on-premise, gate sobre la versión real y D-01 cerrado | [semana-16](semana-16/README.md) |

Diagramas: fuentes `.mmd` en cada `semana-XX/fuentes/`, SVG al lado; cómo regenerarlos en [diagramas/README.md](diagramas/README.md).

## Qué se verificó y qué no

- **Verificado en local:** las 7 suites PHP del módulo, 7 pruebas del prototipo, 9 E2E del módulo, Unit/Feature/Integration de `develop` (5/102/4), micro-HIS de todos los compañeros, `npm run build`, contrato de rutas (17/17) y Pint de los archivos nuevos.
- **No verificado:** el workflow en GitHub Actions, la suite `Database` y el E2E de pila completa (requieren PostgreSQL efímero; aquí no hay base de prueba). El 21-oct el gate local quedó **RECHAZADO** por D-01 (4 micro-HIS sin `tests/run.php`); D-01 se cerró y el 29-oct el rechazo se debe a `phpunit-feature`: 46 fallos por falta de PostgreSQL y 1 de contrato de otro módulo (ver semana 16).
- **No se tocó:** base de datos, migraciones, `config/rbac.php`, rutas, controladores ni módulos de otros compañeros; `composer.json` y `package.json` sin cambios.

## Cómo dividir en PR

Cada PR ≤ 400 líneas, desde `origin/develop`, apilados en este orden (líneas aproximadas): 1) dominio — reglas del plan y la ejecución; 2) dominio — gate y política; 3) aplicación; 4) persistencia + esquema de prueba; 5) presentación + composición; 6) integración + `Ci/`; 7) `bin/` + `config/`; 8–10) pruebas PHP (unit+whitebox, blackbox+application, pdo+ci+architecture); 11) servidor de demo + prototipo (dos PR); 12) `tests/E2E`; 13) workflow; 14+) un PR de docs por semana (semana 15 separa evidencias JUnit). Así el pipeline entra cuando ya existe todo lo que ejecuta.
