# Cierre verificable ASII-19

| Riesgo | Control | Evidencia |
|---|---|---|
| Tenant ajeno | `EnsureHospitalDatabaseTenant` + tenant autenticado | `LabResultsPermissionMatrixTest`: otro hospital → 403; base sin asignar → 503 |
| Rol sin permiso | `rbac:lab-results.read/create/correct` | Matriz: `bioquimico`, `recepcion` y `medico` → 403 en captura/corrección; `recepcion`/`medico` → 403 en consulta; sin escrituras |
| Identidad ausente | `jwt.auth` | Matriz: sin identidad → 401 |
| Auditoría incompleta | cambio y auditoría en la misma transacción | `LabResultsPostgresTest`: 2 eventos en `local_access_audits`; sin actor local → 403 sin escritura |
| Detalles internos expuestos | respuesta genérica en 5xx | Matriz: error de base → 500 sin `SQLSTATE` ni nombres de tablas |
| Secreto expuesto | configuración fuera del repositorio | Revisión del diff: sin credenciales |

## Bitácora de despliegue local (base desechable)

| Campo | Valor |
|---|---|
| Fecha | 2026-10-29 |
| Código | develop `56026d89` (incluye #1297, #1298 y #1299) + pruebas de este PR `36bf217f` |
| Entorno | PHP 8.2.12, PostgreSQL 17.10 desechable local (bases `his_central_test` y `his_hospital_test` recreadas) |
| Migraciones | `composer migrate:central`, `composer migrate:framework`, `composer migrate:hospital` |
| Pruebas | `phpunit --filter 'LabResultsPermissionMatrixTest\|LabResultsPostgresTest' tests/Database/Hospital` → 5 pruebas, 65 aserciones, OK |
| Frontend | `npm run build` OK sobre el mismo código de develop |

Es un despliegue local con base desechable; no se declara staging ni producción.
