# Notas SOAP — Semana 17: evidencia del recorrido real

Ejecutado el 2026-10-22 en el entorno local de verificación ([bitácora](./bitacora-despliegue.md)): servidor HTTP de PHP, bases CENTRAL y HOSPITAL en PostgreSQL 16 con las migraciones del repositorio, usuario ficticio sembrado con `tests/E2E/fixtures/seed-e2e.php`, `JWT_SECRET` efímero y contraseña generada en la ejecución. **Todo el recorrido usa el JWT, el tenant y el RBAC reales**; no hay dobles.

| # | Petición | Resultado esperado | Obtenido |
|---|---|---|---|
| 1 | `POST …/care-episodes/{ep}/soap-notes` sin token | 401 | **401** |
| 2 | `POST /api/v1/auth/login` (médica, hospital 1) | token de acceso | **200**, `access_token` |
| 3 | `POST …/care-episodes/{ep}/soap-notes` con token | 201 en borrador, `X-Trace-Id`, sin caché | **201** `{"status":"draft"}`, `Cache-Control: no-store, private` |
| 4 | `GET …/soap-notes/{id}` | 200 con las cuatro secciones y el autor local | **200**; `author_user_ref_id` = referencia local de la médica |
| 5 | `POST …/soap-notes/{id}/diagnoses` (`J18.9`) | falla segura: no existe `icd10_codes` | **503** `ICD10_UNAVAILABLE` |
| 6 | Segunda nota en el mismo expediente | conflicto por R-06 | **500** `SOAP_PERSISTENCE_ERROR` en esta ejecución; desde #1080 responde **409** `RECORD_ALREADY_HAS_NOTE` |
| 7 | `GET …/soap-notes/{id}` con el hospital 2 | rechazo por tenant | **403** `TENANT_MISMATCH` |

## Lo que quedó en la base

| Tabla | Contenido |
|---|---|
| `clinical_notes` | 1 fila: `status = draft`, `version_no = 1`, en el expediente abierto de la paciente y con la médica como autora |
| `local_access_audits` | 1 fila: `notas_soap` / `nota_soap.registrada`, `metadata = {"version_no": 1}`, sin texto clínico |

El log del servidor (37 líneas) no contiene el texto de las secciones.

## Lo que este recorrido no demuestra

- Asociar un diagnóstico: falta la tabla `icd10_codes` (paso 5).
- Más de una nota por expediente (paso 6).
- Otros roles con token real: solo se usó la médica; el resto de roles se prueba con la matriz RBAC real pero con identidad simulada.
- Staging u on-premise: no hay acceso.
