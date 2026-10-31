# Matriz de amenazas: alergias clínicas (Semana 17)

Alcance: `POST /api/v1/patients/{patient}/allergies`, `GET /api/v1/patients/{patient}/allergy-alert` y la pantalla `/alergias`. La autoridad es el servidor. Que la pantalla oculte acciones no reemplaza JWT, tenant, RBAC ni las reglas del dominio.

Evidencia: **S** es `tests/Feature/Alergias/AllergySecurityTest.php`, **F** es `AllergyApiTest.php` (S16) y **R** es el [recorrido de seguridad](./evidencia/salida-recorrido-seguridad.txt) contra el despliegue local.

| ID | Amenaza | Control | Evidencia | Estado |
|---|---|---|---|---|
| A01 | Operar sin sesión o con un token alterado | `jwt.auth` antes de RBAC y del controller | R: sin token 401; firma alterada 401 | Mitigada |
| A02 | Leer o escribir alergias de otro hospital | `tenant` contrasta JWT y `X-Tenant-ID`; guard de #1154 sobre la base HOSPITAL | R: token A con cabecera B 403. F: guard 403 y 503 sin configurar | Mitigada |
| A03 | Un rol sin permiso consulta o registra | `rbac:alergias.ver` y `rbac:alergias.registrar` con denegación por defecto | S: los 8 roles de `config/rbac.php`; solo superadmin, medico y enfermeria. R: admin 403, recepción 403 | Mitigada |
| A04 | Elegir desde el cuerpo el paciente, el estado o el autor | Paciente de la ruta, estado `active` del dominio, actor del JWT en `local_user_refs`; los campos extra se ignoran | S y R: el cuerpo pide PAC-E2E-02, `resolved` y otro autor; se guarda en PAC-E2E-01, `active`, autor medico ([psql](./evidencia/salida-psql.txt)) | Mitigada |
| A05 | Registrar sin referencia clínica local | `ACTOR_WITHOUT_LOCAL_REFERENCE` (403) antes de escribir | F y `AlergiasPostgresTest` (#1323) | Parcial: falta la sincronización de ASII-25 |
| A06 | Texto enorme que llena la base y el banner | Límites del dominio iguales al `maxlength` de la pantalla: código 50, nombre 200, reacción 500 → `TEXT_TOO_LONG` | S: 201, 51 y 100 000 caracteres 422; justo en el límite 201. R: 422 | **Corregida en esta entrega** |
| A07 | Inyección SQL por alérgeno o reacción | `PdoAllergyRepository` usa sentencias preparadas; ningún valor se concatena al SQL | Revisión del adaptador; R: `1 OR 1=1` en la ruta da 404 | Mitigada |
| A08 | Inyección en la traza (`X-Request-ID`) o en los logs | Patrón `^[A-Za-z0-9._-]{1,64}$`; si no cumple, se genera `req-…` | S: HTML, 65 caracteres y CRLF reemplazados. R: `<script>` reemplazado | Mitigada |
| A09 | Filtrar SQL, credenciales o host con la base caída | `PersistenceFailure` → 503 `ALLERGIES_UNAVAILABLE` con `APP_DEBUG=false` | F: sin `secreto`. R: HOSPITAL en un puerto cerrado da 503 sin `SQLSTATE` ni host | Mitigada |
| A10 | Mostrar «sin alergias» cuando no se pudo leer | Falla segura del dominio: `unavailable` → 503 con el aviso «verificar con el paciente» | F: 503 y nunca `none`. R: 503 | Mitigada |
| A11 | XSS almacenado por el nombre del alérgeno | La API devuelve el texto literal; la pantalla interpola con `{{ }}` y no usa `v-html` | S y R: `<img src=x onerror=…>` vuelve literal; `grep v-html` en el módulo vacío | Mitigada |
| A12 | Datos clínicos en logs o en cachés intermedias | Los logs solo llevan `request_id`, hospital e `allergy_id`; `Cache-Control: no-store, private` | S: ni alérgeno, ni reacción, ni severidad, ni paciente en los logs. R: cabecera presente | Mitigada |
| A13 | Borrar o reescribir el historial de alergias | Trigger de retención de HOSPITAL; la API no expone borrado ni edición | Migración 000401; `MedicalRecordIntegrityTest` (retención) | Mitigada |
| A14 | Agotar la API con solicitudes repetidas | Límite de tasa común de `/api/v1` | No hay control propio verificable | Pendiente: API Gateway |

## Riesgo residual

- **A05:** las `local_user_refs` se siembran a mano hasta que exista la sincronización desde CENTRAL (Tenants, ASII-25).
- **A02:** hay una sola conexión HOSPITAL. El guard de #1154 cierra el acceso a otros hospitales, pero el aislamiento físico por tenant es tarea de Plataforma/Tenants.
- **A14:** este módulo no declara un límite de tasa propio.
