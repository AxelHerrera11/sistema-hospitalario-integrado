# Informe de auditoría de migraciones (Semana 7)

Auditoría ejecutada sobre las migraciones integradas en `origin/develop` (`af6c1d3`).
Los conteos provienen de consultas reales a `pg_constraint` y `pg_class` sobre dos
bases PostgreSQL 16 de prueba (una CENTRAL y una HOSPITAL).

## 1. Método

1. Se aplicaron las migraciones de `database/migrations/central/` y
   `database/migrations/hospital/` sobre bases vacías.
2. Se consultó el catálogo del sistema para contar, por tabla, claves primarias,
   foráneas, `CHECK`, unicidades y exclusiones.
3. Se verificó que cada columna foránea tenga índice y que ninguna FK use cascada.
4. Se listaron las columnas nullable sin valor por defecto y se contrastó cada una
   con su `CHECK` o con la semántica de la entidad.
5. Se enumeraron los triggers de integridad y de retención.

## 2. Resumen por migración

| Migración | Tablas | PK | FK | CHECK | UNIQUE | EXCLUDE | Triggers | Veredicto |
|---|---:|---:|---:|---:|---:|---:|---:|---|
| central `000000` redes | `networks`, `hospitals` | 2 | 1 | 4 | 2 | 0 | 0 | Correcta |
| central `000100` identidad y membresías | `central_users`, `hospital_memberships` | 2 | 2 | 5 | 4 | 0 | 0 | Correcta |
| central `000200` MPI | `mpi_patients`, `mpi_identifiers`, `patient_hospital_links`, `identity_match_candidates` | 4 | 7 | 15 | 5 | 0 | 0 | Correcta |
| central `000300` consentimientos y acceso | `patient_consents`, `cross_hospital_access_grants`, `cross_hospital_access_audits`, `break_glass_reviews` | 4 | 15 | 20 | 6 | 0 | 2 | Correcta |
| central `000400` sincronización | `sync_nodes`, `sync_checkpoints`, `central_inbox_messages` | 3 | 3 | 15 | 6 | 0 | 0 | Correcta |
| hospital `000000` extensiones | — | — | — | — | — | — | 1 | Correcta (`btree_gist`) |
| hospital `000100` identidad local y profesionales | `local_user_refs`, `specialties`, `doctor_profiles`, `doctor_specialties` | 4 | 3 | 6 | 5 | 2 | 0 | Correcta |
| hospital `000200` pacientes y cuidados | `local_patients`, `care_episodes`, `care_assignments` | 3 | 5 | 14 | 4 | 0 | 2 | Correcta |
| hospital `000300` agenda y camas | `appointments`, `wards`, `beds`, `bed_stays` | 4 | 6 | 6 | 3 | 3 | 0 | Correcta |
| hospital `000400` expediente | `medical_records`, `clinical_notes`, `diagnoses` | 3 | 8 | 13 | 3 | 0 | 4 | Correcta |
| hospital `000401` alergias y signos | `allergies`, `vital_signs` | 2 | 6 | 22 | 0 | 0 | 2 | Correcta |

Totales medidos: 15 tablas CENTRAL con 28 FK y 15 tablas HOSPITAL con 29 FK; **las 57
FK usan `ON DELETE RESTRICT`** y **las 57 columnas foráneas tienen índice**. Hay 15
triggers de integridad y retención en HOSPITAL y 2 en CENTRAL.

## 3. Hallazgos

### H-01 · Bloqueante · Falta `doctor_schedules` (propuesta de la 28.ª entidad)

- **Situación:** el módulo de médicos necesita el horario de atención para calcular
  disponibilidad (lo consumen Citas y Admisión). La tabla **no existe** y no está en
  la especificación de 27 entidades.
- **Impacto:** sin `doctor_schedules` no se puede calcular disponibilidad ni garantizar
  que un médico no tenga dos horarios solapados.
- **Propuesta:** crear `doctor_schedules` con la definición entregada por el módulo de
  médicos, con exclusión GiST que impida solapamientos por médico, día y vigencia.
- **Estado:** `PENDIENTE DE APROBACIÓN`. La especificación pasaría de 27 a 28
  entidades; el cambio se registra en
  [registro-coordinacion-doctor-schedules.md](./registro-coordinacion-doctor-schedules.md)
  y se tramita en un PR técnico independiente, con la excepción documentada que permite
  el plan de integración.
- **Responsable de la migración:** por acordar entre el módulo de médicos (Josué) y el
  módulo de verificación de BD (Javier).

### H-02 · Mayor · Slice H8 sin migraciones

- **Situación:** `local_access_audits`, `outbox_events` e `inbox_messages` (base
  HOSPITAL) no tienen migración.
- **Impacto:** RF-D-09 y RF-D-10 (trazabilidad local e idempotencia de eventos) solo
  están cubiertos del lado CENTRAL; la auditoría local del hospital y la publicación
  de eventos no se pueden escribir.
- **Propuesta:** migración única del slice H8 con auditoría append-only y outbox con
  clave de idempotencia, más sus pruebas de integridad.
- **Estado:** `PENDIENTE`. Dueño por confirmar entre auditoría (Cindy, Boris) y
  sincronización (Cindy).

### H-03 · Mayor · Entidades de laboratorio, alertas y medicamentos sin migración

- **Situación:** `medications`, `prescriptions`, `lab_tests`, `lab_orders`,
  `lab_order_items`, `specimens`, `lab_result_versions` y `critical_alerts` no tienen
  migración en `develop`.
- **Impacto:** los módulos de órdenes, resultados, alertas y prescripciones no pueden
  pasar de adaptador en memoria a adaptador real; es el bloque que impide la
  integración del miércoles 21.
- **Propuesta:** cada dueño publica su migración con las reglas de la sección 4 de
  este informe y la verificación de BD valida cada una.
- **Estado:** `PENDIENTE` por módulo.

### H-04 · Menor · Texto libre sin longitud máxima

- **Situación:** `local_patients.contact`, `allergies.reaction`, `clinical_notes.content`,
  `care_episodes.reason`, `bed_stays.reason` y `appointments.reason` aceptan texto de
  longitud arbitraria.
- **Impacto:** riesgo de valores absurdos y de payload grande sin control; no afecta
  la integridad referencial.
- **Propuesta:** `CHECK (char_length(...) <= N)` con un límite acordado por módulo.
- **Estado:** `PROPUESTA` en [migracion-correctiva-propuesta.sql](./migracion-correctiva-propuesta.sql).

### H-05 · Menor · `diagnoses.clinical_note_id` admite diagnóstico sin nota

- **Situación:** la columna es nullable y no hay `CHECK` que obligue a referenciar una
  nota cuando el diagnóstico se declara dentro de una nota clínica.
- **Impacto:** permite diagnósticos huérfanos; puede ser intencional paraWL diagnósticos
  de resultado de laboratorio.
- **Propuesta:** mantenerlo nullable y documentar que el diagnóstico de laboratorio no
  exige nota; o exigir nota para diagnósticos de consulta.
- **Estado:** `PENDIENTE DE DECISIÓN` del módulo de SOAP y diagnósticos.

### H-06 · Informativo · Columnas nullable justificadas

Las 32 columnas nullable sin valor por defecto tienen razón explícita: `ended_at` y
`resolved_at` (estado abierto o cerrado), `signed_at` y `validated_at` (coherencia con
el estado), `supersedes_id` y `correction_reason` (cadena de corrección), `valid_to`
(vigencia abierta), `grant_id` (obligatorio solo con fuente `cross_hospital_grant`),
las columnas de medición de signos vitales (al menos una por registro según
`vital_signs_measurement_check`), `allergen_code` y `allergen_name` (al menos uno no
vacío según `allergies_allergen_check`) y `local_patients.contact` (contacto
opcional). **No es un defecto**: se documenta para que nadie lo "corrija" con un
`NOT NULL` invisible.

### H-07 · Informativo · `beds.status` es estado operativo, no fuente de ocupación

`beds.status` admite `available`, `occupied`, `maintenance` e `inactive` y puede
contradecir el intervalo de `bed_stays`. La ocupación se deriva del intervalo; el
mismatch se informa como inconsistencia. Ninguna migración debe "sincronizar" ambos
valores automáticamente.

## 4. Reglas que debe cumplir toda migración nueva

1. Tabla en `database/migrations/central/` o `database/migrations/hospital/`, nunca en
   la raíz.
2. PK `uuid` con `gen_random_uuid()`.
3. FK locales con `ON DELETE RESTRICT` e índice en la columna referenciada.
4. Instantes en `timestamptz`, fechas de negocio en `date`.
5. Estados en `text` con `CHECK` de dominio, no enums.
6. Unicidad parcial cuando la regla sea "activo a la vez".
7. Exclusión GiST para intervalos que no pueden solaparse.
8. Registro clínico: trigger de inmutabilidad y de retención.
9. Prueba de integridad escrita antes de la migración, ejecutada sobre PostgreSQL real.
10. `down` reversible y probado, y menos de 400 líneas por PR.

## 5. Migración correctiva propuesta

Las correcciones de H-04 y H-05 están en
[migracion-correctiva-propuesta.sql](./migracion-correctiva-propuesta.sql). **No se
aplican en este PR**: el número de archivo de la migración debe acordarse con el
grupo para no colisionar con el slice H5 (medicamentos), H6 (laboratorio) y H7
(auditoría y sincronización).

## 6. Validación ejecutada

```bash
# comando: php artisan migrate --database=central --path=database/migrations/central --realpath --force
# resultado: 5 migraciones aplicadas (networks, central_users, mpi, consentimientos, sincronizacion)

# comando: php artisan migrate --database=hospital --path=database/migrations/hospital --realpath --force
# resultado: 6 migraciones aplicadas sobre PostgreSQL 16

# comando: SELECT confdeltype, count(*) FROM pg_constraint WHERE contype='f' GROUP BY 1
# resultado: 57 filas con confdeltype = 'r' (RESTRICT); ninguna FK en cascada

# comando: SELECT conname, pg_get_constraintdef(oid) FROM pg_constraint WHERE conrelid='vital_signs'::regclass AND contype='c'
# resultado: 16 CHECK de dominio y rango en vital_signs

# comando: SELECT count(*) FROM pg_trigger WHERE NOT tgisinternal
# resultado: 15 triggers en HOSPITAL y 2 en CENTRAL (integridad, inmutabilidad y retencion)

# comando: podman run --rm --network host -v $PWD:/app -w /app localhost/php85-pgsql-full php vendor/bin/phpunit tests/Database
# resultado: OK (53 tests, 679 assertions)
```
