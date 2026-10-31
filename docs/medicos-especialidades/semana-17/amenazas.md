# Matriz de amenazas (STRIDE) de Médicos, especialidades y disponibilidad

Objeto: `POST /api/v1/doctors`, `POST /api/v1/doctors/{doctor}/specialties`, `GET /api/v1/availability` (#1039), el adaptador PDO (#1038, #1042) y la pantalla `/medicos` (#1045).
Pruebas: `tests/Feature/Medicos/DoctorSecurityTest.php` (T1–T7, adaptador PDO real sobre SQLite), `DoctorEndpointTest.php` (contrato HTTP), `tests/Feature/Rbac/DoctorPermissionsTest.php` (matriz de roles) y `tests/Database/Hospital/DoctorsPostgresTest.php` (PostgreSQL 16).

| ID | STRIDE | Amenaza | Control | Evidencia | Estado |
|---|---|---|---|---|---|
| MED-01 | Suplantación | Llamar al API sin identidad | `tenant` + `jwt.auth` antes de `rbac:`; 401 en las tres rutas | T7, `DoctorEndpointTest::test_sin_token_responde_401_en_las_tres_rutas` | Probado |
| MED-02 | Suplantación | Hacerse pasar por otro actor con `X-User-ID` u otra cabecera | El controlador arma la petición del módulo con el actor local resuelto en el servidor; las cabeceras del cliente no pasan | T3 | Probado |
| MED-03 | Suplantación | Operar en otro hospital con `X-Tenant-ID` | La cabecera debe coincidir con el hospital del contexto: 403 `TENANT_MISMATCH` y no se escribe | T7, `DoctorEndpointTest` | Probado |
| MED-04 | Manipulación | Inyección SQL en licencia, especialidad o id de ruta | Sentencias preparadas; el id de ruta solo admite `[\w-]+` | T1 | Probado con SQLite real. En PostgreSQL el adaptador es el mismo código, pero `DoctorsPostgresTest` no incluye casos de inyección |
| MED-05 | Manipulación | Asignación masiva: fijar `doctor_profile_id`, `status`, `valid_to` u hospital en el alta | El `DoctorController` solo lee campos conocidos; el sistema fija id uuid, `status = active` y vigencia | T2 | Probado |
| MED-06 | Manipulación | Burlar la unicidad de la licencia con espacios o minúsculas | La licencia se normaliza (`trim` + mayúsculas) antes de comprobar y guardar (D-01) | `tests/run.php`, `DoctorsPostgresTest` | Probado. No cubre licencias ya guardadas sin normalizar |
| MED-07 | Repudio | No poder rastrear quién registró o asoció una especialidad | `local_access_audits` con `actor_user_ref_id`, `correlation_id`, `action` y `resource_id`, en la misma transacción que el alta | `DoctorsPostgresTest` (alta y atomicidad) | Probado en PostgreSQL |
| MED-08 | Repudio | Consultas de disponibilidad y denegaciones sin rastro propio | Las denegaciones las registra `LogDenials` de la plataforma; las lecturas de disponibilidad no se auditan | — | **Parcial:** este módulo no audita lecturas ni verifica el registro de denegaciones |
| MED-09 | Divulgación | Errores con SQL, rutas, trazas o el dato enviado | El módulo devuelve solo `{"code": ...}`; el fallo de base es 500/503 con código fijo | T4 | Probado |
| MED-10 | Divulgación | Respuestas guardadas por intermediarios | `Cache-Control: no-store, private` en éxitos y errores | T5 | Probado |
| MED-11 | Divulgación | La disponibilidad expone pacientes, licencias o usuarios | La respuesta tiene solo `doctor_id`, `status` y `slots`; el lector de citas entrega intervalos sin paciente (RNF-MED-03) | T6 | Probado |
| MED-12 | Divulgación | Inyección de HTML en la pantalla | La pantalla solo usa interpolación `{{ }}` (escapada por Vue); no usa `v-html` | Revisión del código de `DoctorsPage.vue` | Verificado por lectura; sin prueba automatizada |
| MED-13 | Divulgación | Un tenant lee o escribe datos de otro hospital | `EnsureHospitalDatabaseTenant` (#1154) en las tres rutas: un hospital distinto del asignado a la base HOSPITAL recibe 403 `HOSPITAL_DATABASE_TENANT_MISMATCH` y, sin asignación, 503 | `DoctorEndpointTest` (guard) | **Parcial (plataforma):** la falla es cerrada, pero cada instancia atiende un solo hospital; varios hospitales exigen una base HOSPITAL por despliegue |
| MED-14 | Denegación de servicio | Ráfagas de altas o consultas | — | Las rutas no declaran `throttle` | **Abierto:** sin límite de tasa |
| MED-15 | Denegación de servicio | Campos de longitud ilimitada (licencia, referencia de usuario, código) | — | `RegisterDoctor` y `Request` no validan longitud máxima | **Abierto:** solo se valida que no estén vacíos |
| MED-16 | Denegación de servicio | Caída de la base HOSPITAL | 503 `DOCTOR_STORAGE_UNAVAILABLE` explícito; el alta es atómica y no deja datos a medias | `DoctorEndpointTest` (base caída e inexistente), `DoctorsPostgresTest` | Probado |
| MED-17 | Elevación | Operar sin el permiso de la operación | Cada ruta declara su `rbac:medicos.*`; recepción y médico solo ven disponibilidad, enfermería nada | `DoctorEndpointTest::test_cada_operacion_exige_su_propio_permiso`, `DoctorPermissionsTest` | Probado |
| MED-18 | Elevación | Usuario autenticado sin referencia local registrado como actor | 403 `ACTOR_NOT_PROVISIONED` y no se escribe | `DoctorEndpointTest` | Probado |

**Resumen:** 18 amenazas · 13 probadas · 1 verificada por lectura · 2 parciales · 2 abiertas.

**Riesgos abiertos priorizados:** MED-13 (aislamiento entre hospitales, de la plataforma), MED-14 (sin límite de tasa) y MED-15 (sin longitud máxima de campos). Ninguno se declara mitigado.
