# Narrativa de alcance y trazabilidad — Admisión hospitalaria y asignación de cama

## Propósito

Que el hospital registre la **hospitalización de un paciente** como una admisión abierta, con un médico responsable, y le asigne **una cama disponible** de forma consistente: sin dos pacientes en la misma cama, sin dos camas para el mismo episodio y con trazabilidad de quién asignó qué y cuándo. Cuando no hay camas, la admisión queda registrada **en espera** para no perder al paciente.

## Alcance funcional

**Incluido**

- Registrar la admisión como un episodio `care_episodes` con `type = 'inpatient'` y `status = 'open'`, con motivo, servicio y fecha y hora de ingreso no futura.
- Asignar el médico responsable al episodio (`care_assignments`, `source = 'episode'`), validando que tenga la especialidad activa.
- Verificar que el paciente exista en el hospital, esté activo y no tenga otra hospitalización abierta.
- Buscar camas `available` en salas `active` y sin estancia abierta, filtrando por sala y tipo.
- Asignar una cama al episodio: `bed_stays` abierta y `beds.status = 'occupied'` en una sola transacción.
- Dejar la admisión en espera de cama cuando no hay disponibilidad.
- Consultar la admisión con su sala, cama o estado de espera.
- Auditar la admisión, la asignación de cama y las consultas en `local_access_audits` (HOSPITAL).

**Fuera de alcance**

- Crear o editar salas y camas, y ponerlas en mantenimiento: módulo *Catálogo de salas, wards y camas* (ASII-06).
- Traslados entre camas, altas y liberación de camas: módulo *Traslados, altas y liberación de camas* (ASII-08).
- Tablero de ocupación: módulo *Dashboard de ocupación hospitalaria* (ASII-09).
- Registro del paciente: módulo *Pacientes* (ASII-03).
- Priorización de la lista de espera de camas y reservas anticipadas (posibles mejoras de la Semana 5).
- Admisión por urgencias (`type = 'emergency'`) y consulta externa (`outpatient`).

## Supuestos y restricciones

- **El esquema ya garantiza:**
  - Sin solapamiento de estancias en la misma cama (`bed_stays_bed_active_period_exclusion`, intervalos `[)`).
  - Una sola estancia abierta por episodio (`bed_stays_open_care_episode_unique`).
  - Estancia dentro del periodo del episodio (`bed_stays_episode_period_trigger`).
  - Estancia cerrada inmutable y sin borrado (`bed_stays_finalized_immutable`, `bed_stays_delete_protection`).
  - Especialidad activa del médico asignado (`care_assignments_specialty_trigger`).
  - Coherencia entre estado y `ended_at` del episodio (`care_episodes_status_period_check`).
- **Reglas de la aplicación** que la base no garantiza:
  - Un paciente no puede tener dos episodios `inpatient` abiertos a la vez (ver R1).
  - La cama debe estar `available` y su sala `active`: se comprueba con `SELECT … FOR UPDATE` dentro de la transacción (ver R2).
  - Fecha de ingreso no futura y paciente activo.
- **Concurrencia:** dos usuarios pueden elegir la misma cama al mismo tiempo. El bloqueo `FOR UPDATE` serializa la decisión y la exclusión GiST es la última barrera: el segundo recibe `409 BED_ALREADY_ASSIGNED` y la lista se refresca.
- **Transacción local:** episodio, asignación del médico, estancia, estado de la cama, auditoría y `outbox_events` se escriben en la conexión `hospital`. Si algo falla, `ROLLBACK`. Nada se escribe en CENTRAL de forma síncrona.
- La auditoría guarda metadatos (usuario, paciente, episodio, sala, cama y motivo), no el expediente.

## Trazabilidad entre los diagramas

| UC | Objetivo | Actividad (decisión o paso) | Secuencia (mensajes) |
|---|---|---|---|
| UC-ADM-01 | Registrar admisión hospitalaria | Partición A: «Personal de admisión busca al paciente…», «Capturar médico responsable…», «¿Motivo, médico, especialidad y fecha de ingreso válidos?» → «BEGIN … INSERT care_episodes … care_assignments» → «COMMIT · 201 Admisión registrada» | 1–2, 4–7, 14–16, 19–20, 23–25 |
| UC-ADM-02 | Verificar paciente y hospitalización abierta | A: «¿El paciente existe en el tenant y está activo?», «¿Ya tiene un episodio inpatient abierto?» → 404/409 | 8–13 |
| UC-ADM-03 | Buscar cama disponible | Partición B: «Admisión o jefatura de enfermería elige sala y tipo…», «Listar camas available en salas active…», «¿Hay camas disponibles?» | 26–30, 33 |
| UC-ADM-04 | Asignar cama al episodio | B: «Usuario selecciona la cama…», «BEGIN … SELECT bed … FOR UPDATE», «INSERT bed_stays … UPDATE beds» → «COMMIT · 201 Cama asignada» | 34–35, 37–39, 41, 43, 46–47, 54–56 |
| UC-ADM-05 | Rechazar asignación de cama | B: «409 EPISODE_ALREADY_HAS_BED», «¿La cama sigue available y su sala sigue active?» → «409 BED_NOT_AVAILABLE», «Exclusión GiST o índice único violado» → «409 BED_ALREADY_ASSIGNED» | 42, 44–45, 50–51 |
| UC-ADM-06 | Validar acceso y permisos | A: «¿JWT, tenant y permiso admisiones.registrar?». B: «¿… camas.asignar?». C: «¿… admisiones.ver?» | 2–4, 28, 35–37 |
| UC-ADM-07 | Registrar auditoría (`local_access_audits`, HOSPITAL) | A y B: «+ local_access_audits + outbox_events», «Falla de auditoría» → ROLLBACK + 500. C: «Registrar la lectura… sin bloquear la consulta» | 17–18, 21–22, 48–49, 52–53 |
| UC-ADM-08 | Consultar admisión y cama asignada | Partición C completa: «¿El episodio existe en el tenant?», «¿Tiene bed_stay abierta?» → sala y cama o «En espera de cama» | `GET /api/v1/admissions/{episode}` (se detalla en la Semana 5) |
| UC-ADM-09 | Dejar admisión en espera de cama | B: «¿Hay camas disponibles?» → «200 lista vacía: la admisión queda en espera de cama». C: «Mostrar «En espera de cama»» | 31–32 |

## Flujo y resultados

### A · Registrar admisión hospitalaria

1. **Acceso (UC-ADM-06):** JWT, tenant y permiso `admisiones.registrar`; sin ellos, `401` o `403` con la traza de RBAC.
2. **Datos:** motivo, médico, especialidad y fecha de ingreso no futura; si no, `422` con errores por campo.
3. **Paciente (UC-ADM-02):** si no existe en el tenant, `404`; si está inactivo o fallecido, `409 PATIENT_INACTIVE`.
4. **Hospitalización abierta (UC-ADM-02):** si ya tiene un episodio `inpatient` abierto, `409 ADMISSION_ALREADY_OPEN` y se muestra el vigente.
5. **Transacción (UC-ADM-07):** `INSERT care_episodes`, `INSERT care_assignments`, `INSERT local_access_audits` y `INSERT outbox_events`. Si el médico no tiene la especialidad activa, el trigger lo rechaza: `ROLLBACK` y `422 DOCTOR_SPECIALTY_INVALID`. Si la auditoría falla, `ROLLBACK` y `500`.
6. **Resultado:** admisión `open` sin cama (`201`); la interfaz abre el selector de cama.

### B · Buscar y asignar cama

1. **Acceso:** permiso `camas.asignar`; sin él, `401` o `403`.
2. **Episodio:** `404` si no existe; `409 EPISODE_NOT_OPEN` si no es `inpatient` o no está abierto.
3. **Cama previa:** si el episodio ya tiene una estancia abierta, `409 EPISODE_ALREADY_HAS_BED`. Cambiar de cama es un traslado (ASII-08).
4. **Búsqueda (UC-ADM-03):** camas `available` en salas `active` y sin estancia abierta. Si no hay, `200` con lista vacía y la admisión queda **en espera de cama** (UC-ADM-09).
5. **Bloqueo:** `BEGIN` y `SELECT … FOR UPDATE` de la cama y su sala. Si la cama ya no está disponible o la sala se inactivó, `ROLLBACK` y `409 BED_NOT_AVAILABLE`.
6. **Asignación (UC-ADM-04):** `INSERT bed_stays`, `UPDATE beds SET status = 'occupied'`, auditoría y `outbox_events`.
   - Si la exclusión GiST o el índice único detectan otra asignación simultánea: `ROLLBACK` y `409 BED_ALREADY_ASSIGNED`.
   - Si la auditoría falla: `ROLLBACK` y `500`.
7. **Resultado:** `201` con la estancia, sala, cama y hora de inicio.

### C · Consultar admisión y cama asignada

1. **Acceso:** permiso `admisiones.ver`; sin él, `401` o `403`.
2. **Episodio:** si no existe en el tenant, `404`.
3. **Detalle:** episodio, médico responsable y, si hay estancia abierta, sala, cama y fecha de asignación; si no, «En espera de cama» con el tiempo de espera.
4. **Auditoría de lectura:** se registra sin bloquear la consulta.
5. **Resultado:** `200`.

## Riesgos y pendientes detectados en el esquema

| # | Hallazgo | Impacto | Propuesta |
|---|---|---|---|
| R1 | Ninguna restricción impide que un paciente tenga **dos episodios `inpatient` abiertos**. | Dos admisiones simultáneas del mismo paciente (por ejemplo, desde dos puestos de admisión). | Validarlo en `AdmissionService` y proponer en la Semana 5 un índice único parcial `care_episodes (local_patient_id) WHERE type = 'inpatient' AND status = 'open'`, acordado con *Expediente* (ASII-10). |
| R2 | `beds.status` y `bed_stays` no están sincronizados por la base: no hay trigger que marque la cama como `occupied` al abrir una estancia. | Una cama podría figurar `available` con una estancia abierta, o al revés, si algún módulo actualiza solo una de las dos tablas. | Actualizar ambas en la misma transacción y acordar con *Catálogo de camas* (ASII-06) y *Traslados y altas* (ASII-08) una sola regla: un trigger, o derivar la ocupación de `bed_stays`. |
| R3 | No hay entidad para la **lista de espera** de camas ni para reservas. | La espera se deduce como «episodio `inpatient` abierto sin estancia»; no hay prioridad ni orden explícito. | Mantener la deducción en la Semana 1 y evaluar en la Semana 5 si se necesita prioridad, coordinado con ASII-09. |
| R4 | `care_episodes` no guarda el médico responsable; se modela con `care_assignments`, cuyo `scope` es un JSON obligatorio y no vacío sin contenido definido. | Sin un acuerdo, cada módulo llenaría `scope` distinto. | Proponer `{"role": "attending"}` para el médico responsable de la admisión y acordarlo con *Médicos* (ASII-04) y *Expediente* (ASII-10). |
| R5 | `beds.type` es texto libre. | Filtrar por tipo de cama depende de valores no normalizados. | Acordar un catálogo de tipos con ASII-06. |
| R6 | `local_access_audits` (slice H8) aún no tiene migración (tarea 3.7). | Sin ella no se garantiza «asignación y auditoría en la misma transacción». | Reutilizar la propuesta de columnas del Anexo A de Notas SOAP (ASII-11) y validarla con el grupo antes de implementar. |

## Dependencias con otros módulos

- **Depende de:**
  - *Pacientes* (ASII-03): `local_patients` (ya existe).
  - *Médicos* (ASII-04): `doctor_profiles` y `doctor_specialties` para el médico responsable.
  - *Catálogo de salas, wards y camas* (ASII-06): `wards` y `beds` (ya existen).
  - *RBAC* (ASII-02): permisos `admisiones.*` y `camas.asignar`.
  - Esquema HOSPITAL: `local_access_audits` y `outbox_events` (slice H8).
- **Dependen de este módulo:**
  - *Traslados, altas y liberación de camas* (ASII-08): parte de la estancia abierta creada aquí.
  - *Dashboard de ocupación* (ASII-09): lee las estancias y las admisiones en espera.
  - *Expediente* (ASII-10), *Notas SOAP* (ASII-11) y *Signos vitales* (ASII-13): registran sobre el episodio `inpatient`.
