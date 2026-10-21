# Comunicación de la tabla `doctor_schedules` (entidad 28)

Registro de a quién se comunicó la solicitud y con qué acuerdos, conforme al plan
de integración: "nadie espera a nadie, el contrato se publica antes que el código y
todo bloqueo se avisa el mismo día".

| Destinatario | Canal | Qué se comunicó | Estado |
|---|---|---|---|
| Módulo de médicos y especialidades (ASII-04) | Issue #24 | Definición literal validada contra el esquema, exclusiones, vigencia, alcance de ausencias, aprobación de la entidad 28 y opciones de la regla de especialidad | `RESUELTO` (20-oct-2026) |
| Citas y agenda (ASII-05) | Issue #207 | Formato de los slots derivados de `slot_minutes` y de la vigencia | `PENDIENTE` |
| Admisión y asignación de cama | Plan de integración | Dependencia de la disponibilidad para la asignación | `PENDIENTE` |
| Grupo | PR del módulo de BD | Publicación de la migración, las pruebas y el contrato | `PENDIENTE DE REVISIÓN` |

## Puntos que no se inventan

1. La tabla **no está** en la especificación vigente. Este PR **no cambia** el número
   de entidades: propone 28 y deja la aprobación registrada.
2. El número de archivo de la migración (`000900`) está **acordado** con ASII-04
   (20-oct-2026): los slices H5, H6 y H7 ya tienen números reservados.
3. Las ausencias (vacaciones, permisos, días festivos) **quedan fuera de alcance** y no
   se modelan.
4. La propiedad de la migración: la mantiene el **módulo de verificación de BD**,
   según el mismo acuerdo de ASII-04.

## Regla de especialidad: opción B acordada

ASII-04 respondió en el issue #24 el 20 de octubre de 2026: **A-3 opción (b), trigger de
integridad en la misma migración**.

| Opción | Garantía | Estado |
|---|---|---|
| A. Regla de aplicación | Ninguna en la base de datos; la API valida al escribir | Descartada |
| B. Trigger de integridad | Rechaza el INSERT o UPDATE si no hay `doctor_specialties` activa para ese médico y esa especialidad en la vigencia del horario | **Acordada e implementada en este PR** |
| C. FK compuesta `(doctor_profile_id, specialty_id)` | Sería la más fuerte | **No implementable**: `doctor_specialties` no tiene `UNIQUE (doctor_profile_id, specialty_id)` porque el modelo es por vigencias; PostgreSQL responde `ERROR: there is no unique constraint matching given keys for referenced table "doctor_specialties"` (SQLSTATE 42830) |

La migración crea el trigger `doctor_schedules_specialty_guard`, que rechaza con SQLSTATE
23514 un horario cuya especialidad no está vigente para ese médico. Las pruebas cubren el
rechazo por especialidad ausente, el rechazo por especialidad vencida y la aceptación
dentro de la vigencia.

## Consecuencia de no resolverlo

La disponibilidad de médicos no tiene datos persistidos; Citas y Admisión no pueden
ofrecer un horario real al paciente. La tabla queda en el PR con su migración
reversible y sus pruebas, lista para integrarse en cuanto el grupo apruebe la
entidad 28 y el número de archivo.
