# Evidencia de integración — ASII-06 Semana 9

## Base y alcance

`ASII06_SEMANA09_BASE`:
`8e3311f3e3cc1b484c386ab80b2583bd66d7f795`, rama
`feature/asii-06-semana-09-salas-camas-madelinceron`.

La plantilla general del curso define Semana 9 como “usabilidad/accesibilidad”.
El issue #11 y la documentación ASII-06 no añaden criterios específicos para
esta semana. Se hizo una evaluación estática del flujo UX de Semana 8. No se
implementó UI: no hay pantalla ASII-06 que evaluar y el alcance académico
identificado es evaluación, no construcción de frontend.

## Frontend de referencia inspeccionado

- Vue 3, Vue Router y Pinia; alias `@` hacia `resources/js`.
- `resources/js/modules/patients/pages/PatientsPage.vue` consume API con el
  cliente Axios compartido, consulta permisos efectivos con `can()`, gestiona
  errores HTTP y usa `role="status"`/`role="alert"`, labels e indicador de foco.
- `resources/js/router/index.js` declara rutas y `permissions.js` mantiene
  metadatos, navegación y permisos; `navigation.js`/`guards.js` revalidan
  identidad antes de permitir la navegación.
- `AppLayout.vue` ofrece navegación principal, enlace “Saltar al contenido” y
  menú responsive. La implementación de Pacientes sirve como patrón, no como
  componente ASII-06 ya reutilizado.
- No hay una ruta, entrada en navegación ni página del catálogo. RBAC sí
  contiene `camas.ver`; las rutas API de wards y beds lo aplican con
  `api`, `tenant`, `jwt.auth` y `rbac:camas.ver`.

## Material y método

Se revisaron el flujo
[UX de Semana 8](../semana-08/flujo-ux.md), sus contratos JSON, el template
general de tareas, la matriz real de `config/rbac.php`, los archivos frontend
citados y la evaluación de Semana 9 de Pacientes. La inspección es estática;
no incluye pruebas con usuarios, navegador, NVDA/VoiceOver ni comprobación
visual WCAG.

## Pruebas de regresión ejecutadas

- `php vendor/phpunit/phpunit/phpunit tests/Feature/SalasCamas/WardCatalogEndpointTest.php`
  — PASS: 11 tests, 48 assertions.
- `php artisan test tests/Feature/SalasCamas/WardCatalogEndpointTest.php`
  — sin fallos: 11 tests, 48 assertions; el wrapper reportó 11 advertencias
  `file_get_contents(...)`. PHPUnit directo confirmó PASS.
- `php micro-his/salas-camas/tests/run.php` — PASS: 4 escenarios.
- `php artisan route:list --path=api` — PASS: 11 rutas; contiene
  `GET|HEAD api/v1/wards` y `GET|HEAD api/v1/wards/{ward}/beds`.
- `php -l` en los PHP de ASII-06 relevantes — sin errores; no se modificaron
  archivos PHP.
- `git diff --check` — PASS; búsqueda de espacios finales en los documentos
  nuevos sin coincidencias.

No se ejecutó build frontend porque no se modificó frontend. No se ejecutó
PostgreSQL: esta entrega no altera código de persistencia ni base de datos.

## Riesgos y pendientes

- El informe no acredita conformidad WCAG ni usabilidad con personas; necesita
  un prototipo/UI en la fase correspondiente.
- Confirmar si el docente exige otra evidencia además de evaluación y backlog.
- La futura ruta/UI debe usar `camas.ver` y la navegación/autorización existente.
- `TenantMiddleware` valida el hospital, pero `hospital` sigue siendo conexión
  fija; `wards` y `beds` no tienen `tenant_id` y no existe selección dinámica
  de base por tenant. No hay aislamiento multi-tenant completo.
- Esta rama se basa en la integración de Semana 8, que a su vez conserva las
  rutas de Semanas 6–7; no se reescribieron entregas previas ni se modificaron
  sus PR.
