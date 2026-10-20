# Comunicación de la tabla `doctor_schedules` (entidad 28)

Registro de a quién selehcomunicó la solicitud y con qué acuerdos, conforme al plan
de integración: "nadie espera a nadie, el contrato se publica antes que el código y
todo bloqueo se avisa el mismo día".

| Destinatario | Canal | Qué se comunicó | Estado |
|---|---|---|---|
| Módulo de médicos y especialidades (ASII-04) | Issue #24 | Definición literal validada contra el esquema, exclusiones, vigencia, alcance de ausencias y solicitud de aprobación de la entidad 28 | `PENDIENTE` |
| Citas y agenda (ASII-05) | Issue #207 | Formato de los slots derivados de `slot_minutes` y de la vigencia | `PENDIENTE` |
| Admisión y asignación de cama | Plan de integración | Dependencia de la disponibilidad para la asignación | `PENDIENTE` |
| Grupo | PR del módulo de BD | Publicación de la migración, las pruebas y el contrato | `PENDIENTE DE REVISIÓN` |

## Puntos que no se inventan

1. La tabla **no está** en la especificación vigente. Este PR **no cambia** el número
   de entidades: propose 28 y deja la aprobación registrada.
2. El número de archivo de la migración (`000900`) es una **propuesta**: los slices
   H5, H6 y H7 ya tienen números reservados.
3. Las ausencias (vacaciones, permisos, días festivos) **quedan fuera de alcance** y no
   se modelan.
4. La propiedad de la migración sigue **por acordar** entre el módulo de médicos y el
   módulo de verificación de BD.

## Consecuencia de no resolverlo

La disponibilidad de médicos no tiene datos persistidos; Citas y Admisión no pueden
ofrecer un horario real al paciente. La tabla queda en el PR con su migración
reversible y sus pruebas, lista para integrarse en cuanto el grupo apruebe la
entidad 28 y el número de archivo.
