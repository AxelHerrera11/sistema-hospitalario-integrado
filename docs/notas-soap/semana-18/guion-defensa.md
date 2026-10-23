# Guion de defensa — Notas SOAP y diagnósticos (ASII-11)

Duración prevista: 10 minutos de exposición y demo, y el resto para preguntas. Todo lo que se muestra está en [evidencia-demo.txt](./evidencia-demo.txt) y se puede reproducir con los comandos del [README](./README.md#demo-funcional).

## 1. Qué problema resuelve (1 min)

La nota clínica del episodio debe ser completa, tener un diagnóstico principal validado, quedar auditada y, una vez firmada, no cambiar. Si hay un error, no se edita la firmada: se corrige como una versión nueva que deja constancia del motivo.

## 2. Arquitectura (2 min)

- Un micro-HIS por capas (`Domain`, `Application`, `Persistence`, `Presentation`, `Client`) que no conoce Laravel; el HIS lo integra con un `ServiceProvider` y tres cosas: tenant, `jwt.auth` y RBAC.
- Los casos de uso hablan con un **puerto** (`ClinicalNoteRepository`); hay dos adaptadores con el mismo contrato, PDO y en memoria, y ambos pasan las mismas pruebas.
- Dos controles de acceso: el permiso `notas_soap.*` (de dos partes) y la regla clínica de **médico tratante** con asignación activa. Tener el permiso no basta.
- La identidad sale del token, nunca de una cabecera ni del cuerpo.

## 3. Demo (4 min)

1. **Registrar** una nota con las cuatro secciones → `201` en borrador, con `trace_id` y una fila de auditoría sin texto clínico.
2. **Asociar** J18.9 como principal → `201`, con la descripción tomada del catálogo y no del cliente. Un segundo principal → `409 PRIMARY_DIAGNOSIS_EXISTS`.
3. **Firmar** → `200 signed`. Firmar de nuevo → `409 NOTE_SIGNED`. Intentar un `UPDATE` directo sobre la fila en PostgreSQL → el trigger responde `clinical_notes_signed_immutable`.
4. **Corregir** con las secciones corregidas y el motivo → `201`, versión 2 en borrador; la versión 1 sigue firmada. Corregir otra vez la misma → `409 NOTE_ALREADY_CORRECTED`.
5. **Fallo seguro**: con la bitácora caída, firmar y corregir responden `500` y la nota no cambia. *Nunca se firma sin traza.*
6. Si el equipo lo permite, abrir `/notas-soap`: la pantalla ofrece solo las acciones que el estado y los permisos permiten, aunque el servidor decide de todos modos.

## 4. Qué no está terminado (2 min)

Decirlo antes de que lo pregunten: R-06 (una nota por expediente), la tabla `icd10_codes` y el trigger de D-02 son de ASII-09; el catálogo provisional de 20 códigos existe solo para la demo y está apagado por defecto; no se escribe `outbox_events`; la pantalla no se ejercitó en un navegador; y ningún PR de la cadena está revisado ni hay CI porque los checks no arrancan. La lista completa está en el [README](./README.md#lo-que-no-está-resuelto).

## Cambio solicitado previsto (hipótesis de defensa)

El enunciado oficial no especifica el cambio. Se ensaya este porque coincide con el riesgo R-06, el más visible del módulo:

> **«Permitir más de una nota por expediente.»**

- **Dónde está el límite:** en la base, no en el módulo. `clinical_notes_record_version_unique (medical_record_id, version_no)` junto con el trigger que exige `version_no = 1` en toda nota original. La versión debería ser única por **cadena de corrección** (por ejemplo, con un identificador de cadena), como propuso la Semana 1.
- **Qué cambiaría en el módulo:** casi nada. `PdoClinicalNoteStore::add()` ya inserta con `version_no = 1`; solo dejaría de dispararse la traducción a `409 RECORD_ALREADY_HAS_NOTE`, y la prueba `test_known_limit_r06…` de `NotasSoapPostgresTest` debe invertirse (así está anotado en ella).
- **Qué no se puede hacer solo:** cambiar la migración es de Javier (ASII-09); el módulo no debe tocar el esquema.
- **Lo que ya se hizo bien:** la corrección no choca con R-06, porque la versión 2 lleva `supersedes_id` y `version_no = 2`.

Otras variantes que se pueden cubrir con el mismo mecanismo: exigir que la corrección la firme otro médico (cambia `SignSoapNote` y su guardia, y una prueba) o agregar un cuarto tipo de diagnóstico (cambia `Diagnosis::TYPES`, la lista de la pantalla y una prueba).

## Preguntas esperadas

**¿Cómo se evita firmar sin dejar traza?** La firma, el `UPDATE` de la nota y la inserción en `local_access_audits` van en una sola transacción de la conexión `hospital`. Si la bitácora falla se revierte todo y se responde `500`. Está probado con ambos adaptadores y contra PostgreSQL.

**¿Qué pasa si dos médicos firman o corrigen a la vez?** La fila se bloquea con `FOR UPDATE`; la segunda petición ve el resultado de la primera y recibe `409`. La base respalda la regla: una segunda versión 2 la rechaza `clinical_notes_record_version_unique`.

**¿Por qué leer una nota ajena da 404 y no 403?** Para no revelar qué identificadores existen (defecto D-03 de la Semana 15). Firmar o corregir una nota inexistente sí da 404 frente a 403 si es ajena; está declarado como pendiente y mitigado por los UUID v4.

**¿Por qué el catálogo provisional y por qué está apagado?** Sin `icd10_codes` no se podía asociar un diagnóstico por el API real, y firmar exige uno. El provisional solo responde si el catálogo real no está disponible, nunca lo contradice, y viene apagado: por defecto el módulo falla cerrado con `503`. No es apto para uso asistencial.

**¿Cómo se garantiza que una nota firmada no cambia?** Con dos capas: el caso de uso rechaza (`409`) y la base lo impide con un trigger. La segunda capa no depende del módulo y tiene una prueba propia.

**¿Por qué la corrección pide las cuatro secciones?** Porque el módulo no tiene una ruta para editar un borrador; sin ellas, la versión nueva repetiría el texto. Es una decisión mía sobre el contrato, documentada en `contrato-api.md`.

**¿Qué hace el campo del motivo y dónde queda?** Queda en `clinical_notes.correction_reason`. La auditoría guarda el `version_no` y el `supersedes_id`, pero no el motivo ni el texto clínico.

**¿Qué no se midió?** El rendimiento: no hay medición propia de latencia ni de carga. Las pruebas contra PostgreSQL validan reglas y restricciones, no tiempos.

**¿Por qué no hay CI?** Los checks de GitHub Actions no arrancan por la facturación de la cuenta de la organización. Es ajeno al código; las pruebas se ejecutaron en local y su salida está en la evidencia.
