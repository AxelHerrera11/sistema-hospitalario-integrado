# Correspondencia clase → tabla PostgreSQL (Semana 1)

Fuente: migraciones integradas en `origin/develop` (`af6c1d3`). Cada fila se comprobó
contra el archivo de migración indicado. La columna "restricciones" resume lo que la
base de datos garantiza; el detalle completo está en el informe de auditoría de la
semana 7.

## Identidad y pertenencia (CENTRAL)

| Clase | Tabla | Clave primaria | Columnas de negocio | Restricciones |
|---|---|---|---|---|
| `CentralUser` | `central_users` | `user_id` | `subject`, `email`, `display_name`, `status` | `subject` único; `email` opcional con índice único parcial; `status` en `active/inactive`; no guarda contraseña |
| `Hospital` | `hospitals` | `hospital_id` | `code`, `name`, `timezone`, `schema_version` | `code` único por red; `network_id` FK local `ON DELETE RESTRICT`; `metadata` JSONB objeto |
| `Network` | `networks` | `network_id` | `name`, `timezone`, `status` | `name` único |
| `HospitalMembership` | `hospital_memberships` | `membership_id` | `user_id`, `hospital_id`, `role_code`, `status` | FK locales `RESTRICT`; unicidad por usuario y hospital |
| `MpiPatient` | `mpi_patients` | `mpi_patient_id` | `canonical_name`, `status` | Índice único parcial sobre identificador canónico activo |
| `MpiIdentifier` | `mpi_identifiers` | `mpi_identifier_id` | `identifier_type`, `identifier_value` | Unicidad por tipo y valor; FK lógica a `mpi_patients` |
| `PatientHospitalLink` | `patient_hospital_links` | `patient_link_id` | `mpi_patient_id`, `hospital_id` | Unicidad por paciente y hospital |
| `IdentityMatchCandidate` | `identity_match_candidates` | `candidate_id` | `score`, `status`, `decided_at` | Coherencia de rango de puntuación y de estados |

## Kern clínico (HOSPITAL)

| Clase | Tabla | Clave primaria | Columnas de negocio | Restricciones |
|---|---|---|---|---|
| `Patient` | `local_patients` | `local_patient_id` | `global_id`, `code`, `given_name`, `family_name`, `birth_date`, `sex`, `status` | `code` y `global_id` únicos; `birth_date <= current_date`; `sex` y `status` con `CHECK` de dominio |
| `LocalUserRef` | `local_user_refs` | `local_user_ref_id` | `central_user_id`, `membership_id`, `role_code`, `status` | Referencias lógicas a CENTRAL; unicidad por `central_user_id` y `membership_id` |
| `DoctorProfile` | `doctor_profiles` | `doctor_profile_id` | `license_number`, `status`, `valid_from`, `valid_to` | FK a `local_user_refs`; `license_number` único; vigencia coherente |
| `Specialty` | `specialties` | `specialty_id` | `code`, `name`, `status` | `code` único |
| `DoctorSpecialty` | `doctor_specialties` | `doctor_specialty_id` | `is_primary`, `valid_from`, `valid_to` | FK locales; unicidad por médico y especialidad |
| `Appointment` | `appointments` | `appointment_id` | `starts_at`, `ends_at`, `status`, `reason` | Exclusión GiST de solapamiento por médico y por paciente; `ends_at > starts_at` |
| `CareEpisode` | `care_episodes` | `care_episode_id` | `type`, `status`, `started_at`, `ended_at`, `reason` | Coherencia estado/fecha de cierre; FK a `local_patients` |
| `CareAssignment` | `care_assignments` | `care_assignment_id` | `scope` JSONB, `source`, `status`, `valid_from`, `valid_to` | `scope` objeto no vacío; unicidad parcial de asignación activa equivalente; `grant_id` obligatorio solo con fuente `cross_hospital_grant` |
| `MedicalRecord` | `medical_records` | `medical_record_id` | `record_number`, `status`, `opened_at`, `closed_at` | Un expediente por paciente; `record_number` único;trigger de retención |
| `ClinicalNote` | `clinical_notes` | `clinical_note_id` | `note_type`, `status`, `version_no`, `content`, `signed_at` | Inmutable al firmar; versión única por expediente; trigger de coherencia episodio-paciente-autor |
| `Diagnosis` | `diagnoses` | `diagnosis_id` | `code`, `description`, `type`, `status` | FK a episodio y nota; trigger de integridad |
| `Allergy` | `allergies` | `allergy_id` | `allergen_code`, `allergen_name`, `allergy_type`, `severity`, `status`, `onset_date`, `resolved_at` | Al menos un alérgeno no vacío; unicidad de alergia activa equivalente; retención |
| `VitalSign` | `vital_signs` | `vital_sign_id` | `temperature`, `heart_rate`, `respiratory_rate`, `systolic_bp`, `diastolic_bp`, `oxygen_saturation`, `weight`, `measured_at`, `status` | Al menos una medida; rangos clínicos por `CHECK`; `recorded_at >= measured_at`; retención |
| `Ward` | `wards` | `ward_id` | `code`, `name`, `location`, `status` | `status` de dominio |
| `Bed` | `beds` | `bed_id` | `ward_id`, `code`, `status`, `is_active` | FK a `wards`; `code` único por sala; estado operativo no autoritativo |
| `BedStay` | `bed_stays` | `bed_stay_id` | `bed_id`, `care_episode_id`, `started_at`, `ended_at`, `reason` | Exclusión GiST de estancias activas por cama; unicidad de estancia abierta por episodio |

## Entidades de la especificación sin migración (propuestas, no implementadas)

| Clase propuesta | Tabla esperada | Estado |
|---|---|---|
| `Medication` | `medications` | Sin migración; dueño: catálogo de medicamentos |
| `Prescription` | `prescriptions` | Sin migración; dueño: prescripciones con validación de alergias |
| `LabTest` | `lab_tests` | Sin migración en `develop`; hay un PR abierto del equipo de laboratorio |
| `LabOrder` | `lab_orders` | Sin migración; contrato publicado, responsable por confirmar |
| `LabOrderItem` | `lab_order_items` | Sin migración; depende de `lab_orders` |
| `Specimen` | `specimens` | Sin migración; dueño: recepción de muestras |
| `LabResultVersion` | `lab_result_versions` | Sin migración; dueño: ingreso de resultados |
| `CriticalAlert` | `critical_alerts` | Sin migración en `develop`; hay un PR abierto del equipo de alertas |
| `LocalAccessAudit` | `local_access_audits` | Sin migración; dueño: auditoría y gobernanza |
| `OutboxEvent` | `outbox_events` | Sin migración; dueño: sincronización |
| `InboxMessage` | `inbox_messages` | Sin migración; dueño: sincronización |

## Nota sobre la nomenclatura

La especificación nombró las entidades en singular (`lab_order`, `bed_stay`) y las
migraciones crean las tablas en plural (`bed_stays`, `appointments`). En los
documentos de este módulo se usa el nombre de entidad de la especificación para
las clases y el nombre físico de tabla para el SQL, dejando la correspondencia
explícita en esta tabla.
