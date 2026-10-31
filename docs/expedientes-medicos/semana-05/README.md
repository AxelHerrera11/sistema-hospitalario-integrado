# Semana 05 — Cliente-servidor, API REST, microservicios e integración

**Tarea:** evolucionar el mismo micro-HIS y flujo hacia un diseño cliente-servidor y evaluar una frontera de
microservicio: contrato API, propiedad de datos, comunicación, seguridad, resiliencia, observabilidad y consistencia;
diagramas y una migración razonada, sin infraestructura de producción ni división sin justificación medible.

## Entregables

| Archivo | Contenido |
|---|---|
| [`contrato-api.md`](contrato-api.md) + `micro-his/expediente-medico/openapi.yaml` | Contrato del recurso, cabeceras, respuestas y diferencias micro-HIS/Laravel |
| [`frontera-microservicio.md`](frontera-microservicio.md) | Comparación de aspectos, medición, decisión, disparadores y migración por pasos |
| [`01-cliente-servidor.svg`](01-cliente-servidor.svg), [`02-frontera-microservicio.svg`](02-frontera-microservicio.svg), [`03-secuencia-apertura.svg`](03-secuencia-apertura.svg) | Diagramas (fuentes en [`fuentes/`](fuentes/)) |
| [`plan-issue-rama-pr.md`](plan-issue-rama-pr.md) | Issue, rama, worktree, commits y PR |
| [`herramientas/medir-consulta.php`](herramientas/medir-consulta.php) · [`evidencias/medicion-en-proceso.txt`](evidencias/medicion-en-proceso.txt) | Medición real usada en la decisión |
| [`evidencias/curl-contrato.txt`](evidencias/curl-contrato.txt) | Respuestas reales del prototipo por `curl` |
| [`evidencias/route-guard.txt`](evidencias/route-guard.txt) | Guardián de rutas del CI con la ruta del expediente registrada (`tenant` + `jwt.auth` + `rbac:expediente.ver`) |

## Ubicación del código

- Contrato: `micro-his/expediente-medico/openapi.yaml`.
- Prototipo HTTP: `micro-his/expediente-medico/public/index.php`.
- Adaptador Laravel: `app/ExpedienteMedico/ExpedienteMedicoServiceProvider.php` y `ExpedienteMedicoController.php`;
  prueba `tests/Feature/ExpedienteMedico/ExpedienteMedicoApiTest.php`.

## Cómo verificar

```bash
php vendor/bin/phpunit tests/Feature/ExpedienteMedico
php docs/expedientes-medicos/semana-05/herramientas/medir-consulta.php 500
```

## Estado

- **Implementado:** contrato, prototipo HTTP y adaptador Laravel de la consulta (probado con middlewares reales).
- **Registrado:** `App\ExpedienteMedico\ExpedienteMedicoServiceProvider` en `bootstrap/providers.php` (autorizado el
  2026-10-23); la ruta pasa el guardián de rutas del CI (`evidencias/route-guard.txt`).
- **Propuesto:** microservicio (decisión: no dividir), SPA Vue del expediente, formato común de errores (Lis).
