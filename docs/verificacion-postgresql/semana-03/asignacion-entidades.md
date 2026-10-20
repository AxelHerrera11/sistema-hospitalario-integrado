# Asignación de entidades a cada base (Semana 3)

Inventario que respalda la vista de despliegue: qué tabla vive en qué base y quién
responde por ella. Es la base de la lista de tablas que el módulo de BD mantiene para
el grupo.

## CENTRAL (15 entidades, una sola base)

| Tabla | Entidad | Módulo responsable |
|---|---|---|
| `networks` | `network` | Verificación de BD (ya integrado) |
| `hospitals` | `hospital` | Verificación de BD (ya integrado) |
| `central_users` | `central_user` | Verificación de BD (ya integrado) |
| `hospital_memberships` | `hospital_membership` | Verificación de BD (ya integrado) |
| `mpi_patients` | `mpi_patient` | Verificación de BD (ya integrado) |
| `mpi_identifiers` | `mpi_identifier` | Verificación de BD (ya integrado) |
| `patient_hospital_links` | `patient_hospital_link` | Verificación de BD (ya integrado) |
| `identity_match_candidates` | `identity_match_candidate` | Verificación de BD (ya integrado) |
| `patient_consents` | `patient_consent` | Verificación de BD (ya integrado) |
| `cross_hospital_access_grants` | `cross_hospital_access_grant` | Verificación de BD (ya integrado) |
| `cross_hospital_access_audits` | `cross_access_audit` | Verificación de BD (ya integrado) |
| `break_glass_reviews` | `break_glass_review` | Verificación de BD (ya integrado) |
| `sync_nodes` | `sync_node` | Verificación de BD (ya integrado) |
| `sync_checkpoints` | `sync_checkpoint` | Verificación de BD (ya integrado) |
| `central_inbox_messages` | `central_inbox_message` | Verificación de BD (ya integrado) |

## HOSPITAL (27 entidades, una base por hospital)

| Tabla | Entidad | Estado | Módulo responsable |
|---|---|---|---|
| `local_user_refs` | `local_user_ref` | Integrada | Verificación de BD |
| `specialties` | `specialty` | Integrada | Verificación de BD |
| `doctor_profiles` | `doctor_profile` | Integrada | Verificación de BD |
| `doctor_specialties` | `doctor_specialty` | Integrada | Verificación de BD |
| `local_patients` | `local_patient` | Integrada | Verificación de BD |
| `care_episodes` | `care_episode` | Integrada | Verificación de BD |
| `care_assignments` | `care_assignment` | Integrada | Verificación de BD |
| `appointments` | `appointment` | Integrada | Verificación de BD |
| `wards` | `ward` | Integrada | Verificación de BD |
| `beds` | `bed` | Integrada | Verificación de BD |
| `bed_stays` | `bed_stay` | Integrada | Verificación de BD |
| `medical_records` | `medical_record` | Integrada | Verificación de BD |
| `clinical_notes` | `clinical_note` | Integrada | Verificación de BD |
| `diagnoses` | `diagnosis` | Integrada | Verificación de BD |
| `allergies` | `allergy` | Integrada | Verificación de BD |
| `vital_signs` | `vital_sign` | Integrada | Verificación de BD |
| `medications` | `medication` | Sin migración | Catálogo de medicamentos |
| `prescriptions` | `prescription` | Sin migración | Prescripciones con validación de alergias |
| `lab_tests` | `lab_test` | Sin migración | Catálogo de pruebas de laboratorio |
| `lab_orders` | `lab_order` | Sin migración | Órdenes de laboratorio (por confirmar) |
| `lab_order_items` | `lab_order_item` | Sin migración | Órdenes de laboratorio (por confirmar) |
| `specimens` | `specimen` | Sin migración | Recepción de muestras (por confirmar) |
| `lab_result_versions` | `lab_result_version` | Sin migración | Ingreso de resultados |
| `critical_alerts` | `critical_alert` | Sin migración | Alertas críticas |
| `local_access_audits` | `local_access_audit` | Sin migración | Auditoría y gobernanza |
| `outbox_events` | `outbox_event` | Sin migración | Sincronización |
| `inbox_messages` | `inbox_message` | Sin migración | Sincronización |

## Criterio de asignación

1. Un identificador de persona en la red (MPI, identidad, membresía) es **CENTRAL**.
2. Un dato clínico, una cama, una estancia o un resultado es **HOSPITAL**.
3. Una evidencia de auditoría de acceso es **CENTRAL** si es de red y **HOSPITAL** si
   es local del hospital; las dos excepciones son `cross_hospital_access_audits` y
   `local_access_audits`.
4. Un evento que cruza la frontera hospital-red se materializa con `outbox_events` en
   HOSPITAL y `central_inbox_messages` en CENTRAL.
5. `doctor_schedules` (horarios de atención), solicitada por el módulo de médicos,
   **no está en esta lista**: es la posible entidad 28 y requiere aprobación del grupo
   antes de migrarse.
