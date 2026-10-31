# Plan de issue, rama, worktree y PR

Basado en la sección «Flujo obligatorio con worktree» del README del repositorio (el archivo `docs/worktree-guide.md`
que ese README enlaza no existe en `develop`), la plantilla `.github/pull_request_template.md` y las reglas del plan de
integración (rama desde `origin/develop`, más de 3 commits, menos de 400 líneas por PR, nunca *force push*).

> Nota de estado: este trabajo se dejó **sin commits** en la rama local actual a pedido de la autora. Los commits,
> ramas y PR los hace ella a mano, con una guía local de división por PR (fuera de esta carpeta, no forma parte de
> ningún PR) que asigna cada archivo a una semana y a partes de menos de 400 líneas.

## Issue

- **Título:** `Expediente médico electrónico base — apertura y consulta autorizada del expediente longitudinal`
- **Descripción:** objetivo, alcance y supuestos (Semana 1), RF/RNF y criterios CA-01…CA-10 (Semana 2), contrato
  (`openapi.yaml`), dependencias reales y pendientes (`../pendientes-integracion.md`).
- **Etiquetas sugeridas:** `modulo:expediente`, `semana-NN`.

## Rama y worktree (una por semana o parte)

Se usa `asii-25`, el número con el que la autora nombra todas sus ramas (Tenants, Gobernanza, CI-E2E), y el sufijo
`expediente-medico`. Observación: el README del repositorio lista el expediente en la fila 10 con otra persona,
mientras que el plan de integración lo asigna a Cindy; confirmar con el grupo.

```bash
git fetch origin
git worktree add ../shi-asii-25-expediente-s05 -b feature/asii-25-semana-05-expediente-medico-cindyruano origin/develop
```

Si la semana se divide en partes, cada parte usa `…-cindyruano-parteP` y apunta a la rama de la parte anterior.

## Commits (más de 3 por PR)

Formato: `tipo(expediente-medico): qué agrega (semana NN parte P)`, un commit por grupo coherente de archivos
(diagramas, documentos, código por capa, pruebas, evidencias, README). Ejemplo de la Semana 5:

1. `feat(expediente-medico): contrato OpenAPI y front controller del prototipo (semana 05)`
2. `feat(expediente-medico): adaptador Laravel de la consulta (semana 05)`
3. `test(expediente-medico): prueba Feature con middlewares reales (semana 05)`
4. `docs(expediente-medico): contrato API, frontera de microservicio y plan de PR (semana 05)`

## PR

- **Título:** `Semana 5 — Expediente médico: contrato API y plan de issue, rama, worktree y PR`
- **Base:** `develop`. **Plantilla:** completa, marcando solo lo verdadero.
- **Validación ejecutada:** pegar los comandos y su salida real (no un resumen): `php micro-his/expediente-medico/tests/run.php`,
  `php vendor/bin/phpunit tests/Feature/ExpedienteMedico`, `node --test micro-his/expediente-medico/tests/ui-estados.test.mjs`.
- **Lista previa:** README, narrativa y diagramas con los mismos nombres que el código; SVG regenerados si cambió un
  `.mmd`; lo no implementado marcado como propuesto; el PR solo toca `docs/expedientes-medicos`,
  `micro-his/expediente-medico`, `app/ExpedienteMedico` y `tests/Feature/ExpedienteMedico`, más las 3 líneas
  autorizadas de `bootstrap/providers.php` en la Semana 5.
- **Revisión:** Yamilet (revisión y merge, según el plan). Nadie mergea su propio PR.
