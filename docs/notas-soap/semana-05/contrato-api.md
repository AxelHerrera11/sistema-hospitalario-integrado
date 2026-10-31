# Contrato API REST — Notas SOAP y diagnósticos v1

Contrato que consumen Expediente clínico, Prescripciones, Órdenes de laboratorio y Reportes. Se publica antes del código de integración: la lógica ya existe en el micro-HIS ([`micro-his/notas-soap/`](../../../micro-his/notas-soap/), PR #128 y #129) y este documento fija cómo se expone.

## Convenciones

- **Base:** `/api/v1`. Fuera de ese prefijo, `404 ROUTE_NOT_FOUND`.
- **Formato:** JSON en petición y respuesta (`Content-Type: application/json; charset=utf-8`).
- **Trazabilidad:** toda respuesta, también las de error, lleva `trace_id` (UUID v4) en el cuerpo y en el encabezado `X-Trace-Id`. Es el mismo valor que se guarda como `correlation_id` en la auditoría de la petición.
- **Errores:** cuerpo uniforme `{"code": "...", "trace_id": "..."}`; `code` en mayúsculas, estable y documentado abajo. Nunca se expone el detalle interno de una excepción ni texto clínico.
- **Identidad y tenant:** el actor y el hospital llegan del contexto autenticado (JWT y middleware de tenant), nunca del cuerpo. En el micro-HIS se simula con `X-User-ID`, que no es autoritativo.
- **Autorización en dos niveles:** primero el permiso RBAC de la ruta (`403 FORBIDDEN`); después la regla clínica de médico tratante del episodio (`403 NOT_TREATING_PHYSICIAN`). Tener el permiso no basta para escribir en un episodio ajeno.
- **Falla cerrada:** una ruta sin permiso configurado no se expone (`404`); si RBAC no responde, `503` sin tocar los datos.
- **Estado:** "implementado" significa que funciona en el micro-HIS con `ApiKernel` (#404) y el cliente (#405), con RBAC como doble en memoria. Versión legible por máquina: [openapi.yaml](./openapi.yaml).

## Endpoints y permisos

| Método | Ruta | Permiso RBAC | Éxito | Estado |
|---|---|---|---|---|
| `POST` | `/api/v1/care-episodes/{care_episode_id}/soap-notes` | `notas_soap.crear` | `201` | implementado |
| `POST` | `/api/v1/soap-notes/{clinical_note_id}/diagnoses` | `notas_soap.asociar_diagnostico` | `201` | implementado; en el HIS integrado responde `503 ICD10_UNAVAILABLE` hasta que `icd10_codes` esté migrada y cargada (ASII-09, #1620) |
| `GET` | `/api/v1/soap-notes/{clinical_note_id}` | `notas_soap.ver` | `200` | implementado |
| `GET` | `/api/v1/care-episodes/{care_episode_id}/soap-notes` | `notas_soap.ver` | `200` | propuesto |
| `POST` | `/api/v1/soap-notes/{clinical_note_id}/sign` | `notas_soap.firmar` | `200` | implementado (Semana 18) |
| `POST` | `/api/v1/soap-notes/{clinical_note_id}/corrections` | `notas_soap.corregir` | `201` | propuesto |

El permiso de diagnósticos pasa de `diagnosticos.asociar` (Semana 2, RNF-SOAP-01) a `notas_soap.asociar_diagnostico` para respetar `modulo.accion` de dos partes, la misma forma que usa Médicos (`medicos.asociar_especialidad`, #371). Ninguno está aún en el [catálogo de RBAC](../../rbac/semana-05/permisos.md); se proponen a María (ASII-02) con esta matriz inicial:

| Rol | `notas_soap.ver` | `notas_soap.crear` | `notas_soap.asociar_diagnostico` | `notas_soap.firmar` | `notas_soap.corregir` |
|---|---|---|---|---|---|
| `medico` | X | X | X | X | X |
| `enfermeria` | X |  |  |  |  |
| `admin_hospital` |  |  |  |  |  |

`admin_hospital` no lee notas: es dato clínico y el acceso se limita a quien atiende al paciente.

## Campos

### `POST /api/v1/care-episodes/{care_episode_id}/soap-notes`

| Campo | Tipo | Regla |
|---|---|---|
| `subjective` | string | obligatorio, no vacío ni solo espacios, máximo 4000 caracteres |
| `objective` | string | ídem |
| `assessment` | string | ídem |
| `plan` | string | ídem |

```json
{ "subjective": "Tos de 3 días", "objective": "T 38.4 °C", "assessment": "Neumonía", "plan": "Amoxicilina" }
```

`201` → `{ "clinical_note_id": "…", "status": "draft", "trace_id": "…" }`. Se crea `clinical_notes` con `note_type = soap`, versión 1, autor = actor; el servidor deriva `medical_record_id` del paciente del episodio.

### `POST /api/v1/soap-notes/{clinical_note_id}/diagnoses`

| Campo | Tipo | Regla |
|---|---|---|
| `code` | string | código CIE-10 vigente en el catálogo |
| `type` | string | `primary`, `secondary` o `differential`; uno solo `primary` por nota |

```json
{ "code": "J18.9", "type": "primary" }
```

`201` → `{ "clinical_note_id": "…", "code": "J18.9", "type": "primary", "trace_id": "…" }`. La descripción se toma del catálogo, no del cliente.

### `POST /api/v1/soap-notes/{clinical_note_id}/sign`

Sin cuerpo. Solo firma el autor con asignación activa y solo si la nota está completa.

| Resultado | Código | Cuándo |
|---|---|---|
| `200` | — | `{ "clinical_note_id": "…", "status": "signed", "trace_id": "…" }`; la nota queda con `signed_at` y es inmutable |
| `403` | `NOT_TREATING_PHYSICIAN` | no es el autor o ya no tiene asignación activa |
| `404` | `NOTE_NOT_FOUND` | la nota no existe |
| `409` | `NOTE_SIGNED` | ya estaba firmada, también si otra sesión la firmó entre la lectura y el `UPDATE` |
| `422` | `SOAP_INCOMPLETE` | no tiene exactamente un diagnóstico principal; la nota sigue en borrador |
| `500` | `SOAP_PERSISTENCE_ERROR` | falló la auditoría o la escritura: la firma se revierte, nunca se firma sin traza |

La firma y la auditoría `nota_soap.firmada` (con `version_no`, `primary_code` y `diagnosis_codes`, sin texto clínico) van en una sola transacción. **Pendiente:** el `outbox_events` hacia CENTRAL que prevé la Semana 1 no se escribe todavía, porque no hay contrato del evento con ASII-09 ni con Gobernanza.

### `GET /api/v1/soap-notes/{clinical_note_id}`

`200` → `{ "clinical_note_id": "…", "care_episode_id": "…", "author_user_ref_id": "…", "status": "draft", "sections": { "subjective": "…", "objective": "…", "assessment": "…", "plan": "…" }, "diagnoses": [ { "code": "J18.9", "type": "primary" } ], "trace_id": "…" }`. Además de `notas_soap.ver`, exige asignación activa en el episodio: es dato clínico.

Un campo con tipo incorrecto (número, arreglo, `null`, booleano) o un JSON malformado responde `422 PAYLOAD_INVALID` antes de llegar al caso de uso y no guarda nada.

## Códigos de error

| HTTP | `code` | Cuándo |
|---|---|---|
| `401` | `UNAUTHENTICATED` | Sin actor autenticado o token inválido |
| `403` | `FORBIDDEN` | El actor no tiene el permiso de la ruta o el tenant no coincide; el cuerpo incluye `permission` |
| `403` | `NOT_TREATING_PHYSICIAN` | Sin `care_assignment` activo en el episodio, o no es el autor de la nota (RF-SOAP-03). En la lectura (`GET`) se responde `404 NOTE_NOT_FOUND`, igual que ante una nota inexistente, para no revelar qué identificadores existen |
| `404` | `ROUTE_NOT_FOUND` / `NOTE_NOT_FOUND` | Ruta inexistente / nota inexistente |
| `409` | `DIAGNOSIS_DUPLICATED` / `PRIMARY_DIAGNOSIS_EXISTS` | Código ya asociado / segundo principal (RF-SOAP-05) |
| `409` | `NOTE_SIGNED` | La nota firmada no acepta diagnósticos (RF-SOAP-07) |
| `409` | `MEDICAL_RECORD_NOT_FOUND` | El paciente del episodio no tiene un expediente abierto; este módulo no lo crea |
| `409` | `RECORD_ALREADY_HAS_NOTE` | El expediente ya tiene una nota: `clinical_notes_record_version_unique` solo admite una nota original (R-06, pendiente en ASII-09) |
| `403` | `ACTOR_SIN_REFERENCIA_LOCAL` | El usuario del token no tiene referencia en `local_user_refs` de este hospital |
| `503` | `HOSPITAL_NO_DISPONIBLE` | La base HOSPITAL no respondió al resolver el actor; no se escribe nada |
| `422` | `PAYLOAD_INVALID` | JSON malformado o campo con tipo incorrecto |
| `422` | `SOAP_SECTION_INVALID` | Sección ausente, vacía o mayor a 4000 caracteres (RF-SOAP-02) |
| `422` | `DIAGNOSIS_INVALID` | Código vacío o tipo fuera de los tres permitidos |
| `422` | `DIAGNOSIS_CODE_INVALID` | Código inexistente o inactivo en el catálogo CIE-10 |
| `500` | `SOAP_PERSISTENCE_ERROR` | Falló la base o la auditoría: todo se revierte |
| `503` | `ICD10_UNAVAILABLE` | Catálogo CIE-10 caído: no se asocia nada (falla segura, RF-SOAP-06) |
| `503` | `AUTHORIZATION_UNAVAILABLE` | RBAC no respondió: se niega el acceso (falla cerrada) |

RF-SOAP-04 nombra `DIAGNOSIS_TYPE_INVALID`; el código usa `DIAGNOSIS_INVALID` para código vacío y tipo no permitido, y este contrato adopta el del código.

## Propiedad de datos

Notas SOAP es dueño de `clinical_notes` y `diagnoses`. Lee `care_assignments` (Episodios) solo por `CareAssignmentReader` y el catálogo CIE-10 por `Icd10Catalog`; nunca escribe en tablas de otro módulo. Dependencias de base pendientes de acordar con Javier:

- `icd10_codes` (catálogo CIE-10) no está entre las 27 entidades de la especificación; Javier la registró como tabla candidata (revisión de #376, R-03). ASII-09 la crea como entidad de referencia en el esquema HOSPITAL (PR #1620: `code varchar(10)` PK, `description text`, `active boolean`). Notas SOAP la lee con `active IS TRUE`. Fuente acordada: CIE-10 de la OMS (2019). `micro-his/notas-soap/database/icd10-seed-minimo.csv` es un **conjunto mínimo de 20 códigos para pruebas y demostración**, con descripciones en español sin edición de referencia citada; no es el catálogo oficial ni lo carga ninguna migración. El catálogo completo lo carga el importador de ASII-09 (pendiente). Mientras la tabla no tenga códigos activos, el adaptador responde `503 ICD10_UNAVAILABLE`, no `422`.
- `local_access_audits` es la entidad `local_access_audit` del slice H8, sin migración. El doble sigue las columnas del Anexo A de la [Semana 1](../semana-01/narrativa-alcance-trazabilidad.md) y es **provisional** hasta esa migración (R-02). Médicos (ASII-04) necesita la misma tabla.
- `clinical_notes_record_version_unique (medical_record_id, version_no)` impediría una segunda nota original por expediente (riesgo R1 de la Semana 1); el doble no la replica hasta que se resuelva con Javier.

## Consistencia y privacidad

- Registro y asociación son atómicos: nota o diagnóstico y su auditoría en una sola transacción.
- La auditoría guarda solo identificadores, código y acción (`nota_soap.registrada`, `diagnostico.asociado`), nunca el texto de las secciones (RNF-SOAP-05). La bitácora de acceso tampoco guarda el cuerpo.
- Los identificadores son UUID v4, como las PK del esquema federado (R-01).
- Una nota firmada es inmutable (trigger `clinical_notes_signed_immutable` de la migración `000400`); un cambio se hace con una corrección que crea una versión nueva.

## Versionado

Los campos nuevos se agregan sin romper a los clientes. Un cambio incompatible sube a `/api/v2` y `/api/v1` se mantiene mientras haya consumidores.
