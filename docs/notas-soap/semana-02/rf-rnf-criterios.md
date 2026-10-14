# RF/RNF y criterios de aceptación — Notas SOAP y diagnósticos

Se derivan de los casos de uso y flujos A y B de la [Semana 1](../semana-01/README.md). Cada RF indica el UC que lo origina.

## Requerimientos funcionales

| Código | UC | Requerimiento funcional | Criterio de aceptación |
|---|---|---|---|
| RF-SOAP-01 | UC-SOAP-01 | Registrar una nota SOAP en borrador con Subjetivo, Objetivo, Análisis y Plan. | **Dado** un médico tratante del episodio, **cuando** envía las cuatro secciones válidas, **entonces** se crea `clinical_notes` (`note_type = soap`, `draft`, versión 1) y se responde `201`. |
| RF-SOAP-02 | UC-SOAP-01 | Validar el formato de las cuatro secciones (texto de máximo 4000 caracteres, límite propuesto). | **Dado** que falta una sección o supera el límite, **cuando** se guarda, **entonces** `422 SOAP_SECTION_INVALID` y no se persiste nada. |
| RF-SOAP-03 | UC-SOAP-07 | Solo el médico con asignación activa en el episodio registra; solo el autor tratante asocia diagnósticos. | **Dado** un usuario sin `care_assignment` activo o que no es el autor, **cuando** registra o asocia, **entonces** `403 NOT_TREATING_PHYSICIAN`. |
| RF-SOAP-04 | UC-SOAP-02, 03 | Asociar a la nota un diagnóstico con código CIE-10 vigente y tipo `primary`, `secondary` o `differential`. | **Dado** una nota en borrador y un código vigente, **cuando** se asocia, **entonces** se crea `diagnoses` `active` con el mismo episodio y autor y se responde `201`. Código inexistente: `422 DIAGNOSIS_CODE_INVALID`. |
| RF-SOAP-05 | UC-SOAP-04 | Rechazar un código ya asociado a la nota y un segundo diagnóstico principal. | **Dado** que la nota ya tiene `J18.9` principal, **cuando** se asocia `J18.9` otra vez o `I10` como principal, **entonces** `409 DIAGNOSIS_DUPLICATED` o `409 PRIMARY_DIAGNOSIS_EXISTS`. |
| RF-SOAP-06 | UC-SOAP-03 | Si el catálogo CIE-10 no responde, no asociar nada. | **Dado** el catálogo caído, **cuando** se asocia un diagnóstico, **entonces** `503 ICD10_UNAVAILABLE` y la nota queda igual. |
| RF-SOAP-07 | UC-SOAP-05, 06 | Una nota firmada no acepta diagnósticos nuevos. | **Dado** una nota `signed`, **cuando** se asocia un diagnóstico, **entonces** `409 NOTE_SIGNED`; el cambio se hace con una corrección (UC-SOAP-06). |

## Requerimientos no funcionales

| Código | Categoría | Requerimiento no funcional | Criterio de aceptación (medible) |
|---|---|---|---|
| RNF-SOAP-01 | Seguridad | JWT, tenant y permisos `notas_soap.crear` y `diagnosticos.asociar`. | El 100 % de las peticiones sin permiso o con `X-Tenant-ID` ajeno reciben `401`/`403`. |
| RNF-SOAP-02 | Integridad | Diagnóstico con el mismo episodio y autor que su nota; nada se borra. | Los triggers de la migración `000400` rechazan el 100 % de los casos inconsistentes de la prueba. |
| RNF-SOAP-03 | Seguridad del paciente | Falla segura del catálogo. | Con el catálogo caído, 0 diagnósticos guardados sin validar el código. |
| RNF-SOAP-04 | Rendimiento | Asociación rápida en consulta. | `POST /soap-notes/{note}/diagnoses` ≤ 300 ms (p95) con el catálogo en caché. |
| RNF-SOAP-05 | Privacidad | La auditoría no guarda texto clínico. | Los eventos solo tienen identificadores, código y acción. |
| RNF-SOAP-06 | Mantenibilidad (SRP) | Cada clase del flujo tiene una sola razón para cambiar. | Cambiar el origen del catálogo solo cambia la implementación de `Icd10Catalog`; `SoapNoteRegistrar`, `DiagnosisPolicy` y sus pruebas no cambian ([demo](./evidencia/demo-srp.php)). |

## Escenarios de aceptación

```gherkin
Escenario: Registro y diagnóstico principal
  Dado que la Dra. López tiene care_assignment activo en el episodio
  Cuando registra la nota SOAP completa y asocia J18.9 como primary
  Entonces recibe 201 en ambos pasos y la nota queda en draft con un diagnóstico principal

Escenario: Segundo diagnóstico principal
  Dado que la nota ya tiene J18.9 como primary
  Cuando la médica asocia I10 como primary
  Entonces recibe 409 PRIMARY_DIAGNOSIS_EXISTS y la nota conserva un solo principal

Escenario: Catálogo CIE-10 caído (falla segura)
  Dado que el catálogo no responde
  Cuando la médica asocia J18.9
  Entonces recibe 503 ICD10_UNAVAILABLE y no se guarda ningún diagnóstico

Escenario: Cambio de catálogo sin tocar otras clases (SRP)
  Dado que el grupo decide leer el CIE-10 desde una tabla en lugar de un archivo
  Cuando se registra otra implementación de Icd10Catalog
  Entonces SoapNoteRegistrar, DiagnosisPolicy y TreatingPhysicianGuard no se modifican
```
