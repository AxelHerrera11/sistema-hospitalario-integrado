# Checklist técnico

| ID | Elemento | Evidencia | Estado | Observación |
|---|---|---|---|---|
| RT-01 | Registro y campos requeridos | `Patient::register()`/constructor; `PatientService::register()`; prueba `run.php` | Conforme | Rechaza vacíos en dominio y ejecuta alta por Application. |
| RT-02 | Búsqueda/consulta | `PatientService::search()`; `findByCode()` de ambos adaptadores; prueba | Conforme | Es búsqueda exacta por código, no listado. |
| RT-03 | Edición | `Patient::update()` y SQL `UPDATE`; prueba conserva `id`, `global_id`, `code` | Parcial | Campos identidad se conservan; PDO no comprueba filas afectadas (REV-07). |
| RT-04 | Duplicados | Service comprueba código; migration declara unique para código/global_id; prueba duplicado código | Parcial | `global_id` no se precomprueba y conflicto/race acaba como persistencia genérica (REV-04). |
| RT-05 | Fecha futura/formato | Dominio valida `Y-m-d` y no futura; migration check; prueba futura | Conforme | Validado en dominio, esquema y prueba de regla. |
| RT-06 | Identidad local y pública | DB `local_patient_id`; Domain `$id`; `PatientView` emite `id`; OpenAPI requiere `patient_id` | No conforme | Falta mapping explícito al nombre público (REV-03). |
| RT-07 | `global_id` y MPI | DB UUID con comentario lógico sin FK central; OpenAPI UUID; Domain valida solo no vacío | Parcial | No hay sincronización MPI ni validación UUID en dominio; DB error se traduce como 500 (REV-05). |
| RT-08 | Código y SQL | `code` único en schema; `SELECT` parametrizado por código | Conforme | No hay búsqueda por nombre ni concatenación del parámetro en SQL. |
| RT-09 | Privacidad de respuesta | `PatientView` incluye `global_id`, código, nombre, nacimiento y contacto | Parcial | Campos corresponden al esquema/contrato; faltan endpoints y autorización efectiva que delimiten acceso. |
| RT-10 | Arquitectura y Repository | Controller/Presenter/View; Service depende de `PatientRepository`; dos adaptadores | Conforme | Capas separadas; PDO no está en Application. |
| RT-11 | Errores y códigos HTTP | Presenter asigna 404/422/500 con envelope interno; OpenAPI describe Problem JSON y 409 | Parcial | No hay adaptador HTTP; duplicate-code `422` difiere de `409` contractual; global duplicate se vuelve 500 (REV-04). |
| RT-12 | Contrato OpenAPI | `POST`, `GET {code}`, `PATCH`; esquemas y headers tenant/user/request | Parcial | Contrato completo en papel, sin implementación HTTP; ID y errores requieren mapping. |
| RT-13 | Permisos por operación | Tres permisos en `config/rbac.php` y extensiones OpenAPI; middleware RBAC genérico | No conforme | `routes/api.php` no tiene rutas de Pacientes; los permisos no se aplican a operaciones del módulo (REV-02). |
| RT-14 | Tenant/JWT, PDO, PostgreSQL, pruebas y frontend | Middlewares generales; PDO y migration; `run.php` InMemory; DB tests de esquema; sin Vue Pacientes | Parcial | Falta wiring de API, selección PDO tenant y prueba PDO; no existe frontend (REV-01, REV-06). |

**Estados:** Conforme, Parcial, No conforme, No verificable. No se usa
“Conforme” para contrato o seguridad sin ejecución.
