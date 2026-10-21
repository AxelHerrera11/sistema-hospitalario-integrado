# Bitácora de despliegue

## Entorno disponible

Solo un **entorno local de verificación**: Windows 11, PHP 8.2.12, Composer 2.10, Node 24.15 y Chrome, en el clon de trabajo de la estudiante. **No hay acceso a staging ni a on-premise**, y no se desplegó sobre infraestructura ajena.

## Registro (20/10/2026)

| # | Paso | Comando | Resultado |
|---|---|---|---|
| 1 | Dependencias PHP | `composer install --no-interaction` | Instaladas; `package:discover` falló porque `bootstrap/cache` estaba en solo lectura |
| 2 | Corrección de entorno | `attrib -R bootstrap\cache /S /D` y `attrib -R storage /S /D` | `is_writable` pasa a `true` (OneDrive marcaba las carpetas como solo lectura) |
| 3 | Configuración | `cp .env.example .env` y `php artisan key:generate` | `.env` local ignorado por Git; sin secretos en el repositorio |
| 4 | Frontend | `npm ci` y `npm run build` | Build correcto; el lockfile no se modificó |
| 5 | Rutas | `php artisan route:list --path=api/v1/medications` | 4 rutas registradas |
| 6 | Pruebas del catálogo | `php vendor/bin/phpunit tests/Feature/MedicationCatalog` (merge de prueba con `origin/develop`) | OK (12 tests, 81 assertions) |
| 7 | Micro-HIS | `run.php`, `pdo.php`, `repository.php`, `http.php` y `casos.php` | Todas exit 0 |
| 8 | Prototipo | `node docs/catalogo-medicamentos/semana-11/evidencia/verificar-prototipo.mjs` | 24/24 |
| 9 | Base de prueba local | PostgreSQL 17 desechable (`initdb` en 55431/55433, `btree_gist` en HOSPITAL) y `php vendor/bin/phpunit` | OK (121 tests, Unit, Feature y Database) |
| 10 | Identidad real | `php artisan serve` con CENTRAL desechable migrada y `JWT_SECRET` efímero | `tenant`, `jwt.auth` y RBAC reales: 401, 403 `TENANT_MISMATCH`, 403 `RBAC_DENIED` auditado y 503 sin tabla H5 ([detalle](../semana-16/integracion.md)) |

## Errores encontrados y corregidos en el proceso

| Error | Corrección |
|---|---|
| `bootstrap/cache must be present and writable` | `attrib -R` (paso 2) |
| `MissingAppKeyException` | `php artisan key:generate` |
| `ViteManifestNotFoundException` en `ExampleTest` | `npm run build` |
| `Class "App\Models\Tenant" not found` al ejecutar el API | Resuelto al integrarse Tenants (#657). Sin la base CENTRAL las denegaciones dan 503; las pruebas sustituyen el registro de denegaciones |
| `Secret is not set` al iniciar sesión | `JWT_SECRET` efímero en el entorno del proceso; no se guarda |

## Pendiente para un despliegue real

1. Entorno staging u on-premise con PostgreSQL HOSPITAL y CENTRAL (credenciales fuera del repositorio).
2. Migración de `medication` del slice H5 y semillas de prueba aprobadas.
3. ~~Permisos de escritura aprobados por RBAC~~: resuelto con #627, que los concede solo a `admin_hospital`.
4. Repetir los pasos 5–10 contra ese entorno y registrar las salidas.
