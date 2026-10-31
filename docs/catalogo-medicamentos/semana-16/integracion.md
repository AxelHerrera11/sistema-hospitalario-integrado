# Integración, validación y primera entrega funcional

## Qué se integró (PR #602)

| Pieza del HIS | Tipo | Cómo la usa el catálogo |
|---|---|---|
| `bootstrap/app.php` (`apiPrefix: api/v1`) y el grupo de middleware `api` | Existente | Rutas `/api/v1/medications` |
| `tenant` y `jwt.auth` | Existentes | Delante de cada ruta, como en RBAC |
| `App\Rbac\RequirePermission` (`rbac:<permiso>`), `Authorize`, `RoleGrantsPermission` y `ConfiguredPermissions` (`config/rbac.php`) | Existentes, sin cambios | Autorización por operación y tenant |
| `MedicationCatalogServiceProvider` | Nuevo | Patrón de `RbacServiceProvider`: carga el micro-HIS y enlaza contratos |
| `MedicationCatalogController` | Nuevo, delgado | Reutiliza el `Router` del micro-HIS; agrega `trace_id` y traduce `id` → `medication_id` |
| `HospitalMedicationRepository` | Nuevo | `MedicationRepository` sobre la conexión `hospital`; si falla la conexión responde 503 |
| Domain, Application y Persistence del micro-HIS | Existentes (Semanas 3–15) | Sin duplicar reglas en Laravel |

**Propiedad de datos:** el catálogo es el único escritor de `medication`. Las lecturas de otros módulos pasan por `MedicationLookup` o por `GET /api/v1/medications/{id}`.

## Configuración reproducible (sin secretos)

Variables de `.env.example` que usa el catálogo: `DB_CONNECTION=hospital`, `HOSPITAL_DB_HOST`, `HOSPITAL_DB_PORT`, `HOSPITAL_DB_DATABASE`, `HOSPITAL_DB_USERNAME` y `HOSPITAL_DB_PASSWORD` (vacío en el ejemplo). No hay credenciales en el repositorio.

```bash
composer install && npm ci && npm run build
cp .env.example .env && php artisan key:generate
php artisan route:list --path=api/v1/medications      # 4 rutas
php vendor/bin/phpunit tests/Feature/MedicationCatalog
```

En OneDrive, Windows marca `bootstrap/cache` y `storage` como solo lectura; hubo que ejecutar `attrib -R` para que PHP pudiera escribir.

## Validación ejecutada (20/10/2026)

| Comando | Dónde | Resultado |
|---|---|---|
| `phpunit tests/Feature/MedicationCatalog` | Merge de prueba de la rama con `origin/develop` | OK (6 tests, 58 assertions) |
| Misma suite | Rama apilada (base `b87bca1`) | Lecturas 403: esa base no tiene `medicamentos.ver` (llegó en `7d7c9b0`) |
| Mutación: quitar `rbac:medicamentos.ver` de `GET /medications` | Merge de prueba | 2 fallos: la protección está probada |
| `phpunit --testsuite Unit,Feature` | Merge de prueba | 32 tests, **1 fallo**: `Rbac\PublishedContractsTest` por los permisos propuestos sin catalogar (bloqueo de #586) |
| `phpunit --testsuite Unit,Feature`, después de #622 y de Tenants (#657) | `develop` con #602 (`6d4e306`) | OK (68 tests, 1255 assertions). Las pruebas sustituyen el registro de denegaciones, porque `AuditDenials` escribe en CENTRAL |
| `phpunit` completo (Unit, Feature y Database) | `develop` `63a2903`, PostgreSQL 17 desechable en 55431/55433 | OK (121 tests, 1918 assertions) |
| Suites del micro-HIS y `tests/casos.php` | Merge de prueba | Todas exit 0; casos 17/17 |

Casos cubiertos por la integración:
- **Acceso:** 401, `TENANT_REQUIRED` y `TENANT_MISMATCH`.
- **Lectura:** matriz RBAC real, con recepcion → 403.
- **Consulta:** forma del contrato y 404.
- **Escritura:** hasta #627, denegada a todos los roles. Desde #627, con la matriz real, `admin_hospital` obtiene alta 201, 409, 422, vigencia 200 y 422. `medico` y `superadmin` reciben 403 auditado, una membresía de otro hospital no autoriza y registrar no autoriza cambiar la vigencia.
- **Caída:** 503 sin detalles con la conexión `hospital` real ausente.

Prueba de humo por HTTP real (20/10/2026, `php artisan serve` con `tenant`, `jwt.auth` y RBAC reales sobre una base CENTRAL desechable, con login de médico y de recepción en el hospital A):

| Solicitud | Resultado |
|---|---|
| Sin token | 401 `TOKEN_MISSING` |
| Token del hospital A con `X-Tenant-ID` de B | 403 `TENANT_MISMATCH` |
| Recepción busca | 403 `RBAC_DENIED`; queda en `audit_log` (`rbac.access_denied`, `medicamentos.ver`) |
| Médico registra un medicamento | 403 `RBAC_DENIED`; auditado con `medicamentos.registrar` |
| Médico busca | 503 `CATALOG_UNAVAILABLE`: no existe la tabla `medication` (H5) |

## Errores corregidos durante la integración

| Error | Corrección |
|---|---|
| El contrato promete `medication_id` y el micro-HIS serializa `id` | Traducción en el controlador; la prueba exige `medication_id` y que no exista `id` |
| Abrir la conexión al construir el controlador daría 500 antes de la autenticación | Repositorio perezoso que convierte el fallo de conexión en 503 |
| AV3, búsqueda no ASCII distinta entre adaptadores (Semana 15) | LOWER Unicode en SQLite |

## Bloqueos y limitaciones (no se declaran completados)

| Bloqueo | Dependencia | Efecto |
|---|---|---|
| No hay acceso a staging ni on-premise | Infraestructura del curso | No hay evidencia de despliegue real; solo ejecución local |
| ~~No existen los modelos `User` y `Tenant`~~ **Resuelto** | Tenants (ASII-25), integrado en #657 | `tenant` y `jwt.auth` reales comprobados por HTTP. Las pruebas Feature siguen sustituyendo la identidad porque `jwt.auth` consulta la base CENTRAL |
| No existe la tabla `medication` (slice H5) | Esquema HOSPITAL (`openspec/.../tasks.md` 3.5) | Sin migración ni semilla: el API responde 503 contra la base real |
| ~~Permisos de escritura pendientes~~ **Resuelto** | RBAC: catalogados en #622 y activados en #627 solo para `admin_hospital` | Los demás roles siguen con 403 auditado |
| `limit` del contrato no se aplica | Propio | La API siempre devuelve como máximo 50; se corregirá o se ajustará el contrato |
| ~~Sin PostgreSQL de prueba~~ **Resuelto en local** | Entorno | PostgreSQL 17 desechable: suite `Database` OK. No sustituye a staging |
