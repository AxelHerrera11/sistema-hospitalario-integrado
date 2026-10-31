# Matriz de amenazas: catálogo de pruebas de laboratorio (Semana 17)

Alcance: `GET` y `POST /api/v1/lab-tests` y la pantalla `/laboratorio/catalogo` (#1247). No guarda datos de pacientes, pero es la fuente de los rangos críticos que usan Resultados (ASII-19) y Alertas críticas (ASII-21). Un rango alterado o inválido pone en riesgo a pacientes. La autoridad es el servidor.

Evidencia: **S** es `tests/Feature/CatalogoLab/LabTestSecurityTest.php`, **F** es `LabTestApiTest.php` (#1320) y **R** es el [recorrido de seguridad](./evidencia/salida-recorrido-seguridad.txt) contra el despliegue local.

| ID | Amenaza | Control | Evidencia | Estado |
|---|---|---|---|---|
| L01 | Operar sin sesión o con un token alterado | `jwt.auth` antes de RBAC y del controller | R: sin token 401; firma alterada 401 | Mitigada |
| L02 | Leer o escribir el catálogo de otro hospital | `tenant` contrasta JWT y `X-Tenant-ID`; guard de #1154 | R: token A con cabecera B 403. F: guard 403 | Mitigada |
| L03 | Un clínico cambia rangos críticos | Lectura `laboratorio.catalogo.ver`; alta `laboratorio.catalogo.administrar`, que hoy solo tiene superadmin (admin_hospital sigue en `pending`) | S: los 8 roles de `config/rbac.php`. R: recepción 403 al consultar; médico 403 al administrar | Mitigada |
| L04 | Elegir desde el cuerpo el autor, la versión, el estado o el identificador | Autor del JWT en `local_user_refs`; versión, estado e id los decide el servidor | S y R: versión 99, `inactive` y otro autor se ignoran ([psql](./evidencia/salida-psql.txt): versión 1, activa, autor superadmin) | Mitigada |
| L05 | Una unidad que no es texto o un número infinito tumba la API (500) | El dominio trata una unidad que no es texto y un número no finito como ausentes: `RANGES_INCOHERENT` | S y R: unidad como arreglo o número y `1e400` dan 422 | **Corregida en esta entrega** |
| L06 | Textos enormes en nombre, muestra o unidad | Límites del dominio: nombre 160, muestra 40 y unidad 32 → `TEXT_TOO_LONG`, iguales al `maxlength` de la pantalla | S: 100 000 caracteres 422 y 160 con acentos 201. R: 161 da 422 | **Corregida en esta entrega** |
| L07 | Dos versiones vigentes de la misma prueba | Dominio + exclusión GiST de vigencias en `lab_tests` (#1244) → 409 | F y R: solapada 409 | Mitigada |
| L08 | Inyección en la traza (`X-Request-ID`) | Patrón `^[A-Za-z0-9._-]{1,64}$`; si no cumple, se genera `req-…` | S: HTML, 65 caracteres y CRLF reemplazados. R: `<script>` reemplazado | Mitigada |
| L09 | Filtrar SQL, credenciales o host con la base caída | `PersistenceFailure` → 503 `LAB_CATALOG_UNAVAILABLE` con `APP_DEBUG=false` | F: sin `secreto`. R: HOSPITAL en un puerto cerrado da 503 sin `SQLSTATE` ni host | Mitigada |
| L10 | Inyección SQL por la búsqueda | `PdoLabTestRepository` usa sentencias preparadas; `search[]` se trata como búsqueda vacía | S: `search[]` 200 y `date[]` 422 | Mitigada |
| L11 | Borrar o reescribir una versión usada por órdenes | Trigger de retención de `lab_tests` (#1244); la API no expone borrado ni edición | `LabTestCatalogIntegrityTest` | Mitigada |
| L12 | Agotar la API con solicitudes repetidas | Límite de tasa común de `/api/v1` | No hay control propio verificable | Pendiente: API Gateway |

## Riesgo residual

- **L03:** activar `laboratorio.catalogo.administrar` para admin_hospital depende de ASII-02. Mientras siga en `pending`, solo superadmin administra.
- **L12:** este módulo no declara un límite de tasa propio.
