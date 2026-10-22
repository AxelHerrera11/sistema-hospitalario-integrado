# Matriz de verificación de requisitos de datos (Semana 2)

Traza cada requisito con la restricción real del esquema y la prueba que lo verifica.
Sirve de control para la auditoría de la semana 7 y para el plan SQA de la semana 14.

| Requisito | Restricción o trigger en el esquema | Prueba en `tests/Database` | Estado |
|---|---|---|---|
| RF-D-01 Integridad referencial local | `ON DELETE RESTRICT` en todas las FK HOSPITAL | `CareIntegrityTest`, `ProfessionalIdentityIntegrityTest` | Implementado |
| RF-D-02 No solapamiento temporal | `bed_stays_bed_active_period_exclusion`, `appointments_doctor_active_period_exclusion`, `appointments_patient_active_period_exclusion` | `SchedulingAndBedIntegrityTest` | Implementado |
| RF-D-03 Unicidad del activo equivalente | `care_assignments_active_equivalent_unique`, `allergies_active_equivalent_unique` | `CareIntegrityTest`, `MedicalRecordIntegrityTest` | Implementado |
| RF-D-04 Identidad clínica obligatoria | trigger `enforce_clinical_note_integrity`, trigger `enforce_diagnosis_integrity` | `MedicalRecordIntegrityTest` | Implementado |
| RF-D-05 Inmutabilidad de lo firmado | trigger `clinical_notes_signed_immutable`, `clinical_notes_version_coherence_trigger` | `MedicalRecordIntegrityTest` | Implementado |
| RF-D-06 Coherencia estado-vigencia | `care_episodes_status_period_check`, `doctor_profiles_valid_period`, `bed_stays_period_check` | `CareIntegrityTest`, `SchedulingAndBedIntegrityTest` | Implementado |
| RF-D-07 Dominio de valores | `CHECK` de dominio en estados, sexos y tipos | `ProfessionalIdentityIntegrityTest`, `MedicalRecordIntegrityTest` | Implementado |
| RF-D-08 Aislamiento entre hospitales | dos conexiones `central` y `hospital`; sin FK entre bases | `ConnectionIsolationTest`, `MigrationLayoutTest` | Implementado |
| RF-D-09 Trazabilidad de acciones | append-only en `cross_hospital_access_audits`; falta `local_access_audits` | `CrossAccessAuditImmutabilityTest` (CENTRAL) | Parcial: H8 pendiente |
| RF-D-10 Idempotencia | único por clave en `central_inbox_messages` y `sync_checkpoints` | `CentralSyncIntegrityTest` | Parcial: H8 pendiente |
| RNF-D-01 Aislamiento físico | configuración de conexiones separadas | `ConnectionIsolationTest` | Implementado |
| RNF-D-02 Rendimiento de lectura | índices de `bed_stays`, `appointments` | Medición `EXPLAIN ANALYZE` (semana 11) | Pendiente de medición |
| RNF-D-03 Retención sin pérdida | triggers `*_retention` | Pruebas de rollback de slice | Implementado |
| RNF-D-04 Trazabilidad de esquema | `down` por migración y prueba de rollback | Pruebas de rollback H1–H4 | Implementado |
| RNF-D-05 Protección de datos sensibles | `central_users` sin contraseña; email único parcial | `NetworkIdentityIntegrityTest` | Implementado |
| RNF-D-06 Evidencia reproducible | arnés PostgreSQL local con dos bases | Toda la suite `tests/Database` | Implementado |

## Gaps detectados

1. **Slice H8 ausente.** `local_access_audits`, `outbox_events` e `inbox_messages` no
   tienen migración en `origin/develop`; mientras tanto RF-D-09 y RF-D-10 quedan
   cubiertos solo del lado CENTRAL.
2. **Sin línea base de rendimiento.** RNF-D-02 no se puede afirmar hasta medir con
   `EXPLAIN ANALYZE` en la semana 11.
3. **Entidades de laboratorio sin migración.** No hay restricciones que auditar para
   `lab_orders`, `lab_order_items`, `specimens`, `lab_result_versions` ni
   `critical_alerts`; su verificación queda en manos de sus dueños con las reglas de la
   semana 7.
