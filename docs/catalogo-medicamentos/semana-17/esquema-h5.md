# Esquema H5 — tabla `medications` (HOSPITAL)

Migración `database/migrations/hospital/2026_08_11_000500_create_medication_tables.php`, número reservado para el slice H5 en `openspec/changes/postgresql-federated-schema-rebuild/tasks.md` (tarea 3.5).

| Columna | Tipo | Regla |
|---|---|---|
| `medication_id` | `uuid` | Clave primaria; `DEFAULT gen_random_uuid()` |
| `code` | `text` | Único; `^[A-Z0-9][A-Z0-9-]{1,19}$` (el dominio lo pasa a mayúsculas) |
| `name`, `active_ingredient`, `pharmacological_group` | `text` | Recortados, de 2 a 120 caracteres |
| `validity` | `text` | `active` o `inactive`; nace `active` |
| `created_at`, `updated_at` | `timestamptz` | `now()` por defecto |

- La vigencia es del registro del catálogo: **no** representa el vencimiento de lotes, que queda fuera del alcance.
- Una base HOSPITAL corresponde a un hospital, por eso la tabla no lleva columna de hospital: el aislamiento lo da la conexión del tenant.
- `prescriptions`, del mismo slice, pertenece a Prescripciones y no se crea en esta migración.
- Pruebas: `tests/Database/Hospital/MedicationIntegrityTest.php` (PostgreSQL real).

**Pendiente de acuerdo:** que el responsable de Prescripciones confirme si `prescriptions` se crea en una migración propia del slice (por ejemplo `000501`) en lugar de compartir `000500`.
