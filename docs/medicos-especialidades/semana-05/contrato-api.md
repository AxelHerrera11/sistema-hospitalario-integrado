# Contrato API REST — Médicos v1

Contrato que consumen Citas (Lis), Admisión (Axel), Órdenes (Javier) y Prescripciones (María). La versión legible por máquina está en [openapi.yaml](./openapi.yaml).

## Convenciones

- **Base:** `/api/v1`. Fuera de ese prefijo, `404 ROUTE_NOT_FOUND`.
- **Formato:** JSON en petición y respuesta (`Content-Type: application/json; charset=utf-8`).
- **Trazabilidad:** toda respuesta, también las de error, lleva `trace_id` en el cuerpo y en el encabezado `X-Trace-Id`.
- **Errores:** cuerpo uniforme `{"code": "...", "trace_id": "..."}`; `code` en mayúsculas, estable y documentado abajo. Nunca se expone el detalle interno de una excepción.
- **Identidad:** el actor llega del contexto autenticado. En el micro-HIS se simula con `X-User-ID`; en Laravel sale de la sesión JWT y del middleware de tenant, no de un encabezado del cliente.
- **Fechas:** `YYYY-MM-DD` (ISO 8601); vigencias `[valid_from, valid_to)`.

## Endpoints y permisos

| Método | Ruta | Permiso RBAC | Éxito | Estado |
|---|---|---|---|---|
| `POST` | `/api/v1/doctors` | `medicos.registrar` | `201` | implementado |
| `POST` | `/api/v1/doctors/{doctor_profile_id}/specialties` | `medicos.especialidades.asociar` | `201` | implementado |
| `GET` | `/api/v1/availability?specialty={code}&date={YYYY-MM-DD}` | `medicos.disponibilidad.ver` | `200` | implementado |
| `GET` | `/api/v1/specialties` | `medicos.disponibilidad.ver` | `200` | propuesto |
| `GET` | `/api/v1/doctors/{doctor_profile_id}` | `medicos.disponibilidad.ver` | `200` | propuesto |

Los tres permisos no están todavía en el [catálogo de RBAC](../../rbac/semana-05/permisos.md): se proponen a María (ASII-02) con esta matriz inicial (supuesto SD-MED-11):

| Rol | `medicos.registrar` | `medicos.especialidades.asociar` | `medicos.disponibilidad.ver` |
|---|---|---|---|
| `admin_hospital` | X | X | X |
| `recepcion` |  |  | X |
| `medico` |  |  | X |

## Ejemplos

`POST /api/v1/doctors`

```json
{ "local_user_ref_id": "u-ana", "license_number": "COL-1", "specialty_code": "CARD", "valid_from": "2026-01-01" }
```

`201` → `{ "doctor_profile_id": "doc-1", "trace_id": "e5e3cc1c65208e5c" }`. La especialidad del alta queda como principal.

`POST /api/v1/doctors/doc-1/specialties`

```json
{ "specialty_code": "NEURO", "is_primary": false, "valid_from": "2026-02-01", "valid_to": null }
```

`201` → `{ "doctor_profile_id": "doc-1", "specialty_id": "sp-neuro", "is_primary": false, "trace_id": "..." }`

`GET /api/v1/availability?specialty=NEURO&date=2026-10-19`

```json
{ "code": "OK", "doctors": [ { "doctor_id": "doc-ana", "status": "OK", "slots": ["08:00", "09:00"] },
                             { "doctor_id": "doc-beto", "status": "SCHEDULE_NOT_CONFIGURED", "slots": [] } ], "trace_id": "..." }
```

Sin médicos vigentes para la especialidad: `200` con `code: "NO_DOCTORS_FOR_SPECIALTY"` y `doctors: []`; no es un error.

## Códigos de error

| HTTP | `code` | Cuándo |
|---|---|---|
| `401` | `UNAUTHENTICATED` | Sin actor autenticado |
| `403` | `FORBIDDEN` | El actor no tiene el permiso de la ruta; el cuerpo incluye `permission` |
| `404` | `ROUTE_NOT_FOUND` / `DOCTOR_NOT_FOUND` | Ruta inexistente / médico inexistente o inactivo |
| `409` | `LICENSE_ALREADY_REGISTERED` | Licencia ya registrada |
| `409` | `SPECIALTY_ALREADY_ASSOCIATED` / `PRIMARY_SPECIALTY_CONFLICT` | Misma especialidad solapada / dos principales en el mismo periodo |
| `422` | `PAYLOAD_INVALID` | JSON malformado o un campo con tipo incorrecto (texto, `is_primary` booleano, `valid_to` nulo o texto) |
| `422` | `INVALID_PERIOD` / `PERIOD_OUTSIDE_PROFILE` / `INVALID_DATE_RANGE` | Vigencia inválida / fuera del perfil / fecha de consulta inválida |
| `422` | `SPECIALTY_INACTIVE` | Especialidad inexistente o inactiva |
| `500` | `DOCTOR_PERSISTENCE_ERROR` | Falló la base o la auditoría: todo se revierte |
| `503` | `AUTHORIZATION_UNAVAILABLE` | RBAC no respondió: se niega el acceso (falla cerrada) |

## Propiedad de datos

Médicos es dueño de `doctor_profiles`, `doctor_specialties` y del catálogo `specialties`, y del horario (`doctor_schedules`, propuesto a Javier). Lee `appointments` (Citas) solo a través de `AppointmentReader` para restar las franjas ocupadas; nunca escribe en tablas de otro módulo. Los consumidores obtienen médicos y franjas por este contrato, no por SQL.

## Consistencia

- Alta y asociación son atómicas: perfil, especialidad y auditoría en una sola transacción.
- La disponibilidad es una lectura en el momento de la consulta. Que una franja salga libre no la reserva: Citas debe confirmar al crear la cita, y su restricción `appointments_doctor_active_period_exclusion` rechaza el choque.

## Versionado

Los campos nuevos se agregan sin romper a los clientes. Un cambio incompatible sube a `/api/v2` y `/api/v1` se mantiene mientras haya consumidores.
