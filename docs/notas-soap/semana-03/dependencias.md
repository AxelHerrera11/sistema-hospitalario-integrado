# Dependencias del módulo — Micro-HIS Notas SOAP

## Regla de dependencia entre capas

| Capa | Depende de | No depende de |
|---|---|---|
| Presentation (`SoapNoteHttpHandler`) | Casos de uso de Application, objetos de valor de Domain | PDO, SQL, reglas de negocio |
| Application (casos de uso, `TreatingPhysicianGuard` y puertos) | Domain y sus propios puertos | PDO, HTTP |
| Domain (`SoapSections`, `Diagnosis`, `ClinicalNote`, `DiagnosisPolicy`) | Solo PHP | Application, Persistence, Presentation |
| Persistence (`PdoClinicalNoteStore`, `PdoIcd10Catalog`) | Puertos de Application, Domain, `PDO` | Presentation |

Las flechas apuntan hacia adentro (Presentation → Application → Domain ← Persistence). Persistence depende de Application porque implementa sus interfaces.

## Matriz caso de uso → dependencias

| Caso de uso | Depende de | Requerimientos |
|---|---|---|
| `RegisterSoapNote` (UC-SOAP-01) | `TreatingPhysicianGuard`, `ClinicalNoteRepository` | RF-SOAP-01 a 03 |
| `AssociateDiagnosis` (UC-SOAP-02 a 04) | `TreatingPhysicianGuard`, `Icd10Catalog`, `DiagnosisPolicy`, `ClinicalNoteRepository` | RF-SOAP-03 a 07 |

`RegisterSoapNote` no conoce el catálogo CIE-10. Si cambia el origen del catálogo, solo cambia `PdoIcd10Catalog`.

## Persistencia y SQL

- Todas las consultas usan `prepare()` y `execute([...])`. Ningún valor de entrada se concatena en el SQL.
- `PDO::ERRMODE_EXCEPTION` está activo. Un `PDOException` dentro de la transacción hace `rollBack()` y se traduce a `500 SOAP_PERSISTENCE_ERROR`.
- Las cuatro secciones se guardan como JSON en `clinical_notes.content`, con `note_type = 'soap'` y versión 1.
- `database/schema.sql` es un esquema local simplificado para SQLite, **no un subconjunto** de la migración hospital `000400`. Sus divergencias con la base real están listadas como pendientes en la sección de supuestos.

## Dependencias externas y supuestos

| Dependencia | Estado | Tratamiento en el prototipo |
|---|---|---|
| Autenticación JWT, RBAC y tenant (ASII-01, 02) | Fuera de alcance | `X-User-ID` identifica al médico. |
| `care_assignments` (Admisión) | Otro módulo | Solo lectura. Se simplifica a `user_ref_id` en lugar de `doctor_profile_id`, y a los estados `active` y `ended` en lugar de `active`, `closed` y `revoked` (migración `000200`). Pendiente de integración. |
| `clinical_notes` (migración `000400`) | Otro módulo | El alta no envía `medical_record_id` ni `version_no`, que la tabla real exige (`NOT NULL`). Pendiente de integración con Expediente. |
| Catálogo CIE-10 | Origen por acordar | Tabla `icd10_codes` detrás de `Icd10Catalog`; en las pruebas también un doble que simula caída. |
| `local_access_audits` (slice H8) | Sin migración en `develop` | Tabla mínima en `schema.sql` para probar la atomicidad. |

## Límites de la Semana 3

- Firma y corrección de notas (UC-SOAP-05 y 06) quedan para semanas posteriores; las pruebas simulan la nota firmada con un `UPDATE`.
- El límite de 4000 caracteres por sección es una propuesta pendiente de confirmar con el grupo.
- No se integra con el router de Laravel ni con PostgreSQL real. MVC con Repository y adaptadores InMemory se completan en la Semana 4.
