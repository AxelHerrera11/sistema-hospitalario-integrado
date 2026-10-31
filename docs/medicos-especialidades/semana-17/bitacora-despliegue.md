# Bitácora de despliegue

## Entorno disponible

Solo un **entorno local de verificación**: Windows 11, PHP 8.3.33, Composer, Node y Docker Desktop, en el clon de trabajo del estudiante. **No hay acceso a staging ni a on-premise**, y no se desplegó sobre infraestructura ajena. Las bases de PostgreSQL son contenedores efímeros sin datos reales.

## Registro (22/10/2026)

| # | Paso | Comando | Resultado |
|---|---|---|---|
| 1 | Dependencias PHP | `composer install --no-interaction` | Paquetes instalados; el autoload no se generó y se completó con `composer dump-autoload --no-scripts` |
| 2 | Dependencias y build del frontend | `npm ci` y `npm run build` | 117 paquetes; build correcto (130 módulos, `DoctorsPage` 8.91 kB) |
| 3 | Rutas | `php artisan route:list --path=api/v1` | Las tres rutas de médicos registradas (`DoctorApiController`) |
| 4 | Pruebas HTTP y de seguridad del módulo, sin base real | `php vendor/bin/phpunit tests/Feature/Medicos` | OK (21 tests, 242 assertions); incluye `DoctorSecurityTest` (10 tests, 200 assertions) |
| 5 | Micro-HIS | `php micro-his/medicos-especialidades/tests/core.php` y `tests/run.php` | Ambos: «Todas las pruebas pasaron» |
| 6 | Utilidades de la pantalla | `node` sobre el archivo de pruebas de la carpeta `modules` del frontend de médicos | OK |
| 7 | Bases de prueba | `docker run postgres:16` en los puertos 55431 (CENTRAL) y 55433 (HOSPITAL) y `CREATE EXTENSION btree_gist` | Aceptan conexiones |
| 8 | Esquema y pruebas contra PostgreSQL | `php -d extension=pdo_pgsql -d extension=pgsql vendor/bin/phpunit tests/Database/Hospital/DoctorsPostgresTest.php` (`migrate:fresh` del esquema HOSPITAL en cada prueba) | OK (9 tests, 33 assertions); incluye inyección SQL y campos desmesurados |
| 9 | Suite `Database` completa | `php -d extension=pdo_pgsql -d extension=pgsql vendor/bin/phpunit --testsuite Database` | 138 tests, 1 falla en `AuditChainIntegrityTest` (CENTRAL, ajena al módulo) |

## Errores encontrados y corregidos en el proceso

| Error | Corrección |
|---|---|
| `composer install` no dejó `vendor/autoload.php` | `composer dump-autoload --no-interaction --no-scripts` |
| El motor de Docker no respondía (Docker Desktop apagado) | Se inició Docker Desktop y se esperó a que el motor respondiera |
| `pdo_pgsql` y `pgsql` vienen deshabilitadas en el `php.ini` local | Se cargan por línea de comandos con `-d extension=...`, sin tocar el `php.ini` |
| Falla de `AuditChainIntegrityTest` (escritores concurrentes de CENTRAL) | **No corregida ni investigada**: ajena al módulo; confirmar en CI |

## Pendiente para un despliegue real

1. Entorno staging u on-premise con PostgreSQL HOSPITAL y CENTRAL (credenciales fuera del repositorio).
2. Semillas aprobadas de `specialties` y de `local_user_refs`: sin ellas el alta responde 422 `SPECIALTY_INACTIVE` y 403 `ACTOR_NOT_PROVISIONED`.
3. Una base HOSPITAL por hospital o enrutamiento de la conexión por tenant (amenaza MED-13).
4. Un almacén de caché compartido entre servidores (`CACHE_STORE`, hoy `database` en `.env.example`) para que el límite de tasa (MED-14) cuente igual en todos.
5. Repetir los pasos 3 a 9 contra ese entorno y registrar las salidas.
