# Plan de Issue, rama, worktree y PR

| Elemento | Plan | Estado real |
|---|---|---|
| Issue | [#165](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/165) "ASII-14 — Catálogo de medicamentos". Cada PR usa `Refs #165`; el Issue no se cierra hasta la Semana 18 | Abierto |
| Rama | `feature/asii-14-semana-NN-<parte>-catalogo-medicamentos-yamilet1235`, una por parte, desde `origin/develop` actualizado | Así desde la Semana 2 |
| Worktree | El README pide `git worktree add ../shi-asii-14-catalogo -b <rama> origin/develop` para aislar cada módulo sin mezclar cambios | Ver abajo |
| PR | Plantilla `.github/pull_request_template.md`, destino `develop`, al menos 3 commits significativos, ≤ 400 líneas (objetivo 350) | Así desde la Semana 2 |
| Control | `ASII PR Guard` (`.github/workflows/branch-protection.yml`): cuerpo de al menos 80 caracteres y nombre de rama válido | Automático |
| Aprobación | `BRANCH_PROTECTION.md`: 1 aprobación en `develop` y revisión de Code Owners en `main`. La autora no puede aprobar su propio PR | Aprobador por confirmar |
| Promoción | Tras cada parte integrada, PR `develop → main` inmediato y confirmación remota antes de la parte siguiente | Desde la Semana 4 |

## Papel del worktree y ejecución real

El worktree del README sirve para que cada estudiante trabaje su rama en una carpeta propia sin cambiar la rama de otra copia. En este módulo, la copia principal del repositorio está ocupada por otra sesión que revisa PR de compañeros. Por eso la ejecución se hace en un **clon independiente autorizado** (`asii-14-catalogo-medicamentos-yamilet1235`). Cumple el mismo objetivo de aislamiento sin crear worktrees sobre esa copia.

## Integración con otros módulos (por coordinar)

| Módulo | Qué se necesita | Estado |
|---|---|---|
| RBAC (ASII-02) | Aprobar `medicamentos.registrar` y `medicamentos.cambiar_vigencia` y asignarlos al gestor | Propuesto |
| Esquema H5 | Columnas e identidad de `medication`; restricción única de código por hospital | Pendiente en openspec |
| Prescripciones | Usar `MedicationLookup` o `GET /api/v1/medications/{id}` y rechazar medicamentos `inactive` | Propuesto |
| Alergias | Codificación común del principio activo | Por acordar |
