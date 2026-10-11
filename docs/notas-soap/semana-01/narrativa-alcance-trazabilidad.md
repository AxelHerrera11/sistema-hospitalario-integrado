# Narrativa de alcance y trazabilidad — Notas SOAP y diagnósticos

## Propósito

Que el médico tratante documente cada atención en una **nota SOAP** (Subjetivo, Objetivo, Análisis y Plan) y le **asocie diagnósticos clínicos codificados con CIE-10**, de modo que la nota firmada sea la evidencia clínica oficial del episodio: completa, con un diagnóstico principal validado, auditada e inmutable.

## Alcance funcional

**Incluido**

- Registrar una nota SOAP en borrador dentro de un episodio de atención activo del paciente (`clinical_notes` con `note_type = 'soap'`, `status = 'draft'`).
- Asociar diagnósticos a la nota en borrador:
  - Código validado contra el catálogo CIE-10; la descripción se toma del catálogo, no del texto libre del usuario.
  - Tipo `primary`, `secondary` o `differential`, y fecha de inicio (`onset_date`) no futura.
  - Sin códigos repetidos en la misma nota y con un solo diagnóstico principal.
- Firmar la nota: exige las cuatro secciones con contenido y exactamente un diagnóstico principal activo. Una vez firmada es inmutable.
- Corregir una nota firmada creando una nueva versión en borrador (`supersedes_id` + `correction_reason`), que luego se firma.
- Consultar las notas SOAP y diagnósticos de un episodio (médico y enfermería).
- Auditar la firma y las correcciones en la tabla local `local_access_audits` de HOSPITAL, en la misma transacción que la nota.

**Fuera de alcance**

- Mantenimiento del catálogo CIE-10 (alta, edición, versiones del catálogo).
- Resolver un diagnóstico (`resolved`) o marcarlo como erróneo (`entered_in_error`): el esquema ya lo soporta y se detalla en la Semana 5.
- Prescripciones y órdenes de laboratorio derivadas del Plan: módulos *Prescripciones electrónicas* y *Órdenes de laboratorio*.
- Signos vitales mostrados en la sección Objetivo: los aporta el módulo *Signos vitales* y se integran como lectura.
- Plantillas de nota por especialidad y dictado por voz (posibles mejoras futuras).

## Supuestos y restricciones

- Solo registra, asocia y firma un médico con `care_assignment` activo en el episodio. El esquema ya lo exige con los triggers `clinical_notes_author_assignment_trigger` y `diagnoses_author_assignment_trigger`.
- El diagnóstico debe tener **el mismo episodio y el mismo autor** que la nota a la que se asocia (`diagnoses_note_episode_author_trigger`). Por eso solo el autor de la nota asocia diagnósticos.
- `content` es texto: las cuatro secciones se guardan como JSON (`{"subjetivo": …, "objetivo": …, "analisis": …, "plan": …}`) y se validan en la aplicación.
- **Reglas de la aplicación** que la base de datos no garantiza por sí sola: un solo diagnóstico principal por nota, sin códigos duplicados en la nota y no asociar diagnósticos a una nota firmada.
- **Falla segura:** si el catálogo CIE-10 no responde, no se asocia el diagnóstico (`503`). Si la auditoría de la firma falla, la firma se revierte (`500`). Nunca se firma sin traza.
- **Dónde se guarda la auditoría:** en la tabla local `local_access_audits` de la base **HOSPITAL** (entidad `local_access_audit` del slice H8 del esquema federado), usando **la misma conexión `hospital` y la misma transacción** que `clinical_notes`. Solo por eso el `ROLLBACK` revierte a la vez la firma y la auditoría. La propuesta de columnas está en el [Anexo A](#anexo-a--propuesta-de-la-tabla-local_access_audits-hospital).
- **Sin transacciones entre bases:** el diseño federado prohíbe transacciones que abarquen HOSPITAL y CENTRAL. Si la red o el módulo de gobernanza necesitan el evento, se escribe un `outbox_events` **dentro de la misma transacción local** y se publica después, de forma asíncrona, hacia el `inbox` de CENTRAL. El `ROLLBACK` local **no** garantiza nada sobre CENTRAL: si CENTRAL no responde, la firma local se mantiene y el evento se reintenta desde el outbox.
- Las notas y los diagnósticos **no se borran** (`clinical_notes_delete_protection`): se corrigen por versión o cambian de estado.
- La auditoría guarda metadatos (médico, paciente, nota, versión, códigos y motivo de corrección), no el texto clínico completo.

## Trazabilidad entre los diagramas

| UC | Objetivo | Actividad (decisión o paso) | Secuencia (mensajes) |
|---|---|---|---|
| UC-SOAP-01 | Registrar nota SOAP (borrador) | Partición A: "Médico abre el episodio…", "¿Episodio activo y expediente del paciente abierto?", "Capturar Subjetivo, Objetivo, Análisis y Plan", "¿Secciones con formato válido…?" → "Guardar nota…" → "201 Nota SOAP en borrador" | 1–2, 4–7, 12–20 |
| UC-SOAP-02 | Asociar diagnóstico clínico a la nota | Partición B: "Médico busca el diagnóstico…", "¿La nota existe en el tenant y sigue en borrador?", "¿Código, tipo y fecha de inicio…?" → "Guardar diagnóstico…" → "201 Diagnóstico asociado" | 21–22, 24–33, 48–52 |
| UC-SOAP-03 | Validar código en catálogo CIE-10 | B: "Validar código en el catálogo CIE-10", "¿Catálogo CIE-10 disponible?", "¿El código existe y está vigente…?" | 36–40, 43 |
| UC-SOAP-04 | Rechazar diagnóstico inválido | B: "Rechazar … 422 DIAGNOSIS_CODE_INVALID", "409 DIAGNOSIS_DUPLICATED" y "409 PRIMARY_DIAGNOSIS_EXISTS" | 41–42, 44–47 |
| UC-SOAP-05 | Firmar nota SOAP | Partición C completa: validar estado, "¿S, O, A y P completas y exactamente un diagnóstico principal activo?" → "status = signed…" → "200 Nota firmada e inmutable" | 53–54, 56–63, 66–68, 79–82 |
| UC-SOAP-06 | Corregir nota firmada (nueva versión) | Partición D completa: "¿La nota está firmada y es la última versión?", "¿Escribió el motivo…?" → "Crear versión n + 1…" | `POST /soap-notes/{note}/corrections` (se detalla en la Semana 5) |
| UC-SOAP-07 | Validar acceso y médico tratante | A: "¿JWT, tenant y permiso notas_soap.crear?", "¿Es médico asignado…?". B: "¿… diagnosticos.asociar?", "¿El usuario es el autor de la nota y sigue tratante?". C y D: permiso y autor tratante | 2–3, 8–11, 22–23, 34–35, 54–55, 64–65 |
| UC-SOAP-08 | Registrar evento de auditoría (`local_access_audits`, HOSPITAL) | C: "BEGIN en la conexión hospital … INSERT en local_access_audits (UC-SOAP-08) y en outbox_events", "¿Auditoría registrada?" → ROLLBACK o COMMIT. D: "+ auditoría en local_access_audits (misma transacción)" | 68–79 |
| UC-SOAP-09 | Consultar notas SOAP y diagnósticos | Reutiliza la validación de acceso (permiso `notas_soap.ver`) | `GET /care-episodes/{episode}/soap-notes` (se detalla en la Semana 5) |

## Flujo y resultados

### A · Registrar nota SOAP en borrador

1. **Acceso (UC-SOAP-07):** JWT, tenant y permiso `notas_soap.crear`; sin ellos, `401` o `403` con la traza de RBAC.
2. **Secciones:** formato válido y al menos Subjetivo y Objetivo con contenido; si no, `422` con los errores por sección.
3. **Médico tratante (UC-SOAP-07):** si no tiene asignación activa en el episodio, `403`.
4. **Contexto clínico:** si el episodio o el expediente del paciente están cerrados, `409`.
5. **Resultado:** nota `draft`, `version_no = 1` (`201`). Análisis y Plan pueden completarse antes de firmar.

### B · Asociar diagnóstico clínico

Precondición: existe una nota en borrador creada en A.

1. **Acceso:** permiso `diagnosticos.asociar`; sin él, `401` o `403`.
2. **Datos:** código, tipo y fecha de inicio no futura; si no, `422`.
3. **Nota:** si no existe en el tenant, `404`; si ya está firmada, `409` (el cambio se hace con una corrección, UC-SOAP-06).
4. **Autor (UC-SOAP-07):** si el usuario no es el autor de la nota o ya no es tratante, `403`.
5. **Catálogo (UC-SOAP-03):** si no responde, `503` y no se asocia nada.
6. **Rechazos (UC-SOAP-04):** código inexistente o no vigente (`422 DIAGNOSIS_CODE_INVALID`), código ya asociado a la nota (`409 DIAGNOSIS_DUPLICATED`) o segundo diagnóstico principal (`409 PRIMARY_DIAGNOSIS_EXISTS`).
7. **Resultado:** diagnóstico `active`, con el episodio y el autor de la nota (`201`).

### C · Firmar la nota SOAP

Escenario independiente; precondición: nota existente en borrador.

1. **Acceso:** permiso `notas_soap.firmar`; sin él, `401` o `403`.
2. **Estado:** `404` si no existe; `409` si ya está firmada; `403` si el usuario no es el autor tratante.
3. **Completitud:** las cuatro secciones con contenido y exactamente un diagnóstico principal activo; si no, `422 SOAP_INCOMPLETE` y la nota **sigue en borrador**.
4. **Firma y auditoría (UC-SOAP-05/08):** en una sola transacción de la conexión `hospital`: `UPDATE clinical_notes` (firma), `INSERT local_access_audits` y `INSERT outbox_events`. Las tres tablas están en la misma base HOSPITAL. Si la auditoría falla, `ROLLBACK` de todo y `500`: no existe firma sin traza.
5. **Resultado:** nota `signed`, con `signed_at`, inmutable (`200`). El evento del outbox se publica después hacia CENTRAL. Si esa publicación falla, se reintenta y la firma local no se revierte.

### D · Corregir una nota firmada

1. **Acceso:** permiso `notas_soap.corregir` y médico tratante; sin ellos, `401` o `403`.
2. **Versión:** solo se corrige la última versión firmada (cada nota se reemplaza una sola vez, `clinical_notes_supersedes_unique`); si no, `409`.
3. **Motivo:** obligatorio (`clinical_notes_correction_check`); si falta, `422`.
4. **Resultado:** versión n + 1 en borrador, con `supersedes_id` y `correction_reason` (`201`). La auditoría (`nota_soap.corregida` en `local_access_audits`) se escribe en la misma transacción `hospital`; si falla, no se crea la versión. Luego se completa con B y se firma con C.

## Riesgos y pendientes detectados en el esquema

| # | Hallazgo | Impacto | Propuesta |
|---|---|---|---|
| R1 | `clinical_notes` tiene `UNIQUE (medical_record_id, version_no)` y el trigger exige `version_no = 1` en toda nota original. | Un expediente solo admitiría **una** nota original. La segunda nota SOAP del mismo paciente (por ejemplo, otra consulta) sería rechazada por la base de datos. | Coordinar con el responsable del esquema y del módulo *Expediente* que la versión sea única **por cadena de corrección** y no por expediente (por ejemplo, un `note_chain_id`). Se resuelve antes de implementar (Semanas 5–7). |
| R2 | El catálogo CIE-10 no está entre las 27 entidades del esquema HOSPITAL. | No hay una fuente de códigos para validar. | Consumirlo como referencia de solo lectura detrás de la interfaz `Icd10Catalog`, con una implementación en memoria mientras se decide su origen. |
| R3 | La base de datos no impide un segundo diagnóstico `primary` por nota ni asociar diagnósticos a una nota firmada. | Las reglas dependen solo de la aplicación. | Validarlas en `SoapNoteService` y evaluar un índice único parcial en la Semana 5. |
| R4 | La tabla de auditoría local (`local_access_audit`, slice H8) está prevista en el esquema HOSPITAL, pero su migración aún no existe (tarea 3.7) y no tiene columnas definidas. | Sin ella no se puede garantizar "firma y auditoría en la misma transacción". Si la auditoría terminara en CENTRAL o en un servicio externo, esa garantía dejaría de valer y habría que rediseñar el flujo C. | Proponer las columnas del [Anexo A](#anexo-a--propuesta-de-la-tabla-local_access_audits-hospital) y validarlas con la persona encargada del esquema de base de datos y del módulo de gobernanza y auditoría. Si aún no hay responsable asignado, acordarlas con el grupo de 9 integrantes antes de implementar (Semanas 5–7). |

## Dependencias con otros módulos

- **Depende de:**
  - *Expediente médico electrónico base* (expediente y episodio; las tablas ya existen).
  - *Médicos* (asignación activa del médico al episodio).
  - *RBAC* (permisos `notas_soap.*` y `diagnosticos.asociar`).
  - *Gobernanza y auditoría* y esquema HOSPITAL: la tabla `local_access_audits` y el `outbox_events` (slice H8) para la bitácora de firma y correcciones. La estructura de columnas se acuerda con quien tenga a cargo esos temas o, si no hay responsable asignado, con el grupo.
- **Dependen de este módulo:** *Expediente* (muestra las notas y diagnósticos), *Prescripciones* y *Órdenes de laboratorio* (parten del Plan y del diagnóstico), y los reportes y analytics.

## Anexo A — Propuesta de la tabla `local_access_audits` (HOSPITAL)

> **Estado:** propuesta de documentación (Semana 1). No se implementa esta semana. Se debe validar con la persona encargada del esquema de base de datos y del módulo de gobernanza y auditoría o, si no hay responsable asignado, con el grupo de 9 integrantes. Corresponde a la entidad `local_access_audit` del slice H8 (tarea 3.7, `000700_create_audit_and_sync_tables.php`).

Sigue las convenciones de las migraciones HOSPITAL existentes: tabla en plural, PK `uuid` con `gen_random_uuid()`, FK locales con `ON DELETE RESTRICT`, textos acotados con `CHECK`, `timestamptz` y `jsonb` de tipo objeto. Como `cross_hospital_access_audits` en CENTRAL, solo admite inserciones.

```sql
CREATE TABLE local_access_audits (
    audit_event_id     uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    correlation_id     uuid NOT NULL,                       -- id de la petición; enlaza con el outbox_event
    actor_user_ref_id  uuid NOT NULL REFERENCES local_user_refs(local_user_ref_id) ON DELETE RESTRICT,
    module             text NOT NULL CHECK (btrim(module) <> ''),          -- 'notas_soap', 'rbac', ...
    action             text NOT NULL CHECK (action ~ '^[a-z_]+\.[a-z_]+$'), -- 'nota_soap.firmada'
    resource_type      text NOT NULL CHECK (btrim(resource_type) <> ''),   -- 'clinical_note'
    resource_id        uuid NOT NULL,                       -- referencia lógica (polimórfica, sin FK)
    local_patient_id   uuid REFERENCES local_patients(local_patient_id) ON DELETE RESTRICT,
    care_episode_id    uuid REFERENCES care_episodes(care_episode_id) ON DELETE RESTRICT,
    decision           text NOT NULL DEFAULT 'allowed' CHECK (decision IN ('allowed', 'denied')),
    reason             text,                                -- motivo de corrección o de denegación
    metadata           jsonb NOT NULL DEFAULT '{}'::jsonb CHECK (jsonb_typeof(metadata) = 'object'),
    occurred_at        timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX local_access_audits_actor_occurred_index   ON local_access_audits (actor_user_ref_id, occurred_at);
CREATE INDEX local_access_audits_module_occurred_index  ON local_access_audits (module, occurred_at);
CREATE INDEX local_access_audits_resource_index         ON local_access_audits (resource_type, resource_id);
CREATE INDEX local_access_audits_local_patient_id_index ON local_access_audits (local_patient_id);
CREATE INDEX local_access_audits_care_episode_id_index  ON local_access_audits (care_episode_id);
CREATE INDEX local_access_audits_correlation_id_index   ON local_access_audits (correlation_id);

-- Solo inserciones (append-only)
CREATE TRIGGER local_access_audits_immutable BEFORE UPDATE OR DELETE ON local_access_audits
    FOR EACH ROW EXECUTE FUNCTION prevent_local_access_audit_mutation();
```

| Columna | Justificación |
|---|---|
| — (sin `hospital_id`) | Cada hospital tiene su propia base; el hospital es implícito. Al publicar por el outbox, CENTRAL lo identifica por su `sync_node`. |
| `actor_user_ref_id`, `module`, `occurred_at` | Indexados para las consultas de gobernanza: filtros por usuario, módulo y rango de fechas (`desde` / `hasta`). |
| `resource_type` + `resource_id` | Sin FK, para que la misma tabla audite notas, diagnósticos, prescripciones y otros recursos. |
| `local_patient_id`, `care_episode_id` | Opcionales: permiten consultar quién hizo qué sobre un paciente y se dejan vacíos en eventos no clínicos (por ejemplo, RBAC). |
| `decision` | Permite registrar también denegaciones. Esas se escriben **fuera** de la transacción fallida, porque el `ROLLBACK` las borraría. |
| `metadata` | Solo metadatos (versión, códigos CIE-10, `supersedes_id`); **nunca** el texto clínico de la nota. |
| `correlation_id` | Une la fila de auditoría con el `outbox_events` generado en la misma transacción. |
| Sin `updated_at` | Un registro de auditoría nunca se modifica (trigger append-only). |

**Ejemplos de este módulo**

| `action` | `resource_type` / `resource_id` | `reason` | `metadata` |
|---|---|---|---|
| `nota_soap.firmada` | `clinical_note` / id de la nota | — | `{"version_no": 1, "primary_code": "J18.9", "diagnosis_codes": ["J18.9", "E11.9"]}` |
| `nota_soap.corregida` | `clinical_note` / id de la nueva versión | motivo de la corrección | `{"version_no": 2, "supersedes_id": "…"}` |

**Orden dentro de la transacción de firma (conexión `hospital`):** `BEGIN` → `UPDATE clinical_notes` (status `signed`) → `INSERT local_access_audits` → `INSERT outbox_events` (mismo `correlation_id`) → `COMMIT`. Si cualquier paso falla, `ROLLBACK` de los tres. La publicación del outbox hacia CENTRAL ocurre después y fuera de esta transacción.

## Archivos relacionados

- [Casos de uso](./01-casos-de-uso.svg)
- [Actividades](./02-actividad.svg)
- [Secuencia](./03-secuencia.svg)
- [README del módulo](./README.md)
