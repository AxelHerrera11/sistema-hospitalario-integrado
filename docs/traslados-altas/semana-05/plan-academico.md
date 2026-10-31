# Plan académico: Issue, rama, worktree y PR

| Elemento | Convención del curso | Aplicación en ASII-08 |
|---|---|---|
| Issue | Un Issue por módulo | #9; los PR usan `Refs #9` y nunca lo cierran |
| Rama | `feature/asii-XX-slug-usuario` desde `origin/develop` | `feature/asii-08-semana-NN-<parte>-traslados-altas-yamilet1235`, una por parte |
| Worktree | `.github/BRANCH_PROTECTION.md` propone un worktree por estudiante | **No se usa.** Se trabaja en un clon independiente (`asii-08-traslados-altas-yamilet1235`) para no tocar la copia principal ni sus cambios locales |
| PR | Hacia `develop`, con plantilla y evidencia | Como máximo 400 líneas (adiciones más eliminaciones) y al menos 3 commits significativos; merge commit |
| Promoción | `develop → main` por la responsable de integración | Después de cada merge a `develop`, un PR de promoción de 400 líneas o menos |
| Aprobación | `BRANCH_PROTECTION.md` exige 1 aprobación en `develop` | **Pendiente:** ningún PR de ASII-08 tiene aprobación de otra persona; la regla no está configurada en GitHub |
