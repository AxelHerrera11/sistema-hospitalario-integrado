# Acuerdos pendientes de la tabla `doctor_schedules`

Ficha de acuerdos del módulo **Verificación de Base de Datos PostgreSQL** (ASII-09) para la tabla
`doctor_schedules`. El código está verificado y en el PR #413; lo que falta son **tres acuerdos
del grupo**, con responsable y consequence de no resolverlos. Este documento existe para que la
decisión no dependa de la memoria de nadie y para que cada dueño pueda responder con una sola
palabra en su issue.

## Estado técnico verificado

| Comprobación | Resultado | Evidencia |
|---|---|---|
| Migración reversible y 13 pruebas | Correcta | `php vendor/bin/phpunit tests/Database/Hospital/DoctorScheduleIntegrityTest.php` → OK (13 tests, 111 assertions) |
| Compatibilidad con `develop` vigente | Correcta | `php vendor/bin/phpunit --testsuite Database` → OK (72 tests, 849 assertions) sobre `origin/develop` |
| Revisión técnica de la revisora | "La verificación técnica pasa; queda bloqueado por acuerdos" | Revisión del PR #413 del 20 de octubre de 2026 |
| Restricción del esquema | `doctor_specialties` no tiene `UNIQUE (doctor_profile_id, specialty_id)` | PostgreSQL responde `ERROR: there is no unique constraint matching given keys for referenced table "doctor_specialties"` (SQLSTATE 42830) |

## Los tres acuerdos

| ID | Acuerdo | Opciones | Quién decide | Dónde se responde | Qué bloquea | Fecha límite | Estado |
|---|---|---|---|---|---|---|---|
| A-1 | ¿Se aprueba la **entidad 28** (`doctor_schedules`) en la especificación federada y quién es el propietario de su migración? | (a) Se aprueba la entidad 28 y el módulo de médicos (ASII-04) publica la migración. (b) Se aprueba la entidad 28 y la mantiene el módulo de verificación de BD. (c) No se aprueba la entidad 28. | ASII-04 Médicos y especialidades (`Jhos-hgnu`), con la integración de `Yamilet1235` | Issue #24 | La integración del PR #413 completa | Jueves 22 de octubre, 12:00 | `PENDIENTE` |
| A-2 | ¿Qué **número de archivo** definitivo lleva la migración? | (a) `2026_08_11_000900_create_doctor_schedules.php` (propuesta actual). (b) Otro número del bloque H5/H6/H7, que ya están reservados. | Grupo, coordinado por `Yamilet1235` | Issue #36 o este PR | Renombrar la migración antes de aplicarla en `develop` | Jueves 22 de octubre, 12:00 | `PENDIENTE` |
| A-3 | ¿Cuál es la **regla de especialidad** (un horario puede quedar en una especialidad que el médico no tiene en `doctor_specialties`)? | (a) Regla de aplicación: la API valida al escribir. (b) Trigger de integridad: la base rechaza el `INSERT` o `UPDATE` sin `doctor_specialties` vigente. (c) FK compuesta `(doctor_profile_id, specialty_id)`: **no implementable** con el esquema vigente. | ASII-04 Médicos y especialidades (`Jhos-hgnu`) | Issue #24 | El cierre del PR #413: con (a) no hay cambio de código; con (b) el trigger entra en la misma migración sin superar las 400 líneas del PR | Jueves 22 de octubre, 12:00 | `PENDIENTE` |

## Consecuencia de no resolver

- Sin **A-1**, la tabla no entra en `develop`: el PR #413 no debe integrarse aunque el código sea
  correcto, y Citas y Admisión siguen sin horario real de médicos.
- Sin **A-2**, la migración queda con un número de archivo provisional; si el grupo asigna otro
  después de aplicarla en `develop`, hay que renombrar una migración ya ejecutada.
- Sin **A-3**, el PR #413 no puede aprobarse, porque la regla vigente es una de las tres y hay que
  elegirla de forma explícita. El esquema permite horarios en especialidades que el médico no
  tiene: en la verificación manual de la revisión quedaron 5 horarios de un médico sin `doctor_specialties`.

En los tres casos **no se decide por omisión**: mientras no haya respuesta, el estado sigue
`PENDIENTE` y el módulo no inventa la decisión.

## Lo que este módulo NO decide

- El número de entidades de la especificación (27) no se cambia desde este módulo.
- La propiedad de las migraciones de otros módulos (laboratorio, alertas) no se toca: las publica
  su dueño.
- Los datos de la tabla se crean en el módulo de médicos; este módulo solo verifica el esquema.

## Trazabilidad

| Elemento | Referencia |
|---|---|
| Migración y pruebas | PR #413 (`feature/asii-09-doctor-schedules-javifa2324`, `5b2bd8c`) |
| Contrato funcional | `docs/verificacion-postgresql/doctor-schedules/contrato.md` |
| Comunicación a los módulos | `docs/verificacion-postgresql/doctor-schedules/comunicacion.md` |
| Decisión de ASII-04 (A-1 y A-3) | Issue #24 |
| Formato de slots derivado, para ASII-05 | Issue #207 |
| Estado del módulo de verificación de BD | Issue #36 |
