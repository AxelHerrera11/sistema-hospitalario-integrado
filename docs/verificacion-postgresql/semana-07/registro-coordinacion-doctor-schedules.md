# Registro de coordinación: tabla `doctor_schedules` (28.ª entidad)

**Estado:** `PENDIENTE DE APROBACIÓN DEL GRUPO`
**Solicitante:** módulo de médicos (Josué), por necesidad de citas (Lis) y admisión (Axel).
**Responsable de la verificación de BD:** Javier Alexander Fajardo López.

## 1. Qué se registra

La solicitud es crear la tabla **`doctor_schedules`** (horarios de atención) para
calcular la disponibilidad de médicos. La tabla **no figura** entre las 27 entidades de
la especificación vigente del esquema federado
(`openspec/changes/postgresql-federated-schema-rebuild/specs/hospital-clinical-schema/spec.md`).

Por lo tanto, **la especificación pasaría de 27 a 28 entidades**, y ese cambio
**no se aplica por decisión unilateral**: requiere aprobación del grupo y registro del
acuerdo. El número de la especificación se mantiene en 27 hasta que exista acuerdo
explícito.

## 2. Definición propuesta (literal del solicitante, validada contra el esquema real)

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
```

## 3. Validación contra el esquema real

| Punto de la propuesta | Estado en `origin/develop` | Verificación |
|---|---|---|
| `doctor_profiles(doctor_profile_id)` | Existe, PK uuid | Correcto |
| `specialties(specialty_id)` | Existe, PK uuid | Correcto |
| `btree_gist` en HOSPITAL | Habilitada por `000000_enable_hospital_extensions` | Correcto: permite `EXCLUDE` con `int4range` y `daterange` |
| `gen_random_uuid()` | Disponible (extensión `pgcrypto` o `uuid-ossp` del servidor) | Correcto, igual que el resto del esquema |
| `timestamptz` y `text` con `CHECK` de dominio | Patrón del esquema HOSPITAL | Correcto |
| FK `ON DELETE RESTRICT` | Patrón del esquema HOSPITAL | Correcto |
| Índice en cada FK | Regla de verificación del módulo de BD | Correcto |

**Modificaciones técnicas necesarias:** ninguna. La definición literal es compatible
con el esquema real. La única diferencia con el resto de las migraciones es que el
`CHECK` de `weekday` documenta que el 1 es lunes (ISO-8601) y que el bloque horario se
expresa en minutos desde medianoche, lo que evita el tipo `time`.

## 4. Contrato que se publica al grupo

- **Nombre:** `doctor_schedules` (entidad `doctor_schedule`).
- **Campos:** `doctor_schedule_id`, `doctor_profile_id`, `specialty_id`, `weekday`
  (1 = lunes), `start_minute`, `end_minute`, `slot_minutes`, `valid_from`, `valid_to`,
  `status`, `created_at`, `updated_at`.
- **Claves foráneas:** `doctor_profile_id` y `specialty_id`, locales, con
  `ON DELETE RESTRICT` e índice.
- **Índices:** `doctor_schedules_doctor_profile_id_index` y
  `doctor_schedules_specialty_id_index`.
- **Política de solapamiento:** dos horarios **activos** del mismo médico, el mismo día
  de la semana y vigencias que se cruzan no pueden coexistir. La restricción es por
  médico y día, **independientemente de la especialidad**, porque el médico no puede
  estar en dos lugares a la vez. Los horarios `inactive` quedan fuera del bloqueo.
- **Vigencia:** semiabierta `[valid_from, valid_to)`; `valid_to IS NULL` significa
  vigente.
- **Calendario:** `weekday` ISO-8601 (1 = lunes, 7 = domingo). Los días festivos se
  manejan en la capa de aplicación; no se inventan aquí.
- **Ausencias (vacaciones y permisos):** **fuera de alcance**. No forman parte de esta
  tabla y no se modelan como requisito implícito.

## 5. Estado de los acuerdos

| Acuerdo | Responsable | Estado |
|---|---|---|
| Aprobar la entidad 28 (`doctor_schedules`) | Grupo (coordinación: Josué) | `PENDIENTE` |
| Definir quién escribe la migración | Javier (BD) con el módulo de médicos | `PENDIENTE` |
| Número de archivo de la migración | Javier (BD), sin colisionar con H5, H6 y H7 | `PENDIENTE` |
| Formato de la respuesta de disponibilidad | Módulo de médicos con Citas | `PENDIENTE` |
| Exclusión de ausencias del alcance | Registrado en este documento | `ACORDADO` en el alcance de esta entrega |

## 6. Consecuencia de no resolverlo

Sin `doctor_schedules`, el cálculo de disponibilidad de médicos no tiene datos
persistidos: Citas (Lis) y Admisión (Axel) no pueden ofrecer un horario real al
paciente. El módulo de médicos sigue trabajando con lo que publique su rama, por
lo que el riesgo está en la integración, no en la bloquear el desarrollo.

## 7. Registro en el flujo del módulo

- Hallazgo **H-01** del [informe de auditoría de migraciones](./informe-auditoria-migraciones.md).
- Semana 7: auditoría y registro.
- PR técnico independiente: migración reversible con pruebas, en la excepción que
  permite el plan de integración para no mezclar entregables semanales ni superar el
  límite de 400 líneas por PR.
