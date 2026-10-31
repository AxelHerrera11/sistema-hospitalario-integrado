# Matriz de amenazas: admisión hospitalaria y asignación de cama (Semana 17)

Alcance: `POST /api/v1/admissions`, `GET /api/v1/beds/available` y la pantalla `/admision`. La autoridad es el servidor. Que la pantalla oculte acciones no reemplaza JWT, tenant, RBAC ni las reglas del dominio y de los índices de HOSPITAL.

Evidencia: **S** es `tests/Feature/Admision/AdmissionSecurityTest.php`, **F** es `AdmissionApiTest.php` (S16) y **R** es el [recorrido de seguridad](./evidencia/salida-recorrido-seguridad.txt) contra el despliegue local.

| ID | Amenaza | Control | Evidencia | Estado |
|---|---|---|---|---|
| D01 | Operar sin sesión o con un token alterado | `jwt.auth` antes de RBAC y del controller | R: sin token 401; firma alterada 401 | Mitigada |
| D02 | Admitir o ver camas de otro hospital | `tenant` contrasta JWT y `X-Tenant-ID`; guard de #1154 sobre la base HOSPITAL | R: token A con cabecera B 403. F: guard 403 | Mitigada |
| D03 | Un rol sin permiso lista camas o admite | `rbac:camas.ver` y `rbac:admisiones.registrar` con denegación por defecto | S: los 8 roles de `config/rbac.php`; solo superadmin y recepcion. R: médico 403 en ambas rutas | Mitigada |
| D04 | Elegir desde el cuerpo el autor, la hora de inicio o el episodio | Actor del JWT en `local_user_refs`; hora e identificadores del servidor; los campos extra se ignoran | S y R: `assigned_by_user_ref_id` y `started_at` de 1999 se ignoran ([psql](./evidencia/salida-psql.txt): asignada por recepcion, inicio hoy) | Mitigada |
| D05 | Un arreglo en el JSON tumba la API (500) | El controller y `AdmitPatient` solo aceptan texto: un arreglo cuenta como ausente | S y R: `patient_id` y `bed_id` como arreglo dan 404; `reason` como arreglo da 422 | **Corregida en esta entrega** |
| D06 | Motivo de ingreso enorme | Límite del dominio igual al `maxlength` de la pantalla (500) → `TEXT_TOO_LONG` | S: 100 000 caracteres 422 y 500 con acentos 201. R: 501 da 422 | **Corregida en esta entrega** |
| D07 | Dos pacientes en la misma cama por una carrera | Dominio + exclusión GiST `bed_stays_bed_active_period_exclusion`: la base decide y la API responde 409 `BED_NOT_AVAILABLE` | `AdmisionPostgresTest` (#1324): cama ocupada rechazada sin dejar restos | Mitigada |
| D08 | Inyección en la traza (`X-Request-ID`) | Patrón `^[A-Za-z0-9._-]{1,64}$`; si no cumple, se genera `req-…` | S: HTML con CRLF reemplazado. R: `<script>` reemplazado | Mitigada |
| D09 | Filtrar SQL, credenciales o host con la base caída | `PersistenceFailure` → 503 `ADMISSIONS_UNAVAILABLE` con `APP_DEBUG=false` | F: sin detalles. R: HOSPITAL en un puerto cerrado da 503 sin `SQLSTATE` ni host | Mitigada |
| D10 | El listado de camas revela quién ocupa cada cama | La respuesta solo trae `bed_id`, `code` y `type` de camas libres | S y R: campos exactos; sin identificadores de paciente | Mitigada |
| D11 | Motivo de ingreso o paciente en logs o en cachés | Los logs solo llevan `request_id`, hospital y `bed_stay_id`; `Cache-Control: no-store, private` | S: ni motivo ni paciente en los logs, también en el 503. R: cabecera presente | Mitigada |
| D12 | Inyección SQL por el tipo de cama o los identificadores | `PdoAdmissionRepository` usa sentencias preparadas; los UUID se validan antes | Revisión del adaptador; S: `type[]=icu` da 200 sin error | Mitigada |
| D13 | Admitir sin referencia clínica local | `ACTOR_WITHOUT_LOCAL_REFERENCE` (403) antes de escribir | F y `AdmisionPostgresTest` (#1324) | Parcial: falta la sincronización de ASII-25 |
| D14 | Agotar la API con solicitudes repetidas | Límite de tasa común de `/api/v1` | No hay control propio verificable | Pendiente: API Gateway |

## Riesgo residual

- **D13:** las `local_user_refs` se siembran a mano hasta que exista la sincronización desde CENTRAL (Tenants, ASII-25).
- **D02:** hay una sola conexión HOSPITAL. El guard de #1154 cierra el acceso a otros hospitales, pero el aislamiento físico por tenant es tarea de Plataforma/Tenants.
- **D14:** este módulo no declara un límite de tasa propio.
