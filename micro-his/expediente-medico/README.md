# Micro-HIS · Expediente médico electrónico base

Flujo: **apertura y consulta autorizada del expediente longitudinal**. PHP 8.2+ vanilla (sin framework ni Composer),
capas Presentation / Application / Domain / Persistence + Integration, MVC, patrón Repository, PDO con sentencias
preparadas y adaptadores PDO e InMemory. Documentación por semana: [`docs/expedientes-medicos/`](../../docs/expedientes-medicos/README.md).

## Qué es real, qué es provisional y qué está pendiente

| Elemento | Estado | Detalle |
|---|---|---|
| Tablas `medical_records`, `care_episodes`, `care_assignments`, `doctor_profiles`, `local_patients` | **Real (lectura/escritura vía PDO)** | Migraciones hospital 000100, 000200 y 000400. El módulo solo escribe en `medical_records`. No se creó ni modificó ninguna migración. |
| Motor RBAC `MicroHis\Rbac\Application\Authorize` + matriz activa de `config/rbac.php` | **Real** | Adaptador `Integration/RbacAccessAuthorizer`. `expediente.ver` existe y está activo para superadmin, medico y enfermeria. |
| Bitácora `MicroHis\Gobernanza\Application\RecordAuditEvent` (`PdoAuditLog` / `AuditLogger`) | **Real** | Adaptador `Integration/GobernanzaClinicalAudit`; criticidad Security; eventos `expediente_medico.<accion>`. |
| Pacientes `MicroHis\Pacientes\Application\PatientRepository::findByCode` | **Real** | Adaptador `Integration/PacientesDirectory` (con `PdoPatientRepository` o `InMemoryPatientRepository`). |
| Identidad en `X-User-ID` y membresías en `database/demo-memberships.php` | **Provisional** | El micro-HIS no tiene JWT. En Laravel las aportan Auth (JWT) y `hospital_memberships`. |
| Permiso de apertura | **Propuesta pendiente (RBAC/María)** | No existe en el catálogo. Con la matriz real, toda apertura responde 403. El prototipo y las pruebas que lo declaran lo activan con `expediente_propuesta_apertura()`. |
| Adaptador Laravel `GET /api/v1/patients/{code}/medical-record` | **Implementado, probado y registrado** | `app/ExpedienteMedico/` (ServiceProvider + controlador, patrón de Traslados): `tenant`, `jwt.auth`, `rbac:expediente.ver`, `Authorize` y `RecordAuditEvent` del contenedor. Probado en `tests/Feature/ExpedienteMedico`. Registrado en `bootstrap/providers.php` (autorizado por Cindy el 2026-10-23). La apertura no se publica en Laravel hasta que exista su permiso. |
| Identidad CENTRAL → `local_user_refs` | **Real (reutilizado)** | `App\TrasladosAltas\ResolutorActorLocal` del módulo Traslados y altas; el controlador separa identidad (RBAC/auditoría) y referencia local (vínculo). |
| PostgreSQL | **No verificado** | Las pruebas PDO usan SQLite con `database/schema.sql` (mismos nombres de tabla y columna). No hay PostgreSQL disponible en este entorno. |

## Estructura

```text
app.php                      raíz de composición (expediente_router, expediente_rbac, expediente_audit, expediente_pdo_stores)
bootstrap.php                autocarga + bootstraps de rbac, gobernanza-auditoria y pacientes
openapi.yaml                 contrato HTTP
database/schema.sql          esquema SQLite de prueba (subconjunto fiel de la base HOSPITAL)
database/demo.sql            datos FICTICIOS del prototipo y de las pruebas
database/demo-memberships.php membresías ficticias usuario → hospital → rol
public/index.php             front controller del prototipo; public/ui/ interfaz (HTML + JS + CSS)
src/Domain                   MedicalRecord, CareEpisode, LongitudinalRecord, PatientSummary, reglas
src/Application              casos de uso (OpenMedicalRecord, ConsultLongitudinalRecord), AccessGuard y puertos
src/Persistence              adaptadores PDO e InMemory, HospitalRegistry (aislamiento por tenant)
src/Integration              adaptadores a RBAC, Gobernanza y Pacientes; reloj del sistema
src/Presentation             Router, MedicalRecordController, LongitudinalPresenter, JsonView, Request
tests/                       run.php (unitarias, caja blanca, caja negra, integración PDO, arquitectura) y ui-estados.test.mjs
```

## Ejecutar

```bash
php micro-his/expediente-medico/tests/run.php
node --test micro-his/expediente-medico/tests/ui-estados.test.mjs
php vendor/bin/phpunit tests/Feature/ExpedienteMedico
php -S 127.0.0.1:8096 -t micro-his/expediente-medico/public micro-his/expediente-medico/public/index.php
```

Con el servidor activo, abrir `http://127.0.0.1:8096/ui/`. Las bases SQLite del prototipo se crean en la carpeta
temporal del sistema (`expediente-demo-hospital.sqlite` y `expediente-demo-audit.sqlite`). Para volver a los datos
iniciales, detener el servidor y borrar esos dos archivos. `EXPEDIENTE_PROPUESTA_APERTURA=0` muestra el 403 real de
la apertura y `EXPEDIENTE_DEMO_AUDIT=caida` simula la bitácora caída.

## Códigos de respuesta

| HTTP | `code` | Cuándo |
|---|---|---|
| 200 / 201 | — | Consulta con datos; apertura (201 creada, 200 ya existía) |
| 400 | `TENANT_REQUERIDO` | Falta `X-Tenant-ID` o tiene formato inválido |
| 401 | `IDENTIDAD_REQUERIDA` | Falta `X-User-ID` o tiene formato inválido |
| 403 | `ACCESO_DENEGADO` | RBAC niega el permiso en ese hospital (incluye `trace_id`) |
| 403 | `SIN_VINCULO_ASISTENCIAL` | Sin asignación activa con el paciente, o paciente inexistente (no se revela) |
| 404 | `EXPEDIENTE_NO_ABIERTO` / `PACIENTE_NO_ENCONTRADO` | Consulta sin expediente / apertura de paciente inexistente |
| 405 | `METODO_NO_PERMITIDO` | Otro método sobre el recurso (no hay borrado) |
| 409 | `PACIENTE_NO_ACTIVO` | Apertura de paciente inactivo o fallecido |
| 422 | `CODIGO_PACIENTE_INVALIDO` | Código fuera de `^[A-Za-z0-9][A-Za-z0-9_-]{0,39}$` |
| 503 | `HOSPITAL_NO_DISPONIBLE` / `EXPEDIENTE_NO_DISPONIBLE` / `AUDITORIA_NO_DISPONIBLE` | Hospital sin base, base caída, o bitácora caída (no se entregan datos y la apertura se revierte) |
| 500 | `ERROR_INTERNO` / `*_INCONSISTENTE` | Error inesperado o datos que violan una regla de integridad (no se muestran) |
