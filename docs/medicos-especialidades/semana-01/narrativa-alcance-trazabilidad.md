# Narrativa de alcance y trazabilidad — Médicos, especialidades y disponibilidad

## Propósito

Que el administrador del hospital **registre el perfil profesional de cada médico** con su número de colegiado y su **especialidad principal**, que pueda **asociarle otras especialidades** con su vigencia y que la recepción **consulte la disponibilidad** de los médicos por especialidad para agendar citas. Así el resto del sistema (citas, asignación a episodios, notas y signos vitales) trabaja solo con médicos válidos, con especialidades vigentes y con traza de cada cambio.

## Alcance funcional

**Incluido**

- Registrar el perfil profesional (`doctor_profiles`) de un usuario del hospital que ya existe en `local_user_refs` con rol `Medico`: número de colegiado, fecha de inicio (`valid_from`) y estado `active`.
- Asociar la **especialidad principal** en el mismo alta y en la misma transacción (`doctor_specialties` con `is_primary = true`).
- Asociar **especialidades adicionales** con vigencia (`valid_from`, `valid_to` opcional), validando que no se solapen con la misma especialidad ni generen una segunda principal.
- Consultar el catálogo de **especialidades activas** para elegirlas en el alta, en la asociación y en la búsqueda.
- **Consultar la disponibilidad** de los médicos de una especialidad en un rango de hasta 14 días, opcionalmente de un solo médico: franjas libres según su horario de atención, descontando citas activas, franjas pasadas y fechas sin perfil o asociación vigente.
- Trazar el alta y cada asociación en `local_access_audits` (HOSPITAL) en la misma transacción.

**Fuera de alcance**

- **Crear la cuenta del usuario o su membresía:** es del módulo *Gestión de usuarios y tenants* (CENTRAL) y llega por sincronización a `local_user_refs`.
- **Mantener el catálogo de especialidades** (alta, edición e inactivación): es parte del módulo, pero no del proceso de esta semana; se trabaja después.
- **Cambiar la especialidad principal, cerrar o inactivar una asociación, suspender o dar de baja a un médico.** Requieren reglas sobre las citas y asignaciones futuras (ver R6) y se detallan en la Semana 5.
- **Reingreso** de un médico con un perfil anterior: la licencia es única aun en perfiles inactivos, así que se resuelve reactivando el perfil existente, no con otra alta.
- **Reservar citas:** es del módulo *Citas médicas y agenda*. Este módulo solo informa franjas libres.
- **Mantener el horario de atención y registrar ausencias** (vacaciones, permisos, congresos): dependen de acordar la entidad de horario (ver C1).
- Validar el número de colegiado contra el colegio profesional (servicio externo).
- Publicar perfiles o especialidades hacia CENTRAL: son datos locales del hospital.

## Supuestos y restricciones

- **Actores:** el alta y la asociación las hace el *Administrador del hospital* (`AdminHospital`). La disponibilidad la consulta la *Recepcionista* (personal de agenda); el rol exacto lo define RBAC (ver C3).
- **Elegibilidad del usuario:** solo se registra a un usuario de `local_user_refs` con `status = 'active'`, `valid_to` nulo o futuro y `role_code` de médico. Si no, `422 USER_NOT_ELIGIBLE`.
- **Unicidad:** `license_number` es único en toda la tabla (`doctor_profiles_license_number_unique`) y un usuario tiene un solo perfil activo abierto (`doctor_profiles_active_open_user_unique`). El servicio lo valida antes (`409`) y, si dos altas compiten, la base rechaza la segunda (violación `UNIQUE` → `409`).
- **Especialidad principal obligatoria:** `appointments` y `care_assignments` exigen una especialidad activa y vigente del médico. Un perfil sin especialidad no sirve para atender, así que el alta crea perfil y especialidad principal **en la misma transacción**.
- **Vigencias:** las fechas son `date` con intervalos `[valid_from, valid_to)`. Una asociación debe quedar dentro de la vigencia del perfil. La base **no** lo exige (ver R2), así que lo valida el servicio.
- **Conflictos de especialidad:** la base ya los impide con dos restricciones `EXCLUDE` de `doctor_specialties`: misma especialidad con periodos solapados y dos principales en el mismo periodo. El servicio los detecta antes para responder `409` con un código claro; si dos peticiones compiten, la restricción es la última garantía.
- **Especialidad activa:** `doctor_specialties` no revisa el estado de la especialidad; `care_assignments` sí. Para no crear asociaciones inútiles, el servicio exige `specialties.status = 'active'`.
- **Disponibilidad derivada:** no se guarda. Se calcula con el horario de atención (`DoctorScheduleProvider`) y las citas `scheduled` / `confirmed` del médico (`AppointmentReader`), sin importar la especialidad de la cita, porque el médico no puede estar en dos citas a la vez (`appointments_doctor_active_period_exclusion`).
- **Franjas:** intervalos `[inicio, fin)` en la zona horaria del hospital. La duración de la franja la da el horario (por ejemplo, 30 minutos). No se ofrecen franjas pasadas.
- **La consulta es una foto:** entre la consulta y la reserva otra persona puede tomar la franja. La reserva la protege la restricción `EXCLUDE` de `appointments` en el módulo *Citas*, que debe responder `409` en ese caso. Por eso la respuesta incluye `generated_at`.
- **Minimización de datos:** `AppointmentReader` devuelve solo los intervalos ocupados, nunca el paciente ni el motivo de la cita.
- **Rango acotado:** de hoy en adelante y como máximo 14 días por consulta, para limitar el cálculo.
- **Sin transacciones entre bases:** todo ocurre en la base HOSPITAL del tenant. `local_user_refs` solo se lee.
- **La auditoría guarda metadatos** (administrador, médico, licencia, especialidad y vigencia), no datos clínicos.

## Trazabilidad entre los diagramas

Los números de la columna *Secuencia* corresponden a la numeración automática (`autonumber`) de [`03-secuencia.svg`](./03-secuencia.svg).

| UC | Objetivo | Actividad (decisión o paso) | Secuencia (mensajes) |
|---|---|---|---|
| UC-MED-01 | Registrar el perfil profesional del médico | Partición A: "Administrador elige Registrar médico…", "Capturar número de colegiado…", "¿Campos obligatorios…?", "¿Usuario local activo, vigente y con rol Medico?", "BEGIN… INSERT doctor_profiles…" → "COMMIT · 201 Médico registrado…" → (B) | 1–2, 4–11, 20, 33–36 |
| UC-MED-02 | Asociar especialidad al médico | A: "INSERT doctor_specialties (is_primary)" dentro del alta. Partición B completa: "Administrador abre el médico…", "¿Médico activo y periodo dentro de la vigencia…?", "BEGIN… INSERT doctor_specialties…" → "COMMIT · 201 Especialidad asociada…" → (C) | 21, 37–38, 40–45, 50, 56–59 |
| UC-MED-03 | Rechazar alta o asociación en conflicto | A: "¿Ya tiene un perfil activo o la licencia ya está registrada?" → "409 DOCTOR_ALREADY_REGISTERED / LICENSE_ALREADY_REGISTERED", "Violación UNIQUE (concurrencia)". B: "¿Misma especialidad vigente en un periodo solapado…?" → "409 SPECIALTY_ALREADY_ASSOCIATED / PRIMARY_SPECIALTY_CONFLICT", "Violación EXCLUDE (concurrencia)" | 12–15, 24–27, 46–49, 53–55 |
| UC-MED-04 | Consultar especialidades activas | A: "¿Especialidad principal existe y está activa?". B: "¿Especialidad existe y está activa?". C: "¿Especialidad existe y está activa?" → "422 SPECIALTY_INACTIVE" | 16–19, 42–45, 67–70; además `GET /specialties?status=active` para el selector (se detalla en la Semana 5) |
| UC-MED-05 | Consultar disponibilidad de médicos | Partición C: "Recepcionista elige especialidad…", "¿Rango válido…?", "Buscar médicos con perfil activo y asociación activa…", "¿Hay médicos con la especialidad?" → "200 Lista vacía…" o "200 Disponibilidad por médico…" | 60–61, 63–72, 80–82 |
| UC-MED-06 | Calcular franjas libres | C: "Por cada médico: obtener su horario de atención…", "¿Tiene horario configurado?" → "Marcar… SCHEDULE_NOT_CONFIGURED" o "Generar franjas del horario y restar…", "¿Quedan médicos por procesar?" | 73–79 |
| UC-MED-07 | Validar acceso y permiso | A: "¿JWT, tenant y permiso medicos.registrar?". B: "¿Permiso medicos.asociar_especialidad?". C: "¿JWT, tenant y permiso medicos.ver_disponibilidad?" | 2–4, 38–40, 61–63 |
| UC-MED-08 | Registrar evento de auditoría (`local_access_audits`, HOSPITAL) | A: "+ INSERT local_access_audits medico.registrado", "¿Restricciones y auditoría correctas?" → "Falla la auditoría" o COMMIT. B: "+ INSERT local_access_audits medico.especialidad_asociada" → ROLLBACK o COMMIT | 22–23, 28–32, 51–55 |

## Flujo y resultados

### A · Registrar médico con especialidad principal

1. **Acceso (UC-MED-07):** JWT, tenant y permiso `medicos.registrar`; sin ellos, `401` o `403` con la traza de RBAC.
2. **Formato:** `local_user_ref_id`, `license_number`, `valid_from` y `primary_specialty_id` obligatorios y con formato válido; si no, `422` con los errores por campo.
3. **Elegibilidad:** usuario local activo, vigente y con rol de médico; si no, `422 USER_NOT_ELIGIBLE`.
4. **Duplicados (UC-MED-03):** si el usuario ya tiene perfil activo o la licencia ya existe, `409 DOCTOR_ALREADY_REGISTERED` o `409 LICENSE_ALREADY_REGISTERED`.
5. **Especialidad principal (UC-MED-04):** debe existir y estar activa; si no, `422 SPECIALTY_INACTIVE`.
6. **Transacción (conexión `hospital`):** `INSERT doctor_profiles` → `INSERT doctor_specialties` (`is_primary = true`, desde `valid_from`) → `INSERT local_access_audits` (`medico.registrado`, UC-MED-08). Violación `UNIQUE` por concurrencia: `ROLLBACK` y `409`. Auditoría fallida: `ROLLBACK` y `500`.
7. **Resultado:** `COMMIT` y `201` con `doctor_profile_id` y la especialidad principal. Continúa en B.

### B · Asociar especialidad adicional

1. **Acceso (UC-MED-07):** permiso `medicos.asociar_especialidad`; si no, `403`.
2. **Médico y periodo:** el perfil debe estar `active` (`409 DOCTOR_NOT_ACTIVE`) y el periodo, dentro de su vigencia con `valid_to > valid_from` (`422 PERIOD_OUTSIDE_PROFILE`).
3. **Especialidad (UC-MED-04):** existe y está activa; si no, `422 SPECIALTY_INACTIVE`.
4. **Conflictos (UC-MED-03):** la misma especialidad activa en un periodo solapado (`409 SPECIALTY_ALREADY_ASSOCIATED`) o, si se pide como principal, otra principal en el periodo (`409 PRIMARY_SPECIALTY_CONFLICT`).
5. **Transacción:** `INSERT doctor_specialties` → `INSERT local_access_audits` (`medico.especialidad_asociada`). Violación `EXCLUDE` por concurrencia: `409`; auditoría fallida: `500`. En ambos casos, `ROLLBACK`.
6. **Resultado:** `COMMIT` y `201` con `doctor_specialty_id`. Continúa en C: la especialidad ya cuenta para la disponibilidad.

### C · Consultar disponibilidad

Solo lectura; no abre transacción de escritura ni se audita.

1. **Acceso (UC-MED-07):** permiso `medicos.ver_disponibilidad`; si no, `401` o `403`.
2. **Rango:** `from` ≥ hoy, `to` ≥ `from` y como máximo 14 días; si no, `422 INVALID_DATE_RANGE`.
3. **Especialidad (UC-MED-04):** existe y está activa; si no, `422 SPECIALTY_INACTIVE`.
4. **Médicos:** los que tienen perfil `active` y asociación `active` a la especialidad con vigencia que toca el rango (y, si se pide, solo ese médico). Si no hay, `200` con lista vacía y `NO_DOCTORS_FOR_SPECIALTY`.
5. **Franjas (UC-MED-06), por cada médico:** se pide su horario al `DoctorScheduleProvider`. Sin horario, el médico aparece con `SCHEDULE_NOT_CONFIGURED` y sin franjas. Con horario, se generan las franjas y se restan las citas `scheduled` / `confirmed` (`AppointmentReader`), las franjas pasadas y las fechas fuera de la vigencia del perfil o de la asociación.
6. **Resultado:** `200` con las franjas libres por médico y `generated_at` ([Anexo C](#anexo-c--ejemplo-de-respuesta-de-disponibilidad)).

## Riesgos y pendientes detectados en el esquema

| # | Hallazgo | Impacto | Propuesta |
|---|---|---|---|
| R1 | El esquema HOSPITAL no tiene una entidad de **horario de atención**; la especificación fija exactamente 27 entidades. | Sin horario no se puede calcular la disponibilidad: hoy solo se sabe cuándo el médico está **ocupado**, no cuándo atiende. | `DoctorScheduleProvider` «interfaz» y propuesta de tabla `doctor_schedules` ([Anexo B](#anexo-b--propuesta-de-horario-de-atención-doctor_schedules)). Requiere acuerdo para cambiar la especificación (C1). |
| R2 | `doctor_specialties` no exige que su periodo quede dentro de la vigencia del perfil ni que la especialidad esté activa. | Se podrían crear asociaciones que nunca sirven para citas ni asignaciones (los triggers de esas tablas las rechazarían después). | Validar en `DoctorService` (Semana 1) y evaluar en la Semana 5 un trigger propio del módulo sobre `doctor_specialties`. |
| R3 | `local_user_refs` se llena por sincronización desde CENTRAL, que aún no está implementada, y no hay catálogo cerrado de `role_code`. | Sin usuarios sincronizados no se puede registrar ningún médico, y la elegibilidad por rol no es comprobable. | Acordar con *Gestión de usuarios* y *RBAC* el valor del rol de médico y la sincronización (C3). Para pruebas, datos semilla en `local_user_refs`. |
| R4 | La tabla de auditoría local (`local_access_audit`, slice H8) aún no tiene migración (tarea 3.7). Es el mismo hallazgo R4 de Notas SOAP. | Sin ella no se garantiza "alta y traza en la misma transacción". | Reutilizar la propuesta del [Anexo A de Notas SOAP](../../notas-soap/semana-01/narrativa-alcance-trazabilidad.md#anexo-a--propuesta-de-la-tabla-local_access_audits-hospital) con los eventos de este módulo ([Anexo A](#anexo-a--eventos-de-auditoría)). |
| R5 | La disponibilidad es una foto; entre la consulta y la reserva puede cambiar. | Doble reserva si el módulo *Citas* confiara solo en la consulta. | La restricción `appointments_doctor_active_period_exclusion` ya la impide; *Citas* debe traducir esa violación a `409` (C2). |
| R6 | Los triggers de `appointments` y `care_assignments` validan la especialidad **solo al insertar o actualizar** esas filas. | Si después se cierra o inactiva una asociación, las citas futuras ya creadas quedan con un médico sin esa especialidad vigente. | Fuera de alcance esta semana. En la Semana 5, impedir cerrar una asociación con citas futuras activas o exigir reprogramarlas, acordado con *Citas* (C2). |

## Coordinación pendiente con otros módulos

Este módulo deja **sin desarrollar** las partes que dependen de decisiones de otros módulos, para no modificar entidades que no le corresponden. Antes de implementarlas (a más tardar en la Semana 5, cuando se formaliza el contrato API) hay que acordar lo siguiente:

| # | Con quién | Qué hay que acordar | Qué habilita para este módulo | Mientras tanto |
|---|---|---|---|---|
| C1 | Persona encargada del esquema de base de datos (o, si no hay responsable asignado, el grupo) | Agregar la entidad de horario de atención (`doctor_schedule`) al slice H1, lo que cambia la especificación de 27 entidades, y quién la migra. | Guardar el horario por médico y especialidad y calcular la disponibilidad real. | `DoctorScheduleProvider` con una implementación de configuración para pruebas. |
| C2 | Módulo *Citas médicas y agenda* (ASII-05) | Que *Citas* consuma esta consulta (o `AvailabilityService`) para ofrecer franjas, la duración de la franja, el `409` ante una doble reserva y qué pasa con las citas futuras al cerrar una asociación (R6). Este módulo solo **lee** `appointments`. | Agenda coherente con la disponibilidad. | `AppointmentReader` lee `appointments` en modo lectura; no se modifica la tabla. |
| C3 | Módulos *Gestión de usuarios y tenants* (ASII-01) y *RBAC* (ASII-02) | El `role_code` del médico (por ejemplo, `Medico`), el rol de la recepción, los permisos `medicos.*` y la sincronización CENTRAL → `local_user_refs`. | Registrar médicos reales y proteger las rutas. | Datos semilla en `local_user_refs` y permisos propuestos en este documento. |
| C4 | Módulo *Gobernanza y auditoría* (ASII-22) y persona encargada del esquema (slice H8) | Las columnas de `local_access_audits` (propuesta del Anexo A de Notas SOAP) y los valores de `action` de este módulo. | La traza en la misma transacción que el alta y la asociación. | Se usa la propuesta de Notas SOAP como referencia. |

## Dependencias con otros módulos

- **Depende de:**
  - *Gestión de usuarios y tenants* (usuarios y membresías sincronizados en `local_user_refs`).
  - *RBAC* (permisos `medicos.registrar`, `medicos.asociar_especialidad` y `medicos.ver_disponibilidad`).
  - *Citas médicas y agenda* (lectura de `appointments` para restar las franjas ocupadas).
  - *Gobernanza y auditoría* y esquema HOSPITAL: la tabla `local_access_audits` (slice H8).
- **Dependen de este módulo:** *Citas médicas y agenda* (médico, especialidad y disponibilidad), *Admisión* y *Expediente* (asignación del médico tratante con `care_assignments`), *Notas SOAP* y *Signos vitales* (autor médico asignado), y los reportes y analytics.

## Anexo A — Eventos de auditoría

> **Estado:** decisión de diseño (Semana 1). No se crea ninguna tabla. Los eventos se escriben en `local_access_audits` con la estructura propuesta en el [Anexo A de Notas SOAP](../../notas-soap/semana-01/narrativa-alcance-trazabilidad.md#anexo-a--propuesta-de-la-tabla-local_access_audits-hospital), con `module = 'medicos'` y sin paciente ni episodio.

| `action` | `resource_type` / `resource_id` | `metadata` |
|---|---|---|
| `medico.registrado` | `doctor_profile` / id del perfil | `{"license_number": "COL-12345", "primary_specialty_code": "MED-INT", "valid_from": "2026-10-15"}` |
| `medico.especialidad_asociada` | `doctor_specialty` / id de la asociación | `{"doctor_profile_id": "…", "specialty_code": "CARD", "is_primary": false, "valid_from": "2026-11-01", "valid_to": null}` |

**Orden dentro de la transacción (conexión `hospital`):** `BEGIN` → `INSERT doctor_profiles` → `INSERT doctor_specialties` → `INSERT local_access_audits` → `COMMIT`. Si cualquier paso falla, `ROLLBACK` de todo.

## Anexo B — Propuesta de horario de atención (`doctor_schedules`)

> **Estado:** propuesta de documentación (Semana 1). **No se implementa esta semana** y no se agrega ninguna migración. Requiere el acuerdo C1, porque cambia la especificación de 27 entidades del esquema HOSPITAL. Sigue las convenciones de las migraciones HOSPITAL: tabla en plural, PK `uuid`, FK locales con `ON DELETE RESTRICT`, estados con `CHECK` y vigencia `[valid_from, valid_to)`.

```sql
CREATE TABLE doctor_schedules (
    doctor_schedule_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    doctor_profile_id  uuid NOT NULL REFERENCES doctor_profiles(doctor_profile_id) ON DELETE RESTRICT,
    specialty_id       uuid NOT NULL REFERENCES specialties(specialty_id) ON DELETE RESTRICT,
    weekday            smallint NOT NULL CHECK (weekday BETWEEN 1 AND 7),          -- ISO: 1 = lunes
    start_minute       smallint NOT NULL CHECK (start_minute BETWEEN 0 AND 1439),  -- minutos desde 00:00, hora local
    end_minute         smallint NOT NULL CHECK (end_minute BETWEEN 1 AND 1440),
    slot_minutes       smallint NOT NULL CHECK (slot_minutes BETWEEN 5 AND 240),
    valid_from         date NOT NULL,
    valid_to           date,
    status             text NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    created_at         timestamptz NOT NULL DEFAULT now(),
    updated_at         timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT doctor_schedules_block_check CHECK (end_minute > start_minute AND (end_minute - start_minute) % slot_minutes = 0),
    CONSTRAINT doctor_schedules_valid_period CHECK (valid_to IS NULL OR valid_to > valid_from),
    -- Un médico no tiene dos bloques solapados el mismo día, aunque sean de especialidades distintas
    CONSTRAINT doctor_schedules_doctor_block_exclusion EXCLUDE USING gist (
        doctor_profile_id WITH =,
        weekday WITH =,
        int4range(start_minute, end_minute, '[)') WITH &&,
        daterange(valid_from, COALESCE(valid_to, 'infinity'::date), '[)') WITH &&
    ) WHERE (status = 'active')
);
CREATE INDEX doctor_schedules_doctor_profile_id_index ON doctor_schedules (doctor_profile_id);
CREATE INDEX doctor_schedules_specialty_id_index ON doctor_schedules (specialty_id);
```

| Decisión | Justificación |
|---|---|
| Bloque por médico **y especialidad** | Una cita lleva `specialty_id`; así se sabe en qué horario atiende cada especialidad. |
| `EXCLUDE` por médico y día | El médico no puede atender dos bloques a la vez; usa `btree_gist`, que el esquema ya instala. |
| Minutos desde medianoche | Evita depender del tipo `time` en los rangos y permite `int4range`. |
| `slot_minutes` divide el bloque | Todas las franjas tienen la misma duración y no queda una franja incompleta al final. |
| Sin ausencias | Vacaciones y permisos serían otra entidad; quedan fuera de alcance. |

## Anexo C — Ejemplo de respuesta de disponibilidad

`GET /api/v1/doctors/availability?specialty_id=…&from=2026-10-19&to=2026-10-20`

```json
{
  "specialty": { "specialty_id": "…", "code": "CARD", "name": "Cardiología" },
  "from": "2026-10-19",
  "to": "2026-10-20",
  "generated_at": "2026-10-18T15:04:00-06:00",
  "doctors": [
    {
      "doctor_profile_id": "…",
      "display_name": "Dra. Ana López",
      "schedule_status": "CONFIGURED",
      "free_slots": [
        { "starts_at": "2026-10-19T08:00:00-06:00", "ends_at": "2026-10-19T08:30:00-06:00" },
        { "starts_at": "2026-10-19T09:00:00-06:00", "ends_at": "2026-10-19T09:30:00-06:00" }
      ]
    },
    {
      "doctor_profile_id": "…",
      "display_name": "Dr. Luis Pérez",
      "schedule_status": "SCHEDULE_NOT_CONFIGURED",
      "free_slots": []
    }
  ]
}
```

La franja de 08:30 a 09:00 no aparece porque tiene una cita `confirmed`. La respuesta no incluye datos del paciente.

## Archivos relacionados

- [Casos de uso](./01-casos-de-uso.svg)
- [Actividades](./02-actividad.svg)
- [Secuencia](./03-secuencia.svg)
- [README del módulo](./README.md)
