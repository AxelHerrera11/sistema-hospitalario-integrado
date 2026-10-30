# Cierre verificable ASII-20

| Riesgo | Control | Evidencia |
|---|---|---|
| Rol no bioquímico | `rbac:lab-results.validate` + rol activo del hospital | `LabValidationPermissionMatrixTest`: `tecnico_laboratorio`, `recepcion` y `medico` → 403 al validar o rechazar, sin escrituras |
| Tenant ajeno | `EnsureHospitalDatabaseTenant` + tenant autenticado | Matriz: otro hospital → 403; base sin asignar → 503; sin identidad → 401 |
| Decisión repetida | estado persistido y auditoría | `LabValidationPostgresTest`: segunda validación y rechazo posterior → 422; 2 eventos en `local_access_audits` |
| Detalles internos expuestos | respuesta genérica en 5xx | Matriz: error de base → 500 sin `SQLSTATE` ni nombres de tablas |
| Secreto expuesto | configuración fuera del repositorio | Revisión del diff: sin credenciales |

## Bitácora de despliegue local (base desechable)

| Campo | Valor |
|---|---|
| Fecha | 2026-10-29 |
| Código | develop `97497ecc` (incluye #1297–#1300) + pruebas de este PR `7a54a6ee` |
| Entorno | PHP 8.2.12, PostgreSQL 17.10 desechable local (bases `his_central_test` y `his_hospital_test` recreadas) |
| Migraciones | `composer migrate:central`, `composer migrate:framework`, `composer migrate:hospital` |
| Pruebas | `phpunit --filter 'LabValidationPermissionMatrixTest\|LabValidationPostgresTest' tests/Database/Hospital` → 4 pruebas, 56 aserciones, OK |
| Frontend | `npm run build` OK sobre el código de las semanas 16 integradas |

Es un despliegue local con base desechable; no se declara staging ni producción.
