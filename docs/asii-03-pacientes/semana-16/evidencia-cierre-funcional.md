# Evidencia de cierre funcional — Semana 16

Base de medición `SEMANA16_BASE`: `c26465a5ebb2113cdb814661f4510f7ad15e4af2`.

## Estado de integración

| Verificación | Estado | Resultado comprobado |
|---|---|---|
| Conexión PostgreSQL | PASS | CENTRAL `his_central_test` en `127.0.0.1:55431` y HOSPITAL `his_hospital_test` en `127.0.0.1:55433`, PostgreSQL 16. |
| Migraciones | PASS | 10 migraciones CENTRAL y 9 HOSPITAL aplicadas; estado `[1] Ran` en ambas conexiones. |
| Tablas de autenticación/tenant CENTRAL | PASS | Existen `networks`, `hospitals`, `central_users`, `hospital_memberships`, `auth_credentials` y `auth_sessions`. |
| Tabla `local_patients` | PASS | Existe en HOSPITAL; `to_regclass('public.local_patients')` devuelve la tabla. |
| INSERT / SELECT / UPDATE PostgreSQL | PASS | `PatientPostgresTest` inserta, busca por código, actualiza y vuelve a leer una fila ficticia mediante `PdoPatientRepository`. |
| Restricción UNIQUE de `code` | PASS | Duplicado rechazado por PostgreSQL y mapeado a `PersistenceFailure`. |
| Restricción UNIQUE de `global_id` | PASS | Duplicado rechazado por PostgreSQL y mapeado a `PersistenceFailure`. |
| Limpieza de prueba | PASS | Todas las operaciones PostgreSQL se ejecutan en una transacción revertida; comprobación posterior: 0 filas en `local_patients`. |
| TenantMiddleware real | Parcial | Conexión CENTRAL y middleware real: cabecera ausente → `TENANT_REQUIRED` (400); UUID no registrado → `HOSPITAL_NOT_FOUND` (404). No hay hospital activo para probar autorización de un tenant válido. |
| Login / JWT reales | BLOQUEADO | No se ejecutó login con identidad real: CENTRAL tiene 0 hospitales, usuarios, membresías y credenciales de prueba. |
| RBAC con identidad real | BLOQUEADO | Solo se ejecutó la suite existente con identidad de prueba; no hay roles/usuario real. |
| Vue (código/build) | PASS | Pantalla conectada a GET/POST/PATCH; build Vite finalizó correctamente. |
| GET / POST / PATCH desde Vue a PostgreSQL | BLOQUEADO | No hubo sesión autenticada ni recorrido API→PostgreSQL desde Vue. La prueba PostgreSQL cubre el repositorio, no la API autenticada. |
| Recorrido extremo a extremo | BLOQUEADO | Login → tenant autorizado → Vue → API → PostgreSQL no se pudo realizar. |

## Preparación de bases

Se confirmó primero que las dos bases estaban vacías (0 tablas). Las variables
de conexión de `phpunit.xml` se asignaron solo al proceso de cada comando; no se
creó ni modificó `.env`.

Scripts oficiales en `composer.json`:

- `composer run migrate:central -- --force` — aplicó únicamente
  `database/migrations/central`.
- `composer run migrate:hospital -- --force` — aplicó únicamente
  `database/migrations/hospital`.
- `composer migrate:framework` también está definido, pero no se ejecutó: las
  suites de autenticación usan su propio esquema de prueba y no fue necesario
  para Pacientes.
- No se ejecutó `migrate:fresh`, migración de framework ni comando
  `tenants:migrate-fresh`.

Migraciones CENTRAL aplicadas (10):

`2026_08_11_000000_create_network_registry`,
`000100_create_identity_and_memberships`,
`000200_create_mpi_tables`,
`000300_create_consent_and_access_control`,
`000400_create_central_sync_tables`,
`000500_create_auth_credentials_and_sessions`,
`000600_create_auth_refresh_tokens`,
`000700_create_audit_log`,
`000800_enrich_audit_log`,
`001000_require_audit_log_hashes`.

Migraciones HOSPITAL aplicadas (9):

`2026_08_11_000000_enable_hospital_extensions`,
`000100_create_local_identity_and_professional_tables`,
`000200_create_patient_and_care_tables`,
`000300_create_scheduling_and_bed_tables`,
`000400_create_medical_record_tables`,
`000401_create_allergy_and_vital_sign_tables`,
`000500_create_medication_tables`,
`000700_create_audit_and_sync_tables`,
`000900_create_doctor_schedules`.

## Autenticación, tenant y provisioning

`auth:provision-first-superadmin` es el comando oficial de bootstrap, pero
requiere un hospital CENTRAL activo ya existente. El registro regular de
hospitales exige una identidad autorizada; `DatabaseSeeder` no crea hospitales
ni usuarios. Como CENTRAL quedó con 0 hospitales y no se deben inventar
identidades o tokens, no se intentó provisionar una cuenta ni simular un login.
Los tests de autenticación existentes crean identidad en SQLite en memoria;
no demuestran JWT ni autorización real contra estas bases.

No se encontró un workflow GitHub que inicialice ambas bases. El único workflow
PostgreSQL relacionado es `audit-chain-integrity.yml`, que levanta CENTRAL para
la prueba de integridad de auditoría.

## Comandos y resultados

- `composer run migrate:central -- --force` — PASS, 10 migraciones.
- `composer run migrate:hospital -- --force` — PASS, 9 migraciones.
- `php artisan migrate:status --database=central --path=database/migrations/central --realpath` — PASS, 10 `Ran`.
- `php artisan migrate:status --database=hospital --path=database/migrations/hospital --realpath` — PASS, 9 `Ran`.
- `php vendor/phpunit/phpunit/phpunit tests/Database/Hospital/PatientPostgresTest.php` — PASS, 4 tests y 32 assertions.
- `php artisan test tests/Database/Hospital/PatientPostgresTest.php` — 4 tests y 42 assertions, cero fallos; el wrapper reporta 4 warnings con una ruta `file_get_contents(...)` truncada. La ejecución directa del mismo archivo con PHPUnit terminó PASS, 4 tests y 42 assertions.
- Consulta PDO posterior a la suite — CENTRAL: 0 hospitales, 0 usuarios, 0 membresías y 0 credenciales; HOSPITAL: 0 pacientes.
- `php artisan db:show --database=central` y `php artisan db:table local_patients --database=hospital` conectan y detectan las tablas, pero fallan al formatear el resultado porque falta la extensión PHP `intl`. Las migraciones, consultas PDO y tests confirman el esquema sin depender de esos comandos de presentación.
- `php artisan test tests/Feature/Patients/PatientApiTest.php` — 10 tests, 47 assertions, cero fallos (wrapper informa warnings); PHPUnit directo terminó PASS.
- `php artisan test tests/Unit/Patients/PatientServiceTest.php` — PASS, 5 tests y 10 assertions.
- `php artisan test tests/Feature/Rbac/PatientPermissionsTest.php` — 131 assertions, cero fallos (wrapper informa warning); PHPUnit directo terminó PASS.
- `php micro-his/pacientes/tests/run.php` — PASS, 4 escenarios.
- `php artisan route:list --path=api` — PASS; rutas GET/POST/PATCH presentes.
- `npm run build` — PASS; Vite compiló 105 módulos.
- `node --test resources/js/router/permissions.test.mjs` — PASS, 1 test.
- `php -l tests/Database/Hospital/PatientPostgresTest.php` y `git diff --check` — PASS.

## Conclusión

La persistencia del repositorio Pacientes y sus restricciones únicas quedan
verificadas contra PostgreSQL real de prueba. También queda comprobado el
rechazo de tenants ausentes/no registrados. Falta un hospital activo y una
identidad de prueba autorizada para validar login/JWT, RBAC real, operaciones
API autenticadas y el recorrido Vue completo. No se declara Pacientes cerrado
funcionalmente.
