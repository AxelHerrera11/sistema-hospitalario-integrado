# Dependencias del módulo

## Dependencias reales (código existente en el repositorio)

| Dependencia | Responsable | Contrato usado | Cómo se usa | Modificada |
|---|---|---|---|---|
| `micro-his/rbac` | María | `Application\Authorize::execute(userId, hospitalId, permission)` → `allowed/reason/trace_id`; `InMemoryRbacStore`; `RoleGrantsPermission` | `Integration/RbacAccessAuthorizer` | No |
| `config/rbac.php` (clave `roles`) | María | Matriz activa rol → permisos | `Integration/RbacCatalog::activeRolePermissions` (solo lectura; ignora `pending`) | No |
| `micro-his/gobernanza-auditoria` | Cindy | `Application\RecordAuditEvent::record(...)`, `Domain\AuditLogger`, `Persistence\PdoAuditLog`, `AuditCriticality::Security` | `Integration/GobernanzaClinicalAudit` | No |
| `micro-his/pacientes` | Madelin | `Application\PatientRepository::findByCode`, `Persistence\PdoPatientRepository`, `InMemoryPatientRepository` | `Integration/PacientesDirectory` | No |
| Middlewares `tenant`, `jwt.auth`, `rbac:` (`App\Rbac\RequirePermission`) | Cindy (Auth) y María (RBAC) | Alias de middleware y formato 401/403 (`RBAC_DENIED`, `TENANT_MISMATCH`) | Ruta del `ExpedienteMedicoServiceProvider` (Semana 5) | No |
| `App\TrasladosAltas\ResolutorActorLocal` | Traslados y altas (ASII-08) | `referencia(centralUserId)` → `local_user_ref_id` | `ExpedienteMedicoController` (identidad del JWT → referencia local) | No |
| Migraciones hospital 000100, 000200, 000400 | Javier | Tablas y columnas reales | `PdoMedicalRecordRepository`, `PdoCareRelationship`, `database/schema.sql` (copia SQLite) | No |

## Dependencias provisionales (solo para ejecutar sin el sistema completo)

| Elemento | Por qué existe | Qué lo reemplaza |
|---|---|---|
| Cabecera `X-User-ID` | El micro-HIS no tiene JWT | En Laravel, Auth (JWT) + `ResolutorActorLocal`; implementado en `app/ExpedienteMedico` y registrado en `bootstrap/providers.php` |
| `database/demo-memberships.php` | No hay sesión ni `hospital_memberships` en el micro-HIS | Membresías reales de CENTRAL vía RBAC |
| `database/schema.sql` (SQLite) | No hay PostgreSQL en el entorno de pruebas | Base HOSPITAL real |
| Dobles `InMemory*` y `RecordingAuditLogger` | Pruebas aisladas | Adaptadores PDO / `PdoAuditLog` |

## Dependencias inversas (quién espera al expediente según el plan de integración)

Axel (alergias, admisión), Josué (signos vitales, notas SOAP), Javier (órdenes) y María (prescripciones). Este
módulo les ofrece el contrato `openapi.yaml`; ninguno de esos módulos fue modificado.
