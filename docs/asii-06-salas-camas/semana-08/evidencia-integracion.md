# Evidencia — Semana 8

## Base y trabajo previo

`ASII06_SEMANA08_BASE`:
`728762423a7fd26c2913e49438909d3da4d8ebbe`, en la rama
`feature/asii-06-semana-08-salas-camas-madelinceron`. Es el commit de Semana 7
asociado al PR #1041; esta entrega no recrea ni altera las Semanas 6–7.

No hay un enunciado ASII-06 específico para Semana 8 en el issue #11 ni en la
documentación de semanas previas. El template general de issue del repositorio
define la actividad de Semana 8 como “flujo UX”. Se entrega un flujo de
consulta del catálogo basado en las dos rutas existentes; no se presenta como
una funcionalidad UI implementada ni se agrega una nueva API.

El issue maestro enumera el Definition of Done general del módulo (análisis y
diseño, contrato/API y UI si aplica, validaciones, pruebas, evidencia y
riesgos), pero no asigna pruebas ni evidencias concretas para esta semana. Los
requisitos y criterios disponibles corresponden a Semana 2 (creación e
integridad); no se amplían ni se reinterpretan aquí.

## Arquitectura y contrato existente

El flujo UX consume `GET /api/v1/wards` y
`GET /api/v1/wards/{ward}/beds`. Ambas rutas ya aplican `api`, `tenant`,
`jwt.auth` y `rbac:camas.ver`. La propuesta cubre estados autorizados, vacíos,
ward ausente y fallo temporal, sin añadir acciones de escritura.

## Validación

Esta entrega solo añade documentación; no modifica PHP ni pruebas. Se
ejecutaron comprobaciones de regresión sobre el código existente:

- `php vendor/phpunit/phpunit/phpunit tests/Feature/SalasCamas/WardCatalogEndpointTest.php`
  — PASS: 11 tests, 48 assertions.
- `php artisan test tests/Feature/SalasCamas/WardCatalogEndpointTest.php`
  — 11 pruebas, 48 assertions; sin fallos, pero el wrapper reportó 11
  advertencias `file_get_contents(...)`. PHPUnit directo PASS.
- `php micro-his/salas-camas/tests/run.php` — PASS: 4 escenarios.
- `php artisan route:list --path=api` — PASS: 25 rutas registradas; incluye
  `GET|HEAD api/v1/wards` y `GET|HEAD api/v1/wards/{ward}/beds`.
- `php -l` en los PHP relevantes sin cambios — sin errores de sintaxis.
- `git diff --check` — PASS; no detectó whitespace inválido en el diff.
- Búsqueda de espacios finales en los documentos nuevos — sin coincidencias.

No se ejecutó una prueba PostgreSQL: esta entrega agrega solo documentación UX,
sin modificar código de persistencia o base de datos. No se declara validación
PostgreSQL en esta evidencia.

## Riesgos y pendientes

- No se encontró un brief académico ASII-06 más específico que el punto
  general “flujo UX”. Confirmar con docente si el curso requiere wireframes u
  otro entregable adicional.
- La interfaz no está implementada; esta entrega especifica el recorrido, no
  una pantalla funcional.
- `TenantMiddleware` valida el hospital, pero la conexión `hospital` permanece
  fija. Las tablas `wards` y `beds` no contienen `tenant_id` y no existe
  selección dinámica de base por tenant; no se afirma aislamiento
  multi-tenant completo.
- El flujo depende de que la base de Semana 7 y las rutas de Semanas 6–7 se
  mantengan al aprobar el PR #1041.
