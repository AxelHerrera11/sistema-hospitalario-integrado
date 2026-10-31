# Contrato API REST — Médicos v1

> **Estado:** el contrato de la Semana 5 sigue vigente. Las Semanas 16 a 18 lo integraron en Laravel y **precisaron** su comportamiento real (códigos de error de la plataforma, límites, zona horaria, `slot_minutes`); eso está en la sección final [«Actualización de las Semanas 16 a 18»](#actualización-de-las-semanas-16-a-18-api-en-laravel). Donde difieran, manda esa sección.

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
| `POST` | `/api/v1/doctors/{doctor_profile_id}/specialties` | `medicos.asociar_especialidad` | `201` | implementado |
| `GET` | `/api/v1/availability?specialty={code}&date={YYYY-MM-DD}` | `medicos.ver_disponibilidad` | `200` | implementado |
| `GET` | `/api/v1/specialties` | `medicos.ver_disponibilidad` | `200` | propuesto |
| `GET` | `/api/v1/doctors/{doctor_profile_id}` | `medicos.ver_disponibilidad` | `200` | propuesto |

Los tres permisos vienen de RNF-MED-01 (Semana 2). **Actualización (Semanas 16 a 18):** ya están en el catálogo de RBAC de la plataforma (`config/rbac.php`) y los exigen las rutas de Laravel; el rol `superadmin` también los tiene y `enfermeria` no tiene ninguno. Matriz de la propuesta original (supuesto SD-MED-11), vigente salvo lo anterior:

| Rol | `medicos.registrar` | `medicos.asociar_especialidad` | `medicos.ver_disponibilidad` |
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

Médicos es dueño de `doctor_profiles`, `doctor_specialties` y del catálogo `specialties`, y lee el horario de `doctor_schedules` (entidad 28, aprobada por este módulo el 20 de octubre de 2026; su migración la mantiene el módulo de verificación de base de datos, ASII-09). Lee `appointments` (Citas) solo a través de `AppointmentReader` para restar las franjas ocupadas; nunca escribe en tablas de otro módulo. Los consumidores obtienen médicos y franjas por este contrato, no por SQL.

## Consistencia

- Alta y asociación son atómicas: perfil, especialidad y auditoría en una sola transacción.
- La disponibilidad es una lectura en el momento de la consulta. Que una franja salga libre no la reserva: Citas debe confirmar al crear la cita, y su restricción `appointments_doctor_active_period_exclusion` rechaza el choque.

## Versionado

Los campos nuevos se agregan sin romper a los clientes. Un cambio incompatible sube a `/api/v2` y `/api/v1` se mantiene mientras haya consumidores.

## Actualización de las Semanas 16 a 18: API en Laravel

Las tres rutas se exponen en Laravel (`routes/api.php`, `DoctorApiController`) con `tenant` y `jwt.auth`, y cada una declara su permiso. El `Router` del micro-HIS se reutiliza tal cual, así que **los códigos y estados del módulo no cambian**. Lo que sigue es lo que se verificó contra el servidor real (21 de 21 verificaciones, ver [e2e-real.md](../semana-18/e2e-real.md)) y que el contrato original no decía.

### Autenticación y cabeceras

| Cabecera | Obligatoria | Detalle |
|---|---|---|
| `Authorization: Bearer <JWT>` | Sí | Se obtiene en `POST /api/v1/auth/login` |
| `X-Tenant-ID` | Sí | UUID del hospital; debe coincidir con el del token |
| `X-User-ID` | **No** | Solo existe en el micro-HIS aislado. En Laravel el cliente no puede fijar el actor: se resuelve en el servidor a `local_user_refs.local_user_ref_id` |

### Respuestas que emite el módulo

Llevan `trace_id` en el cuerpo y `X-Trace-Id` en la cabecera (16 caracteres hexadecimales, distinto en cada petición) y `Cache-Control: no-store, private`. Códigos y estados del contrato original, más:

| HTTP | `code` | Cuándo |
|---|---|---|
| `403` | `ACTOR_NOT_PROVISIONED` | El usuario autenticado no tiene una referencia local activa en el hospital: no se escribe nada |
| `404` | `DOCTOR_NOT_FOUND` | También cuando el identificador no es un uuid |
| `409` | `LICENSE_ALREADY_REGISTERED` | La licencia se compara sin espacios y en mayúsculas: ` col-9 ` es `COL-9` |
| `409` | `USER_ALREADY_REGISTERED` | El usuario local ya tiene un perfil de médico activo |
| `422` | `USER_REF_NOT_FOUND` | `local_user_ref_id` es un uuid que no existe |
| `422` | `PAYLOAD_INVALID` | Ahora también: `specialty` ausente en la disponibilidad, texto que supera el límite y `local_user_ref_id` que no es uuid |
| `503` | `DOCTOR_STORAGE_UNAVAILABLE` | La base HOSPITAL no responde. Un RBAC caído, en cambio, lo informa la plataforma como `503 RBAC_UNAVAILABLE` o `RBAC_CONTEXT_UNAVAILABLE` (en lugar de `AUTHORIZATION_UNAVAILABLE`) |

El `500 DOCTOR_PERSISTENCE_ERROR` queda para fallos reales de la base: ya no se usa para entradas inválidas del cliente.

### Respuestas que emite la plataforma (formato propio)

No pasan por el módulo y **no** usan `{"code", "trace_id"}` en todos los casos:

| HTTP | Cuerpo | Cuándo |
|---|---|---|
| `401` | `{"error": "TOKEN_MISSING", "message": ...}` | Sin token (`jwt.auth`) |
| `400` | `{"error": "TENANT_REQUIRED", ...}` | Falta `X-Tenant-ID` |
| `403` | `{"error": "TENANT_MISMATCH", ...}` | El hospital de la cabecera no es el del token |
| `403` | `{"code": "RBAC_DENIED", "trace_id": "trace-..."}` | Sin el permiso de la ruta (sustituye a `FORBIDDEN`; no incluye `permission`) |
| `429` | `{"message": "Too Many Attempts."}` | Límite de tasa; trae `Retry-After` y `X-RateLimit-*`, sin `trace_id` |

Una `GET` a una ruta que no existe bajo `/api/v1` no responde `404 ROUTE_NOT_FOUND` en Laravel: la plataforma sirve la aplicación web (HTML con `200`). Es un comportamiento de la plataforma, no del módulo.

### Límites

| Qué | Valor |
|---|---|
| Longitud de `license_number` y `local_user_ref_id` | hasta 64 caracteres |
| Longitud de `specialty_code` (alta, asociación y consulta) | hasta 32 caracteres |
| Longitud del identificador de médico en la ruta | hasta 64 caracteres |
| Escrituras (`POST`) | 30 por minuto y usuario |
| Consulta de disponibilidad | 120 por minuto y usuario |

Los contadores de escritura y de consulta son independientes. El límite solo funciona con una caché compartida y persistente (`CACHE_STORE=database` o equivalente): con `array` no cuenta nada.

### Disponibilidad

- Las franjas salen de `doctor_schedules`: cada fila trae su `slot_minutes` (5 a 240) y la consulta parte el horario en bloques de ese tamaño, no de 30 minutos fijos.
- Una franja se ofrece libre solo si ninguna cita activa (`scheduled` o `confirmed`) la solapa: una cita de 08:15 a 08:45 ocupa 08:00 y 08:30 con bloques de 30 minutos.
- Las horas son **locales del hospital** (`hospitals.timezone` del tenant; si no es una zona válida, la de la aplicación). Una cita que cruza la medianoche ocupa su parte de cada día.
- `status` es `OK` (con `slots`, que puede ser `[]`) o `SCHEDULE_NOT_CONFIGURED` (el médico no tiene horario vigente en esa fecha). La respuesta nunca incluye datos del paciente.
- Rendimiento medido (RNF-MED-04): un día con 20 médicos, p95 ~13 ms; 14 consultas seguidas, p95 145 a 194 ms (ver [rendimiento.md](../semana-18/rendimiento.md)).

### Lo que el contrato sigue sin ofrecer

No hay rutas para **cargar horarios**, **listar especialidades o médicos**, ni para **dar de baja** a un médico o cerrar la vigencia de una especialidad. Las dos filas «propuesto» de la tabla de endpoints siguen sin implementarse.
