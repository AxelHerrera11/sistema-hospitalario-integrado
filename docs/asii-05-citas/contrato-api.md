# Contrato API de citas — ASII-05

- **Versión:** 1.0 (propuesta, previa al código)
- **Fecha:** 18/10/2026 — publicación antes de la implementación, según el plan de integración 17–23 oct.
- **Base:** `/api/v1` (igual que `resources/js/plugins/axios.js`), autenticación `Authorization: Bearer <jwt>` y cabecera obligatoria `X-Tenant-ID`.

## 1. Fuente de verdad: tabla `appointments`

Todo el contrato se deriva de la migración real `database/migrations/hospital/2026_08_11_000300_create_scheduling_and_bed_tables.php`; no se crea ninguna tabla nueva.

| Columna | Tipo | Regla de negocio (constraint real) |
|---|---|---|
| `appointment_id` | uuid PK | `gen_random_uuid()` |
| `local_patient_id` | uuid NOT NULL | FK `local_patients`; **solape prohibido** con citas activas del mismo paciente (exclusión GiST) |
| `doctor_profile_id` | uuid NOT NULL | FK `doctor_profiles`; **solape prohibido** con citas activas del mismo doctor (exclusión GiST) |
| `specialty_id` | uuid NOT NULL | FK `specialties`; el trigger exige que el doctor tenga la especialidad **activa** en la fecha de `starts_at` (`appointments_specialty_trigger`) |
| `starts_at` / `ends_at` | timestamptz NOT NULL | `ends_at > starts_at`; `completed` exige `ends_at <= now()` (`appointments_status_time_check`) |
| `status` | text | `scheduled` → `confirmed` → `completed`; o `cancelled` (`appointments_status_check`) |
| `reason` | text NOT NULL | no vacío (`btrim(reason) <> ''`) |
| `created_at` / `updated_at` | timestamptz | manejados por Laravel |

Reglas adicionales de los triggers:

- **Filas finalizadas son inmutables:** `completed` o `cancelled` no admiten cambios (`appointments_finalized_immutable`).
- **No existe borrado físico:** `DELETE` siempre falla (`appointments_delete_protection`); la cancelación es por transición de estado.

Las exclusiones GiST solo aplican a estados `scheduled` y `confirmed`; `23P01` (exclusion violation) se traduce a **HTTP 409**.

## 2. Endpoints

Todas las rutas van bajo el grupo `tenant` + `jwt.auth` (patrón de `routes/api.php`) y, cuando María publique el middleware `permission:`, bajo `permission:citas.*`.

| Método y ruta | Permiso | Descripción |
|---|---|---|
| `GET /citas` | `citas.ver` | Listado paginado con filtros |
| `POST /citas` | `citas.crear` | Alta de cita (estado inicial `scheduled`) |
| `GET /citas/{appointment_id}` | `citas.ver` | Detalle |
| `PUT /citas/{appointment_id}` | `citas.crear` | Reprogramación (cambiar horario, doctor, especialidad o motivo) solo en estados `scheduled`/`confirmed` |
| `POST /citas/{appointment_id}/confirmar` | `citas.confirmar` | `scheduled` → `confirmed` |
| `POST /citas/{appointment_id}/completar` | `citas.confirmar` | `confirmed` → `completed` (requiere `ends_at <= now()`) |
| `POST /citas/{appointment_id}/cancelar` | `citas.cancelar` | `scheduled`/`confirmed` → `cancelled`; sin cuerpo |
| `DELETE /citas/{appointment_id}` | — | **405** `appointments_delete_protection` (lo decide la base, no el controlador) |

### 2.1 Filtros de `GET /citas`

| Parámetro | Tipo | Notas |
|---|---|---|
| `status` | `scheduled\|confirmed\|completed\|cancelled` | repetible |
| `doctor_profile_id` | uuid | |
| `local_patient_id` | uuid | |
| `date_from` / `date_to` | `Y-m-d` | solapan contra `[starts_at, ends_at)` |
| `page` / `per_page` | entero | `per_page` máximo 100, default 20 |

### 2.2 Cuerpo de `POST /citas` y `PUT /citas/{id}`

```json
{
  "local_patient_id": "uuid",
  "doctor_profile_id": "uuid",
  "specialty_id": "uuid",
  "starts_at": "2026-10-21T09:00:00-06:00",
  "ends_at": "2026-10-21T09:30:00-06:00",
  "reason": "Control de diabetes"
}
```

La cancelación no acepta cuerpo: `appointments` no tiene columna para el motivo de cancelación y `reason` conserva el motivo original de la cita. Si se requiere registrar el motivo del cancelamiento, debe proponerse una migración aparte, fuera del alcance de este módulo (que no crea tablas).

## 3. Respuestas de éxito

Colección:

```json
{
  "data": [
    {
      "appointment_id": "uuid",
      "local_patient_id": "uuid",
      "doctor_profile_id": "uuid",
      "specialty_id": "uuid",
      "starts_at": "2026-10-21T15:00:00.000000Z",
      "ends_at": "2026-10-21T15:30:00.000000Z",
      "status": "confirmed",
      "reason": "Control de diabetes",
      "created_at": "...",
      "updated_at": "..."
    }
  ],
  "meta": { "current_page": 1, "per_page": 20, "total": 1, "last_page": 1 }
}
```

- `POST /citas` → **201** con el recurso en `data`.
- Transiciones → **200** con el recurso actualizado en `data`.
- El envoltorio `data`/`meta` es **propuesto**: el formato común de respuestas lo acuerda el grupo (Yamilet/Javier, reunión del mar 20) y aquí se alineará si difiere.

## 4. Errores

Envolvente propuesto (§7 del contrato UI base): `{ "message": "...", "code": "...", "trace_id": "..." }`.

| HTTP | `code` | Cuándo |
|---|---|---|
| 401 | `unauthenticated` | token inválido/expirado (hoy solo devuelve `message`) |
| 403 | `forbidden` | sin `citas.*` |
| 404 | `appointment_not_found` | id inexistente en el tenant |
| 405 | `appointments_delete_protection` | `DELETE` |
| 409 | `appointments_overlap` | exclusión GiST: doctor o paciente con solape activo |
| 409 | `appointments_finalized_immutable` | editar `completed`/`cancelled` |
| 422 | `validation_failed` | errores de validación (`errors` con detalle), `ends_at <= starts_at`, `reason` vacío |
| 422 | `appointments_specialty_trigger` | doctor sin especialidad activa en `starts_at` |
| 422 | `appointments_status_time_check` | transición inválida (p. ej. completar antes de `ends_at`) |

Los códigos SQL se capturan por `SQLSTATE` (`23P01`, `23514`, `P0001` con el mensaje del trigger) en una capa de traducción compartida del módulo; `trace_id` queda pendiente del acuerdo de formato común (no existe aún en `JwtAuth`/`TenantMiddleware`).

## 5. Permisos propuestos al catálogo de María (RBAC)

`citas.ver`, `citas.crear`, `citas.confirmar`, `citas.cancelar` — propuestos en la reunión con María; hasta que el middleware `permission:` exista, la autorización efectiva queda para la segunda semana de este módulo (límite de 400 líneas por PR).

## 6. Relación con DIP (principio del módulo)

El contrato se materializa en la segunda tarea como interfaz `AppointmentRepositoryContract` (puerto) + adaptador Eloquent; el controlador dependerá de la interfaz, no de `Appointment::`. Este documento es la fuente de la misma y cualquier cambio de endpoints se refleja primero aquí.
