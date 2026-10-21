# Capas, responsabilidades y contrato del Repository

## Responsabilidades por capa

| Capa | Clases | Responsabilidad | No hace |
|---|---|---|---|
| Entrada | `public/index.php`, `app.php` | Arma la `Request` desde HTTP, elige los adaptadores (PDO o InMemory) y emite la respuesta. | Reglas, SQL |
| Presentation (MVC) | `Router`, `SoapNoteController`, `Request`, `JsonView` | Ruta → acción; el controlador lee campos (la `Request` rechaza tipos incorrectos con 422), arma los objetos de valor, llama al caso de uso y traduce excepciones a estado HTTP; la vista serializa a JSON. | SQL, reglas de negocio |
| Application (modelo) | `RegisterSoapNote`, `AssociateDiagnosis`, `TreatingPhysicianGuard`, puertos `ClinicalNoteRepository`, `CareAssignmentReader`, `Icd10Catalog`, `AuditTrail` | Orquesta cada caso de uso contra sus puertos; aplica la falla segura del catálogo. | HTTP, PDO |
| Domain | `SoapSections`, `Diagnosis`, `ClinicalNote`, `DiagnosisPolicy`, `SoapRuleViolation` | Secciones válidas, tipos de diagnóstico, código duplicado, principal única, nota firmada. | Todo lo externo |
| Persistence | `PdoClinicalNoteStore`, `PdoAuditTrail`, `PdoCareAssignmentReader`, `PdoIcd10Catalog` y sus pares `InMemory…` | Implementan los puertos; PDO con sentencias preparadas y transacción. | Reglas, HTTP |

El controlador no decide reglas: `SoapRuleViolation` ya trae el estado sugerido por el dominio, y el controlador solo lo pasa a la vista.

## Objetos reutilizables

- `JsonView` y `Request` no conocen el módulo: sirven para cualquier controlador del micro-HIS.
- `AuditTrail` e `InMemoryAuditTrail` sirven a cualquier módulo que audite: solo reciben evento, actor y detalle, sin texto clínico (RNF-SOAP-05).
- `InMemoryIcd10Catalog` sirve como doble del CIE-10 para Prescripciones u Órdenes mientras el catálogo real no exista.
- `DiagnosisPolicy` no tiene dependencias y se prueba sin base de datos.

## Contrato `ClinicalNoteRepository` (Application)

| Método | Garantía que debe cumplir cada adaptador |
|---|---|
| `find(noteId)` | Nota con su episodio, autor, estado y solo los diagnósticos `active`; `null` si no existe. |
| `add(episodeId, authorId, sections)` | Nota SOAP en `draft`, versión 1, + evento `nota_soap.registrada`, todo o nada. Devuelve el id (UUID v4). |
| `addDiagnosis(note, diagnosis, description)` | Diagnóstico con el mismo episodio y autor de la nota + evento `diagnostico.asociado`, todo o nada. |

Si la auditoría falla, el adaptador deshace la escritura y lanza `RuntimeException`, que el controlador traduce a `500 SOAP_PERSISTENCE_ERROR`. `PdoClinicalNoteStore` lo logra con la transacción PDO (y `PdoAuditTrail` comparte la conexión); `InMemoryClinicalNoteRepository` restaura el estado anterior.

## Contrato `AuditTrail` (Application)

| Método | Garantía |
|---|---|
| `record(actorId, action, resourceType, resourceId, episodeId, metadata)` | Guarda el evento con las columnas del Anexo A de la Semana 1 (`action` en `modulo.accion`, `metadata` JSON) o lanza `RuntimeException`; nunca finge éxito. `metadata` lleva solo identificadores y códigos. |

**Por qué en Application:** los casos de uso y el repositorio definen lo que necesitan, así que el límite está en Application y Persistence depende de él. Separar la auditoría del store permite cambiar la tabla de auditoría (pendiente de Gobernanza) sin tocar notas ni diagnósticos. `CareAssignmentReader` e `Icd10Catalog` quedan como puertos aparte porque sus datos son de otros módulos.

**Cómo se demuestra que los adaptadores son intercambiables:** `run.php` ejecuta los mismos 15 casos contra los adaptadores PDO (SQLite en memoria) y los InMemory, con los mismos datos y por el mismo router.

## Traducción a HTTP en el controlador

| Ruta | Resultado | Estado | Cuerpo |
|---|---|---|---|
| `POST /care-episodes/{id}/soap-notes` | Nota creada | `201` | `clinical_note_id`, `status: draft` |
| `POST /soap-notes/{id}/diagnoses` | Diagnóstico asociado | `201` | `clinical_note_id`, `code`, `type` |
| Cualquiera | `SoapRuleViolation` | `403`, `404`, `409`, `422` o `503` | `code` |
| Cualquiera | Falla del repositorio o la auditoría (`RuntimeException`) | `500` | `code: SOAP_PERSISTENCE_ERROR` |
| Otra ruta | — | `404` | `code: ROUTE_NOT_FOUND` |
| Cuerpo JSON inválido | — | `422` | `code: PAYLOAD_INVALID` (en `public/index.php`) |

Cada fila termina la petición. Ningún error sigue al caso de uso ni a la base.
