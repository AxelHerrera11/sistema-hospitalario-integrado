# Evidencia funcional — catálogo de medicamentos (21/10/2026)

Flujo verificado: **interfaz Vue → API v1 Laravel → `tenant` + `jwt.auth` + RBAC → PostgreSQL (tabla `medications`, H5)**. Solo datos ficticios y bases desechables; ninguna base compartida ni productiva.

## Entorno

Windows 11, PHP 8.2.12, Laravel 12.58.0, Node 24.15.0, PostgreSQL 17.10 (clústeres propios en 55431 CENTRAL y 55433 HOSPITAL), Chrome 155.0.8059.80 sin interfaz y puppeteer-core 23.11.1. Código: develop `dc65a89` (#766 H5, #774 adaptador, #776 interfaz). Es un **entorno local de verificación**, no staging ni on-premise.

## Tipos de prueba

| Tipo | Qué es real | Qué se sustituye |
|---|---|---|
| Recorrido E2E (`evidencia/*-e2e.mjs`) | Navegador, interfaz, API, JWT, tenant, RBAC, auditoría CENTRAL y PostgreSQL HOSPITAL | Nada: usuarios y hospitales ficticios sembrados en la base CENTRAL desechable |
| HTTP con `curl` | Igual que el E2E | Nada |
| `tests/Database/Hospital/MedicationCatalogPostgresTest.php` | API, RBAC, repositorio y PostgreSQL | Identidad (`tenant` y `jwt.auth`) y registro de denegaciones |
| `tests/Feature/MedicationCatalog/*` | API y RBAC | Identidad y repositorio (InMemory o PDO sobre SQLite) |
| `micro-his/catalogo-medicamentos/tests/*` | Dominio y adaptador PDO | SQLite en memoria en lugar de PostgreSQL |

## Resultados (comando → resultado → código de salida)

| Comando | Resultado | Salida |
|---|---|---|
| `php vendor/bin/phpunit` (con PostgreSQL) | OK (215 tests, 5366 assertions) | 0 |
| `php vendor/bin/phpunit --testsuite Unit,Feature` (sin PostgreSQL) | OK (104 tests, 4138 assertions) | 0 |
| `php vendor/bin/phpunit --testsuite Database` | OK (101 tests, 1112 assertions); incluye `MedicationIntegrityTest` (6) | 0 |
| `node --test "resources/js/modules/medicamentos/*.test.mjs"` | OK (pass 1, fail 0) | 0 |
| `MSYS_NO_PATHCONV=1 VITE_API_URL=/api/v1 npm run build` | Build correcto | 0 |
| `node catalogo-e2e.mjs http://127.0.0.1:8770 <hospital A>` | 11/11 | 0 |
| `node fallo-e2e.mjs http://127.0.0.1:8771 <hospital A>` (HOSPITAL inexistente) | 4/4 | 0 |

**Recorrido E2E (11/11):**
- **admin_hospital:** registra `ASA-100` (201) y ve el éxito. El duplicado da 409, con el error junto al código y sin éxito. La validación local marca código y nombre. «ÁCIDO» encuentra el registro y una búsqueda sin resultados muestra el estado vacío. El cambio de vigencia da 200 y, al volver a la pantalla, se lee del servidor «No vigente».
- **Enfermería:** no ve alta ni cambio de vigencia, y en 375 px no hay desplazamiento horizontal.
- **Recepción:** ve el aviso de falta de `medicamentos.ver`.

Después, `SELECT code, validity, updated_at > created_at FROM medications` en HOSPITAL devolvió `ASA-100 | inactive | t`.

**Fallo de persistencia (4/4):** el alta responde 503, la pantalla no anuncia éxito, ofrece «Reintentar» y conserva los datos.

**HTTP con JWT real:**
- sin token: 401 `TOKEN_MISSING`;
- enfermería forzando el alta por API: 403 `RBAC_DENIED`;
- token del hospital A con `X-Tenant-ID` de B: 403 `TENANT_MISMATCH`;
- uuid inexistente: 404 `MEDICATION_NOT_FOUND`;
- alta de `admin_hospital`: 201.

Las denegaciones quedaron en `audit_log` de CENTRAL como `rbac.access_denied` con su permiso.

## Defectos encontrados y corregidos al probar con PostgreSQL

| Defecto | Corrección |
|---|---|
| `lastInsertId()` no devuelve el uuid de PostgreSQL | `RETURNING medication_id` (#774) |
| Toda búsqueda daba 503: con `ESCAPE '\'`, PDO para PostgreSQL no enlazaba los parámetros (HY093) | Escape con `!` (#774) |
| Un id que no es uuid daba 503 | Se trata como inexistente, 404 (#774) |
| La prueba del 503 dependía de que la base HOSPITAL no tuviera la tabla | Apunta a una base inexistente (#774) |

## Revisión humana confirmada (21/10/2026)

María Yamilet Lindo Pablo confirmó una prueba manual en el entorno local de verificación (`http://127.0.0.1:8770`, CENTRAL 55431 y HOSPITAL 55433), con la interfaz de #780 y el usuario ficticio `admin@prueba.local` (`admin_hospital`) del hospital A:

1. Registró `IBU-400`.
2. Lo buscó.
3. Consultó su detalle.
4. Cambió su vigencia a «No vigente».
5. Salió de la pantalla y volvió usando el menú, sin recargar con F5; al buscarlo de nuevo conservaba «No vigente».

En la base HOSPITAL, `IBU-400` figura con `validity = inactive`, `created_at` 10:59:28 y `updated_at` 11:00:17 (UTC-6), lo que concuerda con esos pasos.

**Alcance:** solo esos cinco pasos quedan revisados por una persona. El resto de esta evidencia proviene únicamente de ejecuciones automatizadas: duplicado, validación, búsqueda sin resultados, enfermería, médico, recepción, versión móvil, escrituras forzadas por API, tenant cruzado y caída de la base (503). No equivale a una revisión independiente de los PR ni a una aceptación del docente.

## Límites de esta evidencia

- Las dos instancias de hospital comparten la misma base HOSPITAL desechable. El aislamiento probado es el de identidad y tenant (`TENANT_MISMATCH` y membresías por hospital), no el enrutamiento de una base física por hospital, que depende de la plataforma.
- No hay prueba con lector de pantalla ni con usuarios reales.
