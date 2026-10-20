# Guion de la defensa — Notas SOAP y diagnósticos (ASII-11), Semana 6

## Orden de la exposición

1. **Problema** (1 min). Una nota SOAP signed es la evidencia clínica oficial de una atención: tiene que decir quién la escribió, qué se diagnosticó y no puede cambiar después. La pregunta de la semana no es «¿cómo guardo un formulario?», sino «¿qué garantiza que esa nota sea evidencia?».

2. **Tesis** (1 min 30 s). La nota no es un formulario: es un documento con autoría, versión y firma. De ahí las tres garantías: **autoría en dos niveles** (permiso RBAC + médico tratante del episodio), **todo o nada** (nota, diagnóstico y auditoría en una sola transacción) y **inmutabilidad tras la firma** (el cambio crea una versión nueva). Y de la tercera sale la tesis que el caso pone a prueba: **la autoría clínica es una cadena, no un campo**.

3. **Del actor al contrato** (2 min). Recorrer la cadena con el diagrama: actor → UML (UC-SOAP-01/02) → RF/RNF (RF-SOAP-01..07, RNF-SOAP-01..06) → SOLID → capas (Presentation, Application, Domain, Persistence) → Repository (`ClinicalNoteRepository`, `AuditTrail`) → contrato `/api/v1`. El mensaje: **no son seis temas sueltos, es una misma decisión aplicada en los seis eslabones** — cada uno explica por qué existe el siguiente.

4. **SOLID en 60 s** (1 min). SRP: una razón de cambio por clase (`SoapSections`, `DiagnosisPolicy`, `Icd10Catalog`, `TreatingPhysicianGuard`, `ClinicalNoteRepository`, `AuditTrail`). OCP: las reglas clínicas viven en políticas sin dependencias, no en el controlador ni en el SQL. DIP: los puertos están en Application. LSP: los dos adaptadores cumplen las mismas garantías y la misma batería los corre. ISP: los puertos son pequeños (`CareAssignmentReader`, `Icd10Catalog`, `PermissionChecker`, `AuditTrail`).

5. **Lo implementado y lo propuesto** (45 s). Decirlo primero, sin adornos: registrar, asociar diagnóstico y leer están en código con sus pruebas; **firmar y corregir están en el contrato, no en el código**, y se implementan en la Semana 7. El versionado de la corrección está en el modelo, en los triggers y en el contrato.

6. **Caso práctico: el relevo de guardia** (3 min). El pedido (Dra. López escribe, deja de ser tratante, el Dr. Ramírez debe completar y firmar; el paciente vuelve en otro episodio del mismo expediente). Lo que hoy lo impide — y aquí está la clave de la respuesta —: **el obstáculo no es el código, es el modelo de datos**: `clinical_notes_author_assignment_trigger`, `diagnoses_note_episode_author_trigger`, `clinical_notes_signed_immutable` y `clinical_notes_record_version_unique`. Después, la tabla de impacto por capa: Domain (`NoteAuthorship` + `AuthorshipPolicy`), Application (`TransferNoteAuthorship`), Repository (un método, misma garantía de todo o nada), Persistence (los dos adaptadores), base (entidad nueva + dos triggers), contrato (endpoint nuevo, aditivo, sigue en `/api/v1`), consumidores (solo Expediente).

7. **Lo que no cambia** (45 s). Falla segura del catálogo, falla cerrada de RBAC, atomicidad, inmutabilidad, versión de la API y reintento único en lecturas. Un cambio que no toca las garantías es un cambio maduro.

8. **Petición a Javier** (1 min). Tres puntos: entidad nueva `clinical_note_authorship_transfers` (con el SQL propuesto), cambiar dos triggers para reconocer al autor vigente, y mover la unicidad de versión de «por expediente» a «por cadena de corrección» (R1/R-06). Dejar claro qué **no** se pide: nada de tabla de diagnósticos, nada de `local_access_audits`, nada de `medical_records` ni `care_assignments`.

9. **Riesgos abiertos** (30 s). Los cinco que no escondo: unicidad por expediente, `icd10_codes` sin origen definido, `local_access_audits` sin migración, RBAC como doble con `X-User-ID` no autoritativo, y la lectura que exige asignación activa (enfermería tiene permiso pero no asignación).

10. **Conclusión** (20 s). La arquitectura que defiendo es la que hace que la nota sea evidencia y no un registro: autoría explícita, escritura atómica con su traza, inmutabilidad con versión, y una frontera por contrato que los demás módulos respetan. El caso del relevo no la desarma: la obliga a ser explícita en un punto que hoy está oculto — **quién autoró**.

## Preguntas que preparo

- **¿Por qué el permiso RBAC no basta?** Porque el permiso es del rol y la asignación es del episodio. `dr.perez` tiene `notas_soap.crear` y aun así recibe `403 NOT_TREATING_PHYSICIAN`: sin esa segunda capa, un médico con el permiso escribiría notas en consultas ajenas.
- **¿Por qué auditar en la misma transacción y no con un evento?** Por dos razones distintas: la **evidencia** local (firma sin traza no es evidencia) va en la transacción, y la **notificación** a CENTRAL va en el `outbox_events`, que se publica después. Si se invirtiera, una caída de CENTRAL dejaría notas firmadas sin notificar; con el orden actual, una caída de CENTRAL no deshace la firma local.
- **¿Por qué una nota firmada no se edita?** Porque la edición destruye la evidencia de lo que el médico vio y decidió. La corrección crea una versión nueva con motivo, y el encadenamiento (`supersedes_id`, `version_no = n + 1`) deja el rastro: qué se corrigió, por qué y quién.
- **¿Por qué el Repository como puerto y no un DAO?** Porque quien define la garantía debe ser la aplicación, no el adaptador. Por eso la misma batería corre contra PDO y contra el doble en memoria: si un adaptador cumpliera menos, fallaría la prueba, no la producción.
- **¿Por qué no es un microservicio?** Con base propia se pierden tres FK y dos triggers de integridad, y la firma quedaría sin outbox. Notas SOAP es módulo del HIS con frontera lógica: los demás leen por `/api/v1`, nunca por SQL.
- **¿Qué pasa si dos médicos firman la misma nota a la vez?** Hoy el doble no tiene control de concurrencia. En PostgreSQL se resuelve en la Semana 7 con la actualización condicionada (`WHERE status = 'draft'`) dentro de la transacción: la segunda firma encuentra 0 filas affected y devuelve `409`. Lo anoto como pendiente, no como resuelto.
- **¿Y el versionado por expediente?** Es el riesgo R1 de la Semana 1, escalado como R-06: `UNIQUE (medical_record_id, version_no)` deja **una sola nota original por expediente**, así que un paciente con dos consultas no cabe. Va a Javier como punto 3 de la petición.
- **¿Y si el relevo se equivoca al transferir?** La transferencia es irreversible desde el módulo (es evidencia, no se borra) y exige motivo; el traspaso queda en `clinical_note_authorship_transfers` y en la auditoría, y la nota firmada conserva a su autor original.

## Archivos de apoyo

- [README de la evaluación](./README.md): tesis, trazabilidad, decisiones defendidas, caso práctico y petición a la base de datos.
- [Semana 1](../semana-01/README.md): UML, actores y narrativa de alcance (integrada en `develop`, PR #8).
- [Semana 2](../semana-02/rf-rnf-criterios.md): RF/RNF y criterios de aceptación (PR #65).
- [Semana 5](../semana-05/contrato-api.md): contrato API v1 y [OpenAPI](../semana-05/openapi.yaml) (PR #374).

> Los enlaces a las Semanas 2 a 5 resuelven cuando la cadena de PRs (#65, #130, #379 y #374) esté integrada en `develop`; esta rama solo añade la entrega de la Semana 6.
