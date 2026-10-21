# Dependencias del módulo — Micro-HIS Médicos

## Regla de dependencia entre capas

| Capa | Depende de | No depende de |
|---|---|---|
| Presentation (`DoctorHttpHandler`) | Casos de uso de Application, `DoctorRuleViolation` | PDO, SQL, reglas de negocio |
| Application (casos de uso y puertos) | Domain y sus propios puertos | PDO, HTTP, configuración |
| Domain (`Period`, `SpecialtyLink`, `SpecialtyAssignmentPolicy`, `SlotPlanner`) | Solo PHP | Application, Persistence, Presentation |
| Persistence (`PdoDoctorStore`, `ConfigScheduleProvider`) | Puertos de Application, Domain, `PDO` | Presentation |

Las flechas apuntan hacia adentro (Presentation → Application → Domain ← Persistence). Persistence depende de Application porque implementa sus interfaces.

## Matriz caso de uso → puertos

| Caso de uso | Puertos que usa | Métodos usados / disponibles |
|---|---|---|
| `RegisterDoctor` (UC-MED-01) | `DoctorProfileStore`, `SpecialtyCatalog` | 3 / 3 |
| `AssociateSpecialty` (UC-MED-02, 03) | `DoctorSpecialtyStore`, `SpecialtyCatalog` | 3 / 3 |
| `QueryAvailability` (UC-MED-05, 06) | `SpecialtyCatalog`, `DoctorsBySpecialty`, `DoctorScheduleProvider`, `AppointmentReader` | 4 / 4 |

## Persistencia y SQL

- Todas las consultas usan `prepare()` y `execute([...])`. Ningún valor de entrada se concatena en el SQL.
- `PDO::ERRMODE_EXCEPTION` está activo. Un `PDOException` dentro de la transacción hace `rollBack()` y se traduce a `500 DOCTOR_PERSISTENCE_ERROR`.
- `database/schema.sql` es un subconjunto de la migración hospital `000100` adaptado a SQLite. `appointments` es una vista mínima de solo lectura del módulo Citas.

## Dependencias externas y supuestos

| Dependencia | Estado | Tratamiento en el prototipo |
|---|---|---|
| Autenticación JWT, RBAC y tenant (ASII-01, 02) | Fuera de alcance | `X-User-ID` identifica al actor para la auditoría. |
| `doctor_schedules` | Pendiente del acuerdo C1 | `ConfigScheduleProvider` (arreglo semanal). |
| `local_access_audits` (slice H8) | Sin migración en `develop` | Tabla mínima en `schema.sql` para probar la atomicidad. |
| Citas (`appointments`) | Otro módulo | Solo lectura de `starts_at` y `status`; sin datos del paciente (RNF-MED-03). |

## Límites de la Semana 3

- La disponibilidad se consulta por un día; el rango de hasta 14 días de RF-MED-06 queda para la Semana 4.
- No se integra con el router de Laravel ni con PostgreSQL real.
- MVC con Repository y adaptadores InMemory se completan en la Semana 4.
