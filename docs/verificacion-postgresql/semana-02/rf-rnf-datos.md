# Requisitos de datos — RF/RNF con criterio de aceptación (Semana 2)

## 1. Requisitos funcionales de datos (RF)

### RF-D-01 Integridad referencial local

- **Enunciado:** toda FK entre entidades clínicas apunta a una entidad de la misma base
  HOSPITAL y usa `ON DELETE RESTRICT`.
- **Criterio de aceptación:** no existe ninguna FK que apunte a la base CENTRAL; borrar
  un padre con hijos falla con `SQLSTATE 23503`.
- **Restricción:** `REFERENCES ... ON DELETE RESTRICT` en las 16 tablas HOSPITAL.
- **Estado:** Implementado.

### RF-D-02 Integridad temporal sin solapamiento

- **Enunciado:** una misma cama, un mismo médico o un mismo paciente no puede tener dos
  intervalos activos simultáneos.
- **Criterio de aceptación:** insertar dos `bed_stays` activos en la misma cama y
  vigencias que se cruzan falla; dos intervalos adyacentes `[a,b)` y `[b,c)` se aceptan.
- **Restricción:** `bed_stays_bed_active_period_exclusion`,
  `appointments_doctor_active_period_exclusion`,
  `appointments_patient_active_period_exclusion` (exclusión GiST con `btree_gist`).
- **Estado:** Implementado y cubierto por `SchedulingAndBedIntegrityTest`.

### RF-D-03 Unicidad de la entidad activa equivalente

- **Enunciado:** no se repiten asignaciones, alergias ni hechos clínicos equivalentes
  que estén activos al mismo tiempo.
- **Criterio de aceptación:** dos filas con la misma clave lógica y estado activo en la
  misma vigencia violan el índice único parcial.
- **Restricción:** `care_assignments_active_equivalent_unique`,
  `allergies_active_equivalent_unique` (índice único parcial).
- **Estado:** Implementado.

### RF-D-04 Obligatoriedad de la identidad clínica

- **Enunciado:** una nota clínica, un diagnóstico o una alergia no pueden existir sin
  paciente, episodio y autor válidos.
- **Criterio de aceptación:** insertar una nota sin `care_episode_id` o con un autor sin
  asignación activa es rechazado por trigger.
- **Restricción:** trigger `enforce_clinical_note_integrity` y `enforce_diagnosis_integrity`.
- **Estado:** Implementado.

### RF-D-05 Inmutabilidad de lo firmado

- **Enunciado:** una nota firmada o un registro clínico finalizado no se modifica; solo se
  corrige mediante una versión posterior.
- **Criterio de aceptación:** un `UPDATE` sobre una nota `signed` lanza excepción; una
  corrección exige `supersedes_id` con versión coherente.
- **Restricción:** trigger `clinical_notes_signed_immutable` y la coherencia de versión.
- **Estado:** Implementado.

### RF-D-06 Coherencia de estado y vigencia

- **Enunciado:** el estado de una fila concuerda con sus fechas de vigencia.
- **Criterio de aceptación:** un episodio `open` con `ended_at`, o una vigencia con
  `valid_to <= valid_from`, son rechazados.
- **Restricción:** `care_episodes_status_period_check`,
  `doctor_profiles_valid_period`, `bed_stays_period_check`.
- **Estado:** Implementado.

### RF-D-07 Dominio de valores controlados

- **Enunciado:** los campos enumerados se validan en la base, no solo en la aplicación.
- **Criterio de aceptación:** un `sex`, `status` o `type` fuera de dominio es rechazado.
- **Restricción:** `CHECK` de dominio en `local_patients`, `care_episodes`,
  `vital_signs`, `allergies`, `bed_stays` y todas las tablas de estado.
- **Estado:** Implementado.

### RF-D-08 Aislamiento entre hospitales

- **Enunciado:** cada hospital tiene su propia base HOSPITAL; ningún dato clínico cruza
  de base.
- **Criterio de aceptación:** las tablas clínicas solo existen en la base HOSPITAL y no
  hay FK entre bases; la separación se resuelve por `X-Tenant-ID` y conexión.
- **Restricción:** dos conexiones Laravel (`central`, `hospital`) y una base por hospital.
- **Estado:** Implementado en la topología; verificación continua en la semana 7.

### RF-D-09 Trazabilidad de las operaciones

- **Enunciado:** toda acción clínica relevante deja rastro consultable.
- **Criterio de aceptación:** la bitácora y los eventos de salida son append-only y no se
  pueden modificar ni borrar.
- **Restricción:** `cross_hospital_access_audits` y `local_access_audits` con
  `append-only`; `outbox_events` e `inbox_messages` para la entrega de eventos.
- **Estado:** `cross_hospital_access_audits` implementada en CENTRAL;
  `local_access_audits`, `outbox_events` e `inbox_messages` **pendientes** de migración
  (slice H8).

### RF-D-10 Idempotencia y control de duplicados

- **Enunciado:** reintentar una operación no duplica efectos.
- **Criterio de aceptación:** el consumidor de eventos usa `inbox_messages` con clave de
  idempotencia y el emisor usa `outbox_events` con clave única.
- **Restricción:** índices únicos de `central_inbox_messages` y `sync_checkpoints`.
- **Estado:** CENTRAL implementado; H8 pendiente.

## 2. Requisitos no funcionales de datos (RNF)

### RNF-D-01 Aislamiento físico de datos clínicos

- **Criterio de aceptación:** los datos de un hospital no son visibles en la base de
  otro; ninguna consulta clínica usa la conexión `central`.
- **Verificación:** `ConnectionIsolationTest` y `MigrationLayoutTest`.

### RNF-D-02 Tiempo de respuesta de las consultas de ocupación y agenda

- **Criterio de aceptación:** las consultas de lectura sobre `bed_stays` y
  `appointments` usan los índices declarados y no llegan a secuencial sobre el catálogo
  completo.
- **Verificación:** medición real con `EXPLAIN ANALYZE` en la semana 11.

### RNF-D-03 Retención sin pérdida de historia clínica

- **Criterio de aceptación:** los registros clínicos no se borran por cascada; la
  retención se aplica con trigger que lanza excepción.
- **Restricción:** triggers `medical_records_retention`, `clinical_notes_retention`,
  `allergies_retention`, `vital_signs_retention`, `care_assignments_retention`.
- **Estado:** Implementado.

### RNF-D-04 Trazabilidad de cambios de esquema

- **Criterio de aceptación:** toda migración tiene su `down` reversible y una prueba de
  integridad que demuestra el rollback.
- **Verificación:** pruebas de rollback de los slices H1–H4 en `tests/Database`.

### RNF-D-05 Protección de datos sensibles

- **Criterio de aceptación:** `central_users` no almacena contraseñas; los identificadores
  se guardan en claro controlado y el acceso se audita.
- **Restricción:** `central_users.email` opcional y único parcial; sin columna de
  contraseña.

### RNF-D-06 Evidencia reproducible de las restricciones

- **Criterio de aceptación:** cada restricción de este documento puede reproducirse con
  un comando sobre PostgreSQL real.
- **Verificación:** comandos en `README.md` y pruebas en `tests/Database`.
