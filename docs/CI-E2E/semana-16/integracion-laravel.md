# Semana 16 (parte 2) — Calidad de versiones integrada en Laravel

Hasta ahora el módulo (gestión de ejecuciones automatizadas y calidad de versiones) vivía solo como micro-HIS, con
servidor de demostración y CLI de CI. Esta parte lo publica en la API v1 de la aplicación.

## Qué se agregó

| Pieza | Ubicación |
|---|---|
| Provider registrado después de Gobernanza | `app/QaCalidad/QaCalidadServiceProvider.php`, `bootstrap/providers.php` |
| Controlador delgado (identidad del JWT y tenant del middleware) | `app/QaCalidad/QaRunController.php` |
| Adaptador RBAC real (falla cerrado con 503) | `micro-his/qa-calidad-versiones/src/Integration/RbacAccessGate.php` |
| Almacén SQLite propio (`QA_RUNS_SQLITE`, por defecto `storage/app/qa/qa_runs.sqlite`) | `config/qa.php` |
| Permisos activos para superadmin y admin_hospital | `config/rbac.php`, `docs/rbac/catalogo-modulos.md` |
| Prueba Feature con middlewares, RBAC y auditoría reales | `tests/Feature/QaCalidad` |

No hay migraciones: las ejecuciones son metadatos de pruebas y se guardan fuera de CENTRAL y HOSPITAL, con el
esquema `database/qa_runs.sqlite.sql` del módulo. La auditoría sí va a la bitácora de Gobernanza (módulo
`qa_calidad`).

## Contrato

| Método | Ruta | Permiso |
|---|---|---|
| GET | `/api/v1/qa/runs` y `/api/v1/qa/runs/{run}` | `qa.ejecuciones.ver` |
| POST | `/api/v1/qa/runs` (planificar), `/{run}/transitions` (start, complete, abort), `/{run}/results` | `qa.ejecuciones.registrar` |
| POST | `/api/v1/qa/runs/{run}/gate-decisions` | `qa.gates.evaluar` |

Middlewares: `api`, `tenant`, `jwt.auth` y `rbac:` con el permiso de la operación; el caso de uso vuelve a exigirlo.
Cuerpos, respuestas y errores son los de `micro-his/qa-calidad-versiones/README.md` (sin cambios).

## Validación (2026-10-30)

```bash
php vendor/bin/phpunit tests/Feature/QaCalidad
# OK (3 tests, 41 assertions): plan → start → resultados → complete → gate approved/rejected; 403 para roles
# clínicos; 401 sin sesión; 422/409 de dominio; una ejecución de otro hospital responde 404.
php micro-his/qa-calidad-versiones/tests/run.php
# TODAS LAS SUITES APROBADAS
php vendor/bin/phpunit tests/Feature/Rbac
# Solo falla PublishedContractsTest por docs/notas-soap (ajeno, también falla en develop).
```
