# Notas SOAP — Semana 17: matriz de amenazas

Modelo STRIDE aplicado a las tres rutas del módulo (`POST /care-episodes/{id}/soap-notes`, `POST /soap-notes/{id}/diagnoses`, `GET /soap-notes/{id}`) y a su persistencia. Cada amenaza lleva el control que la cubre, la prueba que lo demuestra y su estado. Las pruebas `T1` a `T8` están en `tests/Database/Hospital/NotasSoapSecurityPostgresTest.php` (PostgreSQL real, middlewares y RBAC reales).

| ID | STRIDE | Amenaza | Control | Prueba | Estado |
|---|---|---|---|---|---|
| A1 | Suplantación | Llamar sin token o con un token de otro hospital | `tenant` + `jwt.auth` antes del permiso | Recorrido real: 401 sin token, 403 `TENANT_MISMATCH` con otro hospital ([evidencia](./evidencia-funcional.md)); T3 | Cubierta |
| A2 | Suplantación | Forjar el autor con una cabecera `X-User-ID` o con campos del cuerpo | El actor sale del `sub` del token y se traduce a `local_user_refs`; sin referencia, 403 `LOCAL_USER_NOT_FOUND` | `test_actor_comes_from_the_token…`, T2, resolutor contra PostgreSQL | Cubierta |
| A3 | Manipulación | Inyección SQL en ids, código de diagnóstico o secciones | Sentencias preparadas; ids con forma UUID; código con forma CIE-10 | T1, U-15 | Cubierta |
| A4 | Manipulación | Asignación masiva: `status: signed`, `medical_record_id`, `version_no`, `hospital_id`, `author_user_ref_id` | El caso de uso solo lee las cuatro secciones y el código; lo demás lo decide el servidor | T2 | Cubierta |
| A5 | Manipulación | Agregar un diagnóstico a una nota firmada tras leerla (carrera) | `addDiagnosis` relee el estado con `FOR SHARE` dentro de la transacción (409 `NOTE_SIGNED`) | Prueba de la carrera en PostgreSQL | **Parcial:** el trigger de `diagnoses` no mira el estado de la nota; un INSERT directo se salta el control (ASII-09) |
| A6 | Repudio | Negar haber registrado una nota o un diagnóstico | `local_access_audits` con actor local, acción y `correlation_id`; solo inserciones | T7, prueba de la auditoría | Cubierta para escrituras |
| A7 | Repudio | Leer una nota sin dejar rastro | — | — | **Abierta:** las lecturas (`GET`) no se auditan en `local_access_audits`; solo las escrituras |
| A8 | Divulgación | Saber qué notas existen sin atender el episodio | Misma respuesta `404 NOTE_NOT_FOUND` para nota ausente y no autorizada | T4, B-17 | Cubierta |
| A9 | Divulgación | Filtrar internos (SQL, trazas, rutas) en los errores | Errores `{code, trace_id}`; `APP_DEBUG=false`; `Cache-Control: no-store, private` | T6; recorrido real | Cubierta |
| A10 | Divulgación | Texto clínico en la auditoría o en el log del servidor | La auditoría guarda ids y códigos, no secciones | T7; el log del servidor del recorrido real tiene 0 coincidencias con el texto de la nota | Cubierta |
| A11 | Divulgación | Que quien no es médico tratante lea o escriba | RBAC (`notas_soap.*`) y asignación activa vía `doctor_profiles` | `test_permissions_follow_the_real_rbac_matrix`, `NotasSoapPostgresTest` | Cubierta |
| A12 | Denegación | Secciones enormes o solo en blanco | Límite de 4000 caracteres y rechazo de espacios Unicode (D-01) | T8, U-13, U-14 | Cubierta |
| A13 | Denegación | Base HOSPITAL caída | `503 HOSPITAL_NO_DISPONIBLE` sin traza; la falla es segura | `test_hospital_database_down…` | Cubierta |
| A14 | Denegación | Ráfagas de peticiones | — | — | **Abierta:** las rutas del módulo no tienen un límite de tasa propio (el catálogo de medicamentos sí lo tiene) |
| A15 | Elevación | Enfermería escribiendo o un médico asociando a la nota de otro | Matriz RBAC real y guardia de autor (`NOT_TREATING_PHYSICIAN`) | `test_permissions_follow_the_real_rbac_matrix`, W-02, W-08 | Cubierta |
| A16 | Disponibilidad | Una sola nota por expediente (restricción única de la base, R-06) | Se informa como `409 RECORD_ALREADY_HAS_NOTE`, sin nota ni auditoría a medias | `test_known_limit_r06…` | **Abierta** (ASII-09) |
| A17 | Disponibilidad | Sin catálogo CIE-10, no se pueden asociar diagnósticos | Falla segura `503 ICD10_UNAVAILABLE` | Recorrido real | **Abierta** (ASII-09): desde #1620 la tabla existe, pero vacía; #1633 responde 503 mientras no tenga códigos activos. Falta cargar el catálogo oficial |

**Resumen:** 17 amenazas, 12 cubiertas, 1 parcial (A5) y 4 abiertas (A7, A14, A16, A17). A7 y A14 dependen de este módulo; A5, A16 y A17 dependen de la base (Javier).

## Qué no se probó

- Cifrado en reposo y en tránsito: es de la infraestructura de despliegue, que no está disponible.
- Expiración y revocación de tokens: es del módulo de Tenants (ASII-01); aquí se usa su middleware.
- La identidad de las pruebas Feature y Database se simula con `CatalogTestIdentity`; el recorrido real usa el JWT verdadero, pero solo para el médico.
