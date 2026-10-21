# RF/RNF y criterios de aceptación — Médicos, especialidades y disponibilidad

Se derivan de los casos de uso y las particiones A, B y C de la [Semana 1](../semana-01/README.md). Cada RF indica el UC que lo origina.

## Requerimientos funcionales

| Código | UC | Requerimiento funcional | Criterio de aceptación |
|---|---|---|---|
| RF-MED-01 | UC-MED-01, 02 | Registrar el perfil del médico con número de colegiado, `valid_from` y especialidad principal en la misma transacción. | **Dado** un usuario local activo con rol de médico, **cuando** el administrador envía datos válidos, **entonces** se crean `doctor_profiles` (`active`) y `doctor_specialties` (`is_primary = true`) y se responde `201`. |
| RF-MED-02 | UC-MED-03 | Rechazar una licencia ya registrada o un usuario con perfil activo. | **Dado** que `COL-1` ya existe, **cuando** se registra otro médico con `COL-1`, **entonces** `409 LICENSE_ALREADY_REGISTERED` (o `409 DOCTOR_ALREADY_REGISTERED`) y no se crea nada. |
| RF-MED-03 | UC-MED-04 | Solo se usan especialidades activas del catálogo. | **Dado** una especialidad `inactive` o inexistente, **cuando** se registra, asocia o consulta con ella, **entonces** `422 SPECIALTY_INACTIVE`. |
| RF-MED-04 | UC-MED-02 | Asociar especialidades adicionales con vigencia dentro de la del perfil. | **Dado** un médico `active`, **cuando** se asocia `MED-INT` dentro de su vigencia, **entonces** se crea la asociación y se responde `201`; fuera de la vigencia, `422 PERIOD_OUTSIDE_PROFILE`. |
| RF-MED-05 | UC-MED-03 | Rechazar la misma especialidad solapada y una segunda principal. | **Dado** que el médico tiene `CARD` principal vigente, **cuando** se asocia `CARD` otra vez o `NEURO` como principal en el mismo periodo, **entonces** `409 SPECIALTY_ALREADY_ASSOCIATED` o `409 PRIMARY_SPECIALTY_CONFLICT`. |
| RF-MED-06 | UC-MED-05, 06 | Consultar las franjas libres por especialidad en un rango de hasta 14 días. | **Dado** un horario con 08:00, 08:30 y 09:00 y una cita `confirmed` a las 08:30, **cuando** la recepción consulta, **entonces** `200` con 08:00 y 09:00 y `generated_at`. Rango inválido: `422 INVALID_DATE_RANGE`. Sin médicos: `200` vacío con `NO_DOCTORS_FOR_SPECIALTY`. |
| RF-MED-07 | UC-MED-06 | Informar al médico sin horario configurado sin ocultarlo. | **Dado** un médico sin horario, **cuando** se consulta, **entonces** aparece con `SCHEDULE_NOT_CONFIGURED` y sin franjas. |

## Requerimientos no funcionales

| Código | Categoría | Requerimiento no funcional | Criterio de aceptación (medible) |
|---|---|---|---|
| RNF-MED-01 | Seguridad | JWT, tenant y permisos `medicos.registrar`, `medicos.asociar_especialidad`, `medicos.ver_disponibilidad`. | El 100 % de las peticiones sin permiso o con `X-Tenant-ID` ajeno reciben `401`/`403`. |
| RNF-MED-02 | Consistencia | Alta, especialidad principal y auditoría atómicas; sin duplicados por concurrencia. | Con 10 altas simultáneas de la misma licencia queda 1 perfil; si falla la auditoría, 0 perfiles sin traza. |
| RNF-MED-03 | Privacidad | La disponibilidad no expone datos del paciente. | `AppointmentReader` devuelve solo intervalos ocupados; 0 campos de paciente en la respuesta. |
| RNF-MED-04 | Rendimiento | Consulta de disponibilidad ágil. | ≤ 500 ms (p95) para 20 médicos y 14 días. |
| RNF-MED-05 | Mantenibilidad (ISP) | Ningún cliente depende de métodos que no usa. | La métrica de la [demo](./evidencia/demo-isp.php) reporta 0 métodos sin usar en `DoctorRegistrar`, `SpecialtyAssociator` y `AvailabilityService`, y ningún adaptador lanza «no soportado». |

## Escenarios de aceptación

```gherkin
Escenario: Alta con especialidad principal
  Dado que el administrador tiene permiso medicos.registrar y CARD está activa
  Cuando registra a la Dra. Ana con licencia COL-1 y CARD como principal
  Entonces recibe 201 y el perfil queda active con CARD is_primary

Escenario: Segunda especialidad principal
  Dado que la Dra. Ana tiene CARD como principal vigente
  Cuando el administrador asocia NEURO como principal en el mismo periodo
  Entonces recibe 409 PRIMARY_SPECIALTY_CONFLICT

Escenario: Disponibilidad sin franjas ocupadas
  Dado que la Dra. Ana atiende CARD a las 08:00, 08:30 y 09:00 y tiene cita confirmed a las 08:30
  Cuando la recepcionista consulta la disponibilidad de CARD
  Entonces recibe 200 con las franjas 08:00 y 09:00 y sin datos del paciente

Escenario: Adaptador de solo lectura para la agenda (ISP)
  Dado que el módulo Citas expone una vista de solo lectura de médicos, horarios y citas
  Cuando se conecta a AvailabilityService
  Entonces no necesita implementar métodos de alta ni de asociación
```
