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

## Errores encontrados y corregidos en el proceso

| Error | Corrección |
|---|---|
| `bootstrap/cache must be present and writable` | `attrib -R` (paso 2) |
| `MissingAppKeyException` | `php artisan key:generate` |
| `ViteManifestNotFoundException` en `ExampleTest` | `npm run build` |
| `Class "App\Models\Tenant" not found` al ejecutar el API | No corregible en el módulo: dependencia de Tenants; las pruebas sustituyen la identidad |

## Pendiente para un despliegue real

1. Entorno staging u on-premise con PostgreSQL HOSPITAL y CENTRAL (credenciales fuera del repositorio).
2. Migración de `medication` del slice H5 y semillas de prueba aprobadas.
3. Modelos `User` y `Tenant` (Tenants) para que `tenant` y `jwt.auth` funcionen.
4. Permisos de escritura aprobados y catalogados por RBAC.
5. Repetir los pasos 5–7 contra ese entorno y registrar las salidas.
