# Guía de worktrees para ASII

Cada estudiante debe trabajar su módulo en un worktree separado. Esto evita modificar `main`, reduce conflictos entre módulos y permite que cada PR sea revisable.

## Regla principal

> `main` no se usa para desarrollar. Cada módulo inicia desde `origin/develop` en una rama `feature/asii-XX-slug-usuario`.

## Flujo obligatorio

Desde el repositorio principal:

```bash
git fetch origin
git worktree add ../shi-asii-XX-slug -b feature/asii-XX-slug-usuario origin/develop
cd ../shi-asii-XX-slug
```

Ejemplo:

```bash
git fetch origin
git worktree add ../shi-asii-03-pacientes -b feature/asii-03-pacientes-glendi20 origin/develop
cd ../shi-asii-03-pacientes
```

## Convenciones de nombres

| Elemento | Convención | Ejemplo |
|---|---|---|
| Worktree local | `../shi-asii-XX-slug` | `../shi-asii-03-pacientes` |
| Rama feature | `feature/asii-XX-slug-usuario` | `feature/asii-03-pacientes-glendi20` |
| Pull request | `ASII-XX: módulo — usuario` | `ASII-03: pacientes — glendi20` |
| Issue | `ASII-XX — módulo asignado` | `ASII-03 — Pacientes` |

Reemplazos:

- `XX`: número de módulo con dos dígitos.
- `slug`: nombre corto del módulo en minúsculas y sin espacios.
- `usuario`: GitHub username del estudiante.

## Trabajo diario dentro del worktree

```bash
git status
git fetch origin
git merge origin/develop
```

Antes de programar:

- confirma que estás dentro del worktree correcto;
- confirma que la rama empieza con `feature/asii-`;
- revisa el issue asignado;
- trabaja solo archivos relacionados con tu módulo.

Comandos útiles:

```bash
git branch --show-current
git worktree list
git status --short
```

## Prohibiciones

- No trabajar en `main`.
- No abrir PR hacia `main`; todo PR estudiantil va hacia `develop`.
- No hacer `git push --force` ni variantes de force push.
- No mezclar dos módulos en la misma rama.
- No modificar archivos de otro módulo sin acuerdo explícito.
- No abrir PR sin evidencia de análisis, diseño, implementación y pruebas.
- No resolver conflictos eliminando código de otro compañero sin revisión.

## Publicar la rama

Cuando tengas un primer avance coherente:

```bash
git push -u origin feature/asii-XX-slug-usuario
```

Después abre un pull request hacia `develop` y completa la plantilla de PR.

## Mantenerse actualizado con `develop`

Durante el desarrollo:

```bash
git fetch origin
git merge origin/develop
```

Si hay conflictos:

1. detente y lee los archivos en conflicto;
2. conserva los cambios del otro módulo salvo que el docente indique lo contrario;
3. resuelve solo la intersección necesaria;
4. ejecuta pruebas o build;
5. documenta la resolución en el PR.

## Recuperación de situaciones comunes

### No sé en qué worktree estoy

```bash
pwd
git worktree list
git branch --show-current
git status --short
```

Si la rama no empieza con `feature/asii-`, no continúes hasta ubicar el worktree correcto.

### Ya existe la rama feature

Si la rama ya existe, no la crees otra vez. Usa el worktree con la rama existente:

```bash
git fetch origin
git worktree add ../shi-asii-XX-slug feature/asii-XX-slug-usuario
cd ../shi-asii-XX-slug
```

### El worktree aparece movido o no reconocido

Desde el repositorio principal:

```bash
git worktree list
git worktree repair
```

Luego vuelve a comprobar:

```bash
git worktree list
git status --short
```

### Hice cambios por accidente en `main`

No sigas trabajando. Primero guarda evidencia de lo que cambió:

```bash
git status --short
git diff
```

Después avisa al docente o administrador del repositorio antes de mover, copiar o descartar cambios. No uses force push ni comandos destructivos para “arreglar rápido”.

### Mi rama está atrasada respecto a `develop`

```bash
git fetch origin
git merge origin/develop
```

Ejecuta las validaciones del módulo después del merge y documenta el resultado en el PR.

## Evidencia mínima antes del PR

- Issue relacionado.
- Capturas o enlaces a diagramas.
- RF/RNF y criterios de aceptación.
- Contrato API si el módulo expone endpoints.
- Capturas de UI si aplica.
- Comandos ejecutados y resultado.
- Pruebas o explicación técnica si una prueba no aplica.
- Riesgos pendientes y decisiones tomadas.
