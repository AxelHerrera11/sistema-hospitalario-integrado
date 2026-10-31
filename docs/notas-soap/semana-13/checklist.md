# Checklist de revisión técnica — Notas SOAP y diagnósticos (Semana 13)

Quince ítems para «registro de nota SOAP y asociación de diagnóstico clínico». Cada revisor marca **Cumple**, **No cumple** o **No aplica** (con motivo) y, si no cumple, abre un hallazgo con el [formato](./formato-hallazgos.md). Las rutas son relativas a `micro-his/notas-soap/` en la rama de la Semana 5. **Sev.** es la severidad que tendría el incumplimiento (C = Crítico, M = Mayor, m = Menor).

## Reglas de dominio

| # | Qué se verifica | Dónde | Criterio de aprobación | Sev. |
|---|---|---|---|---|
| C-01 | Las cuatro secciones son obligatorias y de máximo 4000 caracteres | `Domain/SoapSections.php` | Vacía, solo espacios o 4001 caracteres lanzan `SOAP_SECTION_INVALID` (422); hay prueba para cada caso | M |
| C-02 | El diagnóstico exige código y tipo válidos; sin duplicados ni segundo principal | `Domain/Diagnosis.php`, `DiagnosisPolicy.php` | `DIAGNOSIS_INVALID` 422, `DIAGNOSIS_DUPLICATED` y `PRIMARY_DIAGNOSIS_EXISTS` 409, cada uno con su prueba | M |
| C-03 | Una nota firmada no acepta diagnósticos | `DiagnosisPolicy.php`, esquema | `NOTE_SIGNED` 409 en la política **y** bloqueo en la base, no solo en el código | C |

## Contrato API

| # | Qué se verifica | Dónde | Criterio de aprobación | Sev. |
|---|---|---|---|---|
| C-04 | Rutas, verbos y permisos coinciden con el OpenAPI | `Presentation/ApiKernel.php`, `semana-05/openapi.yaml` | Las tres rutas, su permiso y su código de respuesta son idénticos en código y contrato | M |
| C-05 | Cada `code` de error tiene un estado HTTP único y documentado | `SoapRuleViolation`, `SoapNoteController::respond` | Ningún `code` aparece con dos estados distintos; los no previstos devuelven `SOAP_PERSISTENCE_ERROR` 500 | M |
| C-06 | Todas las respuestas llevan `trace_id` y ninguna filtra detalle interno | `ApiKernel::handle`, `JsonView` | El cuerpo y `X-Trace-Id` coinciden; ninguna respuesta contiene el mensaje de una excepción | M |

## Permisos y tenant

| # | Qué se verifica | Dónde | Criterio de aprobación | Sev. |
|---|---|---|---|---|
| C-07 | Autorización en dos niveles: permiso de la ruta y médico tratante del episodio | `ApiKernel`, `TreatingPhysicianGuard` | Permiso sin asignación activa devuelve `403 NOT_TREATING_PHYSICIAN`; solo el autor asocia diagnósticos | C |
| C-08 | Falla cerrada: sin identidad `401`, sin permiso `403`, RBAC caído `503` | `ApiKernel::route` | Ante la duda se niega y no se toca la base | C |
| C-09 | Aislamiento por tenant | `ApiKernel`, `Request`, repositorios | Toda lectura y escritura queda acotada al tenant del actor, o hay evidencia de que otra capa lo garantiza | C |

## Integridad, autoría, versionado y expediente

| # | Qué se verifica | Dónde | Criterio de aprobación | Sev. |
|---|---|---|---|---|
| C-10 | Nota o diagnóstico y su auditoría se escriben en una sola transacción | `PdoClinicalNoteStore::transaction` | Si la auditoría falla, hay `ROLLBACK` y no queda una nota sin traza | C |
| C-11 | Las reglas de integridad viven también en la base y resisten la concurrencia | `database/schema.sql`, migración `000400` | Unicidad de código y de principal por nota, y bloqueo de diagnóstico en nota firmada, con prueba que lo intenta | C |
| C-12 | La auditoría no guarda texto clínico y no se puede modificar ni borrar | `AuditTrail`, `local_access_audits` | `metadata` solo con identificadores, código y tipo; triggers de solo anexado | M |
| C-13 | La autoría es de quien escribe y no cambia | `RegisterSoapNote`, `AssociateDiagnosis` | `author_user_ref_id` sale del actor autenticado; el diagnóstico hereda episodio y autor de su nota | C |
| C-14 | Versionado y expediente: la nota se ancla a un expediente y a una versión | esquema, `PdoClinicalNoteStore::add` | El alta informa `medical_record_id` y `version_no` válidos para el esquema real, y la corrección no pisa la versión firmada | M |

## Dependencias

| # | Qué se verifica | Dónde | Criterio de aprobación | Sev. |
|---|---|---|---|---|
| C-15 | Solo las cuatro dependencias necesarias, todas detrás de un puerto | `Application/*` y `app.php` | Aplicación y dominio no importan PDO, HTTP ni clases de otros módulos; el doble en memoria pasa la misma batería | m |

## Puntos de atención declarados por el autor

Antes de la reunión, el autor declara lo que **ya sabe** que puede fallar, para que el tiempo de los revisores se use en lo desconocido. Se comprobaron así: *ejecución* = se reprodujo; *lectura* = se leyó el código o la migración, sin ejecutarla.

| # | Ítem | Observación | Cómo se comprobó |
|---|---|---|---|
| P-1 | C-03, C-11 | El trigger `clinical_notes_signed_immutable` protege el `UPDATE` de la nota, pero **no impide insertar un diagnóstico** en una nota ya firmada. La migración `000400` (`enforce_diagnosis_integrity`) tampoco comprueba la firma | Ejecución en el esquema SQLite de la Semana 5: insertar el diagnóstico en una nota `signed` funciona. Lectura de la migración |
| P-2 | C-02, C-11 | No hay unicidad por (`clinical_note_id`, `code`) ni un solo `primary` por nota: la política es la única barrera, y entre `find` y `INSERT` hay una carrera | Ejecución: se insertaron el mismo código y dos principales. Lectura: la migración solo tiene `UNIQUE` en el expediente y en la versión |
| P-3 | C-09 | El código del micro-HIS no contiene la palabra «tenant»; RNF-SOAP-01 exige `X-Tenant-ID` | Búsqueda en `micro-his/notas-soap`: 0 coincidencias. Puede resolverlo otra capa; la pregunta es cuál |
| P-4 | C-14 | `PdoClinicalNoteStore::add` inserta sin `medical_record_id`, y la migración lo declara `NOT NULL` | Lectura |
| P-5 | C-08 | La identidad es la cabecera `X-User-ID`, que no está firmada: cualquier cliente puede suplantarla | Lectura; riesgo ya declarado en la Semana 6 |
| P-6 | C-07 | `AssociateDiagnosis` y `GetSoapNote` buscan la nota **antes** de autorizar el episodio: quien no atiende distingue `404` de `403` y puede enumerar identificadores | Lectura |
