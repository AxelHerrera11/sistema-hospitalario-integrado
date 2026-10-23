# Evidencia de prototipo — ASII-06 Semana 11

## Base y alcance

`ASII06_SEMANA11_BASE`:
`b25c9966557905bd14201b84945cd11d709956db`.

Rama: `feature/asii-06-semana-11-salas-camas-madelinceron`.

La plantilla del curso define Semana 11 como “mockup o prototipo”. Se
implementó una vista Vue funcional del HIS, no una captura estática.

## Integración

- Ruta frontend: `/wards`, name `wards`, protegida con autenticación y el
  permiso existente `camas.ver`.
- Navegación: **Salas y camas**, visible solo si el conjunto de permisos
  efectivos del usuario contiene `camas.ver`.
- API compartida: `api` de `resources/js/plugins/axios.js`, con configuración
  vigente de autenticación y encabezado de tenant.
- La página importa las tarjetas `WardCatalogWardCard.vue` y
  `WardCatalogBedCard.vue` y `pages/ward-catalog.css`, integrados por #1068.
- El CSS compartido define foco visible y botones de acción de 44 px mínimos;
  la tarjeta de ward expone su selección con `aria-pressed`.
- `GET /api/v1/wards`: muestra `ward_id` como clave interna y presenta
  `code`, `name`, `location`, `status`.
- `GET /api/v1/wards/{ward}/beds`: presenta `code`, `type`, `status`; el
  `ward_id` de la ruta proviene del registro seleccionado.
- Rutas backend existentes aplican middleware `api`, `tenant`, `jwt.auth` y
  `rbac:camas.ver`.

## Estados implementados

- Carga y actualización de wards.
- Lista vacía de wards y ward existente sin camas.
- Errores de `401`, `403`, `404`, `503` y conectividad, con mensajes seguros.
- Reintento en errores temporales; recarga de wards cuando la ward seleccionada
  ya no existe.
- El cambio rápido de selección ignora respuestas de camas obsoletas.
- Acceso sin permiso muestra restricción y la navegación se oculta por permiso
  efectivo; la seguridad del servidor no se sustituye.

## Diseño y accesibilidad

La vista utiliza tarjetas y dos columnas en escritorio; bajo 700 px, apila
wards y camas. A 380 px o menos, las acciones ocupan el ancho disponible.
Los controles son nativos, tienen foco visible, tamaño mínimo aproximado de
44 px, y los estados se exponen mediante `role="status"` o `role="alert"`.
Los datos esenciales pueden envolver y no dependen del color.

Esto no acredita cumplimiento WCAG: no se hizo una revisión con lector de
pantalla, herramienta de contraste ni pruebas manuales de zoom/reflow en
navegador.

## Pruebas y validaciones ejecutadas

- `npm run build` — **PASS**, Vite compila el prototipo (incluye
  `WardCatalogPage-*.js`) con los componentes de la Semana 11a.
- `node --test tests/Frontend/WardCatalogNavigationTest.mjs` — **PASS**,
  2 tests, 0 fallos.
- `node --test resources/js/router/permissions.test.mjs` y
  `resources/js/router/navigation.test.mjs` — **PASS**.
- `php vendor/phpunit/phpunit/phpunit tests/Feature/SalasCamas/WardCatalogEndpointTest.php`
  — **PASS**, 13 tests, 54 assertions (incluye las regresiones de
  aislamiento: `X-Tenant-ID` de otro hospital → `403 TENANT_MISMATCH`,
  sin cabecera → `400 TENANT_REQUIRED`).
- `php micro-his/salas-camas/tests/run.php` — **PASS**, 4 escenarios.
- `php artisan route:list --path=api` — **PASS**, figuran
  `api/v1/wards` y `api/v1/wards/{ward}/beds`.
- `git diff --check` — **PASS**.

## Riesgos y pendientes

- No se ejecutó una inspección visual/manual ni un recorrido autenticado en
  navegador; el build no los sustituye.
- La terminología en español de estados de catálogo requiere confirmación
  funcional; la UI presenta el valor textual real de la API.
- El estado `occupied` es dato del catálogo; la vista no lo interpreta como
  estancia ni asignación clínica.
- La conexión `hospital` sigue fija; el tenant se valida, pero no hay selección
  dinámica de base de datos ni aislamiento multi-tenant completo.
