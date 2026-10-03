# Correcciones aplicadas a la base del curso

Este documento explica qué se cambió respecto al repositorio base común
(`sistema-hospitalario-integrado-SistenasII-2026`) antes de repartir el
trabajo entre las 9 áreas del grupo. Todo se verificó con
`php artisan migrate:fresh --seed`, `php artisan test` (18 pruebas, en SQLite y PostgreSQL) y
peticiones reales al servidor.

## Errores que impedían que la base funcionara bien

### 1. El middleware `JwtAuth` no compilaba
`app/Http/Middleware/JwtAuth.php` declaraba la clase `JwtAuth` e importaba
el facade `JWTAuth`. PHP no distingue mayúsculas en nombres de clase, así que
el archivo producía *Fatal error: Cannot declare class ... because the name is
already in use* (incluso `php -l` falla). Se importó el facade con alias
(`use ...\JWTAuth as JWT;`).

### 2. La validación token ↔ hospital nunca se ejecutaba
El paquete `tymon/jwt-auth` registra su propio alias `jwt.auth`, que en el
servidor sobrescribía al del proyecto. Consecuencia: un usuario del hospital A
podía usar su token enviando `X-Tenant-ID` del hospital B y la API respondía
200. El middleware del proyecto ahora se llama **`auth.jwt`**; con él, esa
petición responde 403.

## Seguridad y aislamiento de datos

### 3. Aislamiento automático por hospital
Nuevo trait `App\Models\Concerns\BelongsToTenant`, aplicado a todos los
modelos con `tenant_id`. Filtra todas las consultas por el hospital de la
petición y asigna `tenant_id` al crear registros. `User` también usa el trait:
el JWT compara primero su claim de hospital con la petición y solo después
carga al usuario mediante el scope. Cubierto por
`tests/Feature/TenantIsolationTest.php`.

### 4. Llaves foráneas hacia `tenants`
`tenants.id` pasó a `uuid` y todas las columnas `tenant_id` son
`foreignUuid(...)->constrained('tenants')`. Antes eran `char(36)` sin
relación, y en MySQL el tipo ni siquiera coincidía con `tenants.id`.

### 5. Códigos únicos por hospital, no globales
`beds.code`, `patients.code`, `doctors.license_number`, `admissions.code`,
`medical_records.record_number`, `lab_orders.code` y `samples.barcode` ahora
son `unique(['tenant_id', ...])`.

### 6. `/auth/register` ya no es público
Antes cualquiera con el ID del hospital podía crearse una cuenta. Ahora
requiere token y rol **Admin**, valida el correo único por hospital y acepta
un campo `role`.

### 7. Roles y permisos
- Registrados los middlewares de Spatie: `role`, `permission`,
  `role_or_permission` (antes `role:Admin` fallaba).
- Guard por defecto `api` (los roles usan ese guard).
- `RoleSeeder` crea 45 permisos `modulo.accion` y los asigna a 6 roles
  (se agregó **Bioquimico** para la validación de resultados).
- Respuesta JSON uniforme 403 cuando falta un permiso.
- `/auth/me` devuelve también la lista de permisos del usuario.
- Login limitado a 10 intentos por minuto.

## Simplificación

### 8. Se eliminó Stancl Tenancy
Solo se usaba como clase base de `Tenant` y obligaba a mantener una conexión
`central` fija a SQLite (con MySQL, los tenants se habrían leído de otra base).
`Tenant` es ahora un modelo normal con `HasUuids`. Se quitó de
`composer.json` y `composer.lock` (junto con sus 3 dependencias exclusivas)
y se eliminó la conexión `central` de `config/database.php`.

### 9. Modelos faltantes
Se agregaron `Appointment`, `BedTransfer`, `Diagnosis`, `LabTest`,
`LabOrderItem`, `Sample`, `CriticalAlert` y `AuditLog`, más las relaciones
correspondientes en `Patient`, `Admission`, `SoapNote`, `LabOrder` y
`LabResult`.

## Frontend

### 10. Rutas protegidas y sesión
- La ruta de inicio exige sesión (`meta.requiresAuth`) y el guard soporta
  `meta.permission`.
- Interceptor de Axios: ante un 401 limpia la sesión y redirige al login.
- El store expone `can('modulo.accion')` y `hasRole('Rol')` para mostrar u
  ocultar opciones.
- Botón de cerrar sesión en el layout; el login respeta `?redirect=`.

## Pruebas
- `phpunit.xml` define `JWT_SECRET` de prueba.
- `ExampleTest` usa `withoutVite()` (antes fallaba sin el build del frontend).
- Nuevas: `AuthTest` (8 pruebas) y `TenantIsolationTest` (3 pruebas).

## PostgreSQL como motor del proyecto

Verificado con PostgreSQL 16: migraciones, seeders demo y las 18 pruebas.

### 11. `X-Tenant-ID` inválido provocaba error 500
En PostgreSQL `tenants.id` es de tipo `uuid`; un valor como `hospital-1`
generaba `SQLSTATE[22P02]` y la respuesta mostraba el SQL completo. Ahora
`TenantMiddleware` valida el formato y responde 400.

### 12. Búsquedas sin distinguir mayúsculas ni acentos
En PostgreSQL `LIKE` distingue mayúsculas. Se agregó la macro
`whereSearch()` (en `AppServiceProvider`) y la migración que habilita la
extensión `unaccent`. En SQLite/MySQL la macro usa `LIKE` normal.

### 13. Configuración
- `.env.example` apunta a PostgreSQL (SQLite queda como alternativa comentada).
- `phpunit.pgsql.xml` corre las pruebas contra la BD `hospital_his_test`.
- Nuevas pruebas: `SearchMacroTest` y UUID inválido en `AuthTest`.

## Acción requerida al actualizar
La estructura de la base de datos cambió, así que hay que recrearla:

```bash
composer install
php artisan migrate:fresh --seed
php artisan test --configuration=phpunit.pgsql.xml
```
