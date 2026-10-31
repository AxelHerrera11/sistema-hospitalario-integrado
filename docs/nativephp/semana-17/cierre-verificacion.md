# Cierre verificable ASII-29

| Riesgo | Control | Evidencia |
|---|---|---|
| Acción sin permiso | `rbac:lab-results.read/create` antes de preparar o sincronizar | `NativePhpPermissionMatrixTest`: `bioquimico`, `recepcion` y `medico` → 403 al preparar; `bioquimico`/`recepcion` → 403 al sincronizar; sin escrituras |
| Datos de otro hospital | `EnsureHospitalDatabaseTenant` + tenant autenticado | Matriz: otro hospital → 403; base sin asignar → 503; sin identidad → 401 |
| Falsa sincronización | respuesta `simulado` sin gateway remoto | `NativePhpActionsPostgresTest`: la acción sigue en `pendiente_sincronizar` tras sincronizar |
| Detalles internos expuestos | respuesta genérica en 5xx | Matriz: error de base → 500 sin `SQLSTATE` ni nombres de tablas |
| Secretos locales | configuración fuera del repositorio | Revisión del diff: sin credenciales |

## Bitácora de despliegue local (base desechable)

| Campo | Valor |
|---|---|
| Fecha | 2026-10-29 |
| Código | develop `208049de` (incluye #1297–#1301) + pruebas de este PR `b6a15a53` |
| Entorno | PHP 8.2.12, PostgreSQL 17.10 desechable local (bases `his_central_test` y `his_hospital_test` recreadas) |
| Migraciones | `composer migrate:central`, `composer migrate:framework`, `composer migrate:hospital` |
| Pruebas | `phpunit --filter 'NativePhpPermissionMatrixTest\|NativePhpActionsPostgresTest' tests/Database/Hospital` → 5 pruebas, 61 aserciones, OK |
| Frontend | `npm run build` OK sobre el código de las semanas 16 integradas |

Es un prototipo integrado en el HIS web con base desechable; no se declara una aplicación NativePHP distribuida ni staging/producción.
