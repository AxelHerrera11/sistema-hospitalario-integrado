# Contrato de la tabla `doctor_schedules` (horarios de atención)

**Estado:** `PENDIENTE DE APROBACIÓN DEL GRUPO`
**Solicitante:** módulo de médicos y especialidades (ASII-04, `Jhos-hgnu`), necesario
para calcular la disponibilidad que consumen Citas (ASII-05) y Admisión (ASII-07).
**Publica:** módulo de verificación de base de datos (ASII-09, `Javifa2324`).

## 1. Por qué este documento

La tabla `doctor_schedules` **no figura** en la especificación vigente del esquema
federado, que hoy tiene 15 entidades CENTRAL y 27 HOSPITAL
(`openspec/changes/postgresql-federated-schema-rebuild/specs/hospital-clinical-schema/spec.md`).

Para poder crearla hay que **aprobar que la especificación pase de 27 a 28 entidades**.
Este PR entrega la definición, la migración reversible, las pruebas y el contrato
funcional, y deja la aprobación registrada como pendiente: **el número de entidades
de la especificación no se cambia en este PR**.

## 2. Definición de la tabla

```sql
CREATE TABLE doctor_schedules (
    doctor_schedule_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    doctor_profile_id  uuid NOT NULL REFERENCES doctor_profiles(doctor_profile_id) ON DELETE RESTRICT,
    specialty_id       uuid NOT NULL REFERENCES specialties(specialty_id) ON DELETE RESTRICT,
    weekday            smallint NOT NULL CHECK (weekday BETWEEN 1 AND 7),
    start_minute       smallint NOT NULL CHECK (start_minute BETWEEN 0 AND 1439),
    end_minute         smallint NOT NULL CHECK (end_minute BETWEEN 1 AND 1440),
    slot_minutes       smallint NOT NULL CHECK (slot_minutes BETWEEN 5 AND 240),
    valid_from         date NOT NULL,
    valid_to           date,
    status             text NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    created_at         timestamptz NOT NULL DEFAULT now(),
    updated_at         timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT doctor_schedules_block_check CHECK (end_minute > start_minute AND (end_minute - start_minute) % slot_minutes = 0),
    CONSTRAINT doctor_schedules_valid_period CHECK (valid_to IS NULL OR valid_to > valid_from),
    CONSTRAINT doctor_schedules_doctor_block_exclusion EXCLUDE USING gist (
        doctor_profile_id WITH =, weekday WITH =,
        int4range(start_minute, end_minute, '[)') WITH &&,
        daterange(valid_from, COALESCE(valid_to, 'infinity'::date), '[)') WITH &&
    ) WHERE (status = 'active')
);
CREATE INDEX doctor_schedules_doctor_profile_id_index ON doctor_schedules (doctor_profile_id);
CREATE INDEX doctor_schedules_specialty_id_index ON doctor_schedules (specialty_id);

-- Regla de especialidad (opción B acordada con ASII-04).
CREATE OR REPLACE FUNCTION doctor_schedules_require_specialty() RETURNS trigger
LANGUAGE plpgsql AS $$
BEGIN
    IF EXISTS (SELECT 1 FROM doctor_profiles WHERE doctor_profile_id = NEW.doctor_profile_id)
       AND EXISTS (SELECT 1 FROM specialties WHERE specialty_id = NEW.specialty_id)
       AND NOT EXISTS (
           SELECT 1
           FROM doctor_specialties s
           WHERE s.doctor_profile_id = NEW.doctor_profile_id
             AND s.specialty_id = NEW.specialty_id
             AND s.status = 'active'
             AND s.valid_from < COALESCE(NEW.valid_to, 'infinity'::date)
             AND NEW.valid_from < COALESCE(s.valid_to, 'infinity'::date)
       )
    THEN
        RAISE EXCEPTION 'doctor_schedules_specialty_guard: el medico % no tiene vigente la especialidad %',
            NEW.doctor_profile_id, NEW.specialty_id
            USING ERRCODE = '23514';
    END IF;

    RETURN NEW;
END;
$$;

CREATE TRIGGER doctor_schedules_specialty_guard
    BEFORE INSERT OR UPDATE ON doctor_schedules
    FOR EACH ROW EXECUTE FUNCTION doctor_schedules_require_specialty();
```

La definición es la que entrega el módulo de médicos, **conservada de forma literal**
y validada contra el esquema real:

| Punto | Verificación en `origin/develop` |
|---|---|
| `doctor_profiles(doctor_profile_id)` | Existe, PK `uuid` |
| `specialties(specialty_id)` | Existe, PK `uuid` |
| `doctor_specialties(doctor_profile_id, specialty_id)` | Existe, sin `UNIQUE` sobre el par (modelo por vigencias) |
| `btree_gist` en HOSPITAL | Habilitada por `000000_enable_hospital_extensions`; permite la exclusión con `int4range` y `daterange` |
| `gen_random_uuid()`, `timestamptz`, `text` con `CHECK` | Mismo patrón que el resto del esquema HOSPITAL |
| FK `ON DELETE RESTRICT` e índice por FK | Regla de verificación del módulo de BD |

**Modificaciones técnicas:** ninguna sobre la definición literal que entregó el módulo de
médicos. La única aclaración es que `weekday` usa la numeración ISO-8601 (**1 = lunes**) y que
el bloque horario se expresa en minutos desde medianoche, lo que evita el tipo `time` y
simplifica el cálculo de disponibilidad.

## 3. Contrato funcional

| Elemento | Regla |
|---|---|
| Identificador | `doctor_schedule_id` UUID |
| Médico | `doctor_profile_id` obligatorio, FK local, `ON DELETE RESTRICT` |
| Especialidad | `specialty_id` obligatorio: la cita lleva `specialty_id`, por eso cada bloque declara la especialidad en que se atiende |
| Regla de especialidad | Un horario solo existe si el médico tiene **vigente** esa especialidad en `doctor_specialties` durante la vigencia del horario. Lo garantiza el trigger `doctor_schedules_specialty_guard` (rechazo con SQLSTATE **23514**), no la aplicación |
| Día | `weekday` ISO-8601, de 1 (lunes) a 7 (domingo) |
| Bloque | `start_minute` a `end_minute` en minutos desde medianoche; el fin es exclusivo y siempre posterior al inicio |
| Turnos | `slot_minutes` divide exactamente la duración del bloque: `(end_minute - start_minute) % slot_minutes = 0` |
| Vigencia | Semiabierta `[valid_from, valid_to)`; `valid_to IS NULL` significa vigente |
| Estado | `active` o `inactive` |
| Solapamiento | Dos bloques **activos** del mismo médico, el mismo día y vigencias que se cruzan no pueden coexistir, **sea cual sea la especialidad**: un médico no puede estar en dos lugares a la vez |
| Bloques inactivos | No bloquean: un bloque `inactive` puede solaparse con otro `inactive` o con uno `active` |
| Ausencias | **Fuera de alcance**: vacaciones, permisos y días festivos no se modelan aquí |

## 4. Migración

- **Archivo:** `database/migrations/hospital/2026_08_11_000900_create_doctor_schedules.php`
- **Número propuesto:** `000900`, elegido para no colisionar con los slices ya
  reservados: H5 (`000500`, medicamentos y prescripciones), H6 (`000600`, laboratorio)
  y H7 (`000700`, auditoría y sincronización).
- **Reversión:** `DROP TRIGGER`, `DROP FUNCTION` y `DROP TABLE doctor_schedules;` — verificado en la prueba de rollback.
- **Conexión:** `hospital`, como el resto del esquema clínico.

## 5. Pruebas

`tests/Database/Hospital/DoctorScheduleIntegrityTest.php` — 16 pruebas, 135
aserciones, ejecutadas sobre PostgreSQL 16:

| Caso | Qué demuestra |
|---|---|
| `test_valid_schedule_is_accepted` | Un horario válido se guarda con su vigencia |
| `test_overlapping_block_for_the_same_doctor_and_weekday_is_rejected` | Se rechaza el cruce en el mismo médico, día y vigencia |
| `test_non_overlapping_blocks_are_accepted` | Dos bloques contiguos del mismo médico y día se aceptan |
| `test_same_block_on_another_weekday_is_accepted` | El mismo bloque en otro día de la semana se acepta |
| `test_same_block_for_another_doctor_is_accepted` | El mismo bloque para otro médico se acepta |
| `test_crossing_validity_periods_are_rejected` | Dos bloques que solo se cruzan por vigencia también se rechazan |
| `test_inactive_blocks_do_not_block_each_other` | Un bloque `inactive` no bloquea ni se deja bloquear |
| `test_invalid_foreign_keys_are_rejected` | Médico y especialidad inexistentes se rechazan |
| `test_block_end_must_be_after_start` | `end_minute > start_minute` |
| `test_block_length_must_be_a_multiple_of_slot_minutes` | La duración es múltiplo del turno |
| `test_invalid_validity_period_is_rejected` | `valid_to > valid_from` |
| `test_invalid_weekday_and_slot_are_rejected` | Día entre 1 y 7 y turno entre 5 y 240 minutos |
| `test_schedule_without_the_specialty_of_the_doctor_is_rejected` | El **trigger** rechaza un horario en una especialidad que el médico no tiene |
| `test_schedule_outside_the_validity_of_the_specialty_is_rejected` | El horario se rechaza si cae fuera de la vigencia de la especialidad del médico |
| `test_schedule_inside_the_validity_of_the_specialty_is_accepted` | El horario se acepta dentro de la vigencia de la especialidad |
| `test_rollback_removes_the_table_and_keeps_the_previous_slices` | La reversión borra la tabla y **no** toca los slices anteriores |

## 6. Acuerdos pendientes

| Acuerdo | Responsable | Estado |
|---|---|---|
| Aprobar la entidad 28 (`doctor_schedules`) | Grupo | `PENDIENTE` |
| Confirmar el número de archivo de la migración | Javier (BD) | `PENDIENTE` |
| Confirmar quién escribe la migración | Javier (BD) con ASII-04 | `PENDIENTE` |
| Formato de la respuesta de disponibilidad (slots concretos) | ASII-04 con ASII-05 | `PENDIENTE` |
| Definir el modelo de ausencias (fuera de alcance aquí) | ASII-04 | `PENDIENTE` |

## 7. Consecuencia de no resolverlo

Sin esta tabla, el cálculo de disponibilidad de médicos no tiene datos persistidos:
Citas y Admisión no pueden ofrecer un horario real al paciente. El módulo de médicos
puede seguir trabajando con su propio diseño, pero el día que este PR se integre, la
tabla pasa a ser la fuente de verdad y hay que acordar su número de archivo antes.
