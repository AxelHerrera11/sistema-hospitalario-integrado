# Checklist de la revisión técnica formal (15 ítems)

Cada ítem se responde Sí / No / N.A. con la línea o prueba que lo sustenta. «No» genera un hallazgo.

| Id | Área | Pregunta | Dónde mirar |
|---|---|---|---|
| RT-01 | Reglas de dominio | ¿`MedicalRecord` y `CareEpisode` rechazan toda combinación de estado/fechas que la migración también rechaza? | `src/Domain`, migraciones 000200 y 000400, U-03/U-04 |
| RT-02 | Contrato API | ¿Cada código de `openapi.yaml` existe en el código y en las pruebas, y viceversa? | `openapi.yaml`, `MedicalRecordController::respond`, caja negra |
| RT-03 | Contrato API | ¿La respuesta 200 contiene solo los campos documentados (sin `contact`, `global_id`)? | `LongitudinalPresenter`, N-01 |
| RT-04 | Permisos | ¿Toda ruta Laravel lleva `tenant`, `jwt.auth` y `rbac:` con un permiso del catálogo? | `ExpedienteMedicoServiceProvider`, `PublishedContractsTest` |
| RT-05 | Permisos | ¿Ningún permiso propuesto aparece como activo en código, rutas ni documentos? | `ExpedientePermissions`, `config/rbac.php` |
| RT-06 | Tenant | ¿Es imposible leer la base de un hospital con la membresía de otro? | `HospitalRegistry`, P-08, N-05 |
| RT-07 | Acceso autorizado | ¿El vínculo asistencial usa la referencia local y RBAC/auditoría la identidad CENTRAL? | `RequestContext`, C8, Feature |
| RT-08 | Integridad | ¿Un expediente por paciente incluso con aperturas concurrentes? | `PdoMedicalRecordRepository::open`, P-03, A6 |
| RT-09 | Integridad | ¿Si la auditoría falla, la apertura se revierte y la consulta no entrega datos? | A5, C6, P-04, N-12 |
| RT-10 | Integridad | ¿La vista longitudinal rechaza episodios o expedientes de otro paciente? | `LongitudinalRecord`, U-06 |
| RT-11 | Persistencia | ¿Todo SQL es preparado y solo hay `INSERT` sobre `medical_records`? | `src/Persistence`, R-05 |
| RT-12 | Datos sensibles | ¿Ningún error, log ni evento de auditoría contiene nombre, motivo o SQL? | `ExpedienteRuleViolation`, `GobernanzaClinicalAudit`, N-04b, P-07b |
| RT-13 | Datos sensibles | ¿Las respuestas con datos llevan `Cache-Control: no-store`? | `JsonView`, Feature |
| RT-14 | Dependencias | ¿Se usan solo contratos públicos de RBAC, Gobernanza, Pacientes y Traslados, sin modificarlos ni duplicarlos? | `src/Integration`, `app/ExpedienteMedico`, R-06 |
| RT-15 | Dependencias | ¿Cada dependencia provisional (X-User-ID, membresías de demo, SQLite) está marcada y tiene reemplazo identificado? | README del micro-HIS, `semana-03/dependencias.md` |
