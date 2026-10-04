# ASII-02 - Pacientes y expediente base

Rama de trabajo: `feature/area-2-pacientes-expediente`
Base del PR: `develop`
Estado: **ETAPA 2 (backend Patient) implementada**. El expediente medico (`medical_records`) y la UI quedan para etapas posteriores.

## 1. Alcance

Este documento describe el contrato de la API de pacientes del Area 2. La etapa
actual cubre el CRUD de datos demograficos y la consulta del resumen de expediente
para saber si el paciente ya tiene expediente abierto.

Quedan fuera de esta etapa y se anadiran despues:

- Alta y edicion de `medical_records` (expediente clinico).
- Notas SOAP, diagnosticos y signos vitales (Area 5).
- Borrado logico de pacientes via API (el modelo ya tiene `SoftDeletes`, pero no se
  expone endpoint porque el flujo de baja pertenece al area de admisiones).
- Interfaz Vue / store Pinia.

## 2. Contrato API

Todas las rutas requieren la cabecera `X-Tenant-ID`. Las rutas privadas tambien
requieren `Authorization: Bearer <token>`.

| Metodo | Ruta | Permiso | Proposito |
|---|---|---|---|
| GET | `/api/v1/patients` | `pacientes.ver` | Listar y buscar pacientes. |
| POST | `/api/v1/patients` | `pacientes.crear` | Crear paciente. |
| GET | `/api/v1/patients/{patient}` | `pacientes.ver` | Detalle con resumen de expediente. |
| PUT | `/api/v1/patients/{patient}` | `pacientes.editar` | Actualizar paciente. |

No se implementa `DELETE`: el paciente se archiva logicamente desde el flujo de
admisiones del Area 4.

## 3. Campos del paciente

`code` es obligatorio en la base de datos, pero opcional en el payload: si no se
envia, la API lo genera. El resto de obligatorios y maximos vienen de la migracion
`create_admission_catalogs`.

| Campo | Tipo | Regla | Columna |
|---|---|---|---|
| `tenant_id` | uuid | **No se acepta del cliente.** Se toma de `X-Tenant-ID`. | `foreignUuid` |
| `code` | string | opcional, max 20, unico por hospital | `string(20)` |
| `first_name` | string | requerido, max 100 | `string(100)` |
| `last_name` | string | requerido, max 100 | `string(100)` |
| `birth_date` | date | requerido, `1900-01-01` a hoy | `date` |
| `gender` | enum | requerido: `M`, `F`, `otro` | `enum` |
| `dpi` | string | opcional, max 20, 13 digitos (`/^\d{13}$/`) | `string(20)` |
| `nit` | string | opcional, max 20 | `string(20)` |
| `phone` | string | opcional, max 20 | `string(20)` |
| `email` | string | opcional, email valido, max 150 | `string(150)` |
| `address` | string | opcional, max 200 | `string(200)` |
| `insurance_company` | string | opcional, max 100 | `string(100)` |
| `insurance_policy` | string | opcional, max 50 | `string(50)` |
| `emergency_contact_name` | string | opcional, max 100 | `string(100)` |
| `emergency_contact_phone` | string | opcional, max 20 | `string(20)` |
| `blood_type` | enum | opcional: `A+ A- B+ B- O+ O- AB+ AB-` | `enum` |
| `notes` | text | opcional, max 2000 (limite de negocio, la columna es `text`) | `text` |

Ejemplo de payload de alta:

```json
{
  "first_name": "Ana",
  "last_name": "Rios",
  "birth_date": "1992-03-08",
  "gender": "F",
  "dpi": "9999999999999",
  "phone": "5555-1111",
  "email": "ana@demo.local",
  "blood_type": "O+"
}
```

`PUT` es reemplazo completo: `first_name`, `last_name`, `birth_date` y `gender` son
obligatorios tambien al editar. Los campos opcionales que no se envien conservan su
valor actual (no se borran por omision). Enviar `code` es opcional; si se omite, el
codigo no cambia.

## 4. Respuestas

`GET /api/v1/patients` devuelve el **paginador nativo de Laravel**, igual que
`GET /api/v1/doctors`. IMPORTANTE: el paginador nativo **no anida la metadata bajo
`meta`**; la expone en la raiz. El store de Pinia debe leer `data` para los items y
`total` / `per_page` / `last_page` para la paginacion.

```json
{
  "current_page": 1,
  "data": [ { "id": 1, "code": "PAC-SANMAR-0001", "first_name": "Ana", "medical_record": null } ],
  "first_page_url": "http://localhost/api/v1/patients?page=1",
  "from": 1,
  "last_page": 2,
  "last_page_url": "http://localhost/api/v1/patients?page=2",
  "links": { "first": null, "last": "...", "prev": null, "next": "..." },
  "next_page_url": "http://localhost/api/v1/patients?page=2",
  "path": "http://localhost/api/v1/patients",
  "per_page": 15,
  "prev_page_url": null,
  "to": 15,
  "total": 20
}
```

`GET`, `POST` y `PUT` de un paciente devuelven el modelo envuelto en `patient`:

```json
{ "patient": { "id": 1, "code": "PAC-SANMAR-0001", "medical_record": null } }
```

`medical_record` es un **resumen** (`id`, `tenant_id`, `patient_id`,
`record_number`, `opened_at`) o `null` si el paciente aun no tiene expediente. No se
cargan los antecedentes ni los signos vitales: `Patient::vitalSigns()` es un
`hasManyThrough` con `orderByDesc`, y contar sobre esa relacion falla en PostgreSQL.

Codigos de estado: `200` en listado, detalle y actualizacion; `201` en alta; `401`
sin token; `403` sin permiso o con un token de otro hospital; `404` si el paciente
no existe, esta borrado logicamente o pertenece a otro hospital; `422` con
`errors` por campo en fallos de validacion.

## 5. Parametros del listado

| Parametro | Por defecto | Valores | Descripcion |
|---|---|---|---|
| `search` | - | texto | Busca en `first_name`, `last_name`, `dpi`, `code` y `phone`. |
| `q` | - | texto | Alias de `search` (mismo criterio que medicos y citas). |
| `per_page` | 15 | 1 a 50 | Se limita a 50 aunque se pida mas. |
| `sort_by` | `last_name` | `last_name`, `first_name`, `code`, `birth_date`, `created_at` | Lista blanca; un valor no permitido cae al valor por defecto. |
| `sort_dir` | `asc` | `asc`, `desc` | Cualquier otro valor se trata como `asc`. |
| `page` | 1 | entero | Pagina solicitada. |

`sort_by` y `sort_dir` no se interpolan en el SQL: solo se acepta un valor de la
lista blanca. Se anade `id` como segundo criterio de orden para que dos apellidos
iguales no se repitan ni se salten al paginar.

La busqueda usa el macro `whereSearch()` de `AppServiceProvider`: en PostgreSQL
ignora mayusculas y acentos (`unaccent` + `ILIKE`), en SQLite ignora mayusculas. Por
eso `search=lopez` encuentra `López` solo en PostgreSQL; en la suite SQLite esa
prueba se omite. Los comodines `%`, `_` y `\` del texto buscado se escapan, de modo
que no actuan como comodines.

## 6. Generacion del codigo

`app/Services/PatientCodeGenerator.php` genera `PAC-{PREFIJO}-{0001}`:

- El prefijo son los primeros 6 caracteres alfanumericos del `slug` del hospital en
  mayusculas (`san-marcos` -> `SANMAR`), igual que el seeder de demo, para que los
  codigos de dos hospitales nunca se parezcan. El largo total es 15 caracteres, dentro
  del `string(20)`.
- El correlativo es el maximo ya usado con ese prefijo, mas uno.
- La busqueda del maximo usa `withTrashed()`: la restriccion `uq_patients_tenant_code`
  es dura y un paciente borrado logicamente sigue ocupando su codigo. Ademas hay un
  bucle `do/while` que descarta codigos ocupados, para no chocar con codigos
  manuales ya existentes.
- Si el cliente envia `code`, se respeta el suyo, validado con `Rule::unique` acotado
  al hospital de la peticion.
- No se filtra `tenant_id` a mano en el servicio: el global scope de
  `BelongsToTenant` ya acota la consulta al hospital de la peticion.

## 7. Aislamiento por hospital (tenant)

Defensa en cuatro capas:

1. **Global scope.** `Patient` usa el trait `BelongsToTenant`; toda consulta anade
   `where tenant_id = <X-Tenant-ID>`. No se escribe `tenant_id` a mano en el
   controlador ni en el generador de codigos.
2. **`tenant_id` no es un campo aceptable.** No esta en las reglas de validacion, y
   `validate()` devuelve solo las claves declaradas. Un `tenant_id` enviado en el alta
   se descarta y se reemplaza por el del contexto; en la edicion se ignora. Hay dos
   pruebas que lo verifican.
3. **Sin binding implicito de modelo.** `SubstituteBindings` pertenece al grupo
   `api` y se ejecuta **antes** del middleware `tenant`, asi que al resolver un
   modelo por ruta todavia no existe `currentTenant` y el global scope no se aplicaria:
   un paciente de otro hospital se colaria con un 200. Por eso `show` y `update`
   reciben el id y lo resuelven con `Patient::query()->findOrFail($id)`, ya con el
   tenant resuelto. Un paciente de otro hospital no resuelve y responde 404, sin
   confirmar que existe. **Este mismo problema latente existe hoy en `{doctor}` y
   `{appointment}` del Area 3**, que si usan binding implicito (ver seccion 10).
4. **Token contra cabecera.** El middleware `auth.jwt` valida que el token pertenezca
   al hospital de `X-Tenant-ID`; si no, responde 403.

## 8. Permisos

| Permiso | Uso | Roles que lo tienen |
|---|---|---|
| `pacientes.ver` | listado y detalle | Admin, Medico, Enfermera, Recepcionista, Laboratorio |
| `pacientes.crear` | alta | Admin, Recepcionista |
| `pacientes.editar` | edicion | Admin, Recepcionista |

Definidos en `database/seeders/RoleSeeder.php`. No se modifico ese archivo. El
expediente usara `expediente.editar` (no existe `expediente.crear`); la apertura del
expediente se disparara junto con la creacion del paciente en una etapa posterior.

## 9. Pruebas

`tests/Feature/PatientTest.php`, 45 pruebas contra la API real:

- Listado: contenido del hospital, orden por defecto (`last_name` asc), orden
  descendente, columna no permitida, paginacion, tope de `per_page`.
- Busqueda: sin distincion de mayusculas, alias `q`, busqueda por apellido, `dpi`,
  `code` y telefono, acentos en PostgreSQL (se omite en SQLite), busqueda vacia.
- Detalle: resumen de expediente, `null` sin expediente, expediente de otro hospital
  oculto, 404 por id inexistente y por paciente borrado logicamente.
- Alta: recepcionista crea, generacion de codigo correlativo, codigo distinto por
  paciente, no reutiliza codigo de borrado logico, reanuda secuencia tras codigo
  manual, acepta codigo explicito, rechaza duplicado en el mismo hospital y lo
  permite en otro.
- Validaciones: campos obligatorios, fecha futura, `gender` y `blood_type` fuera del
  enum, `dpi` y `email` malformados, valores mas largos que la columna.
- Edicion: recepcionista actualiza, conserva el codigo si no se envia, acepta su
  propio codigo, rechaza el de otro paciente del mismo hospital, exige los
  obligatorios, 404 por id inexistente.
- Autorizacion: 401 sin token, 403 sin rol, Enfermera lee pero no escribe, Admin
  escribe, token de otro hospital rechazado.
- Aislamiento: listado y busqueda no filtran otros hospitales, detalle y edicion dan
  404 para un paciente ajeno, `tenant_id` del cliente ignorado en alta y edicion.

Comandos:

```powershell
php artisan test --filter=PatientTest
php artisan test
php artisan route:list --path=api/v1/patients
```

Resultado: 44 pruebas pasan, 1 se omite (busqueda sin acentos, solo aplica en
PostgreSQL). Suite completa: 65 pasan, 1 se omite.

## 10. Pendientes y riesgos

- **Riesgo abierto en el Area 3.** `{doctor}` y `{appointment}` usan binding
  implicito con `SubstituteBindings` antes de `tenant`, el mismo defecto que se
  corrigio aqui. No se toco el Area 3; conviene aplicar la misma correccion
  (`findOrFail` dentro del controlador) en una tarea aparte.
- **Auditoria.** No se creo `AuditLogger` ni se escribieron eventos de auditoria en
  altas y ediciones. Queda pendiente enganchar el servicio de auditoria del Area 1
  (`audit_logs`) en `store` y `update`.
- **Apertura de expediente.** Hoy `POST /patients` no crea `medical_records`; la
  respuesta incluye `medical_record: null` para que la UI sepa que falta abrirlo.
  Definir si se abre automaticamente al dar de alta al paciente.
- **Validar en PostgreSQL.** La prueba de acentos se omite en SQLite. Correr
  `php artisan test --configuration=phpunit.pgsql.xml` antes del PR.
- **`notes` con limite de 2000 caracteres.** La columna es `text` sin tope; el limite
  viene de la regla de validacion. Si el negocio necesita mas, hay que subir la regla.
- **Sin endpoint de baja.** El modelo tiene `SoftDeletes` pero no hay ruta `DELETE`;
  falta definir que rol archiva un paciente y que pasa con su expediente.
- **Sin UI.** No hay pantallas Vue ni store Pinia de pacientes;Area 3 ya tiene
  selectores de pacientes pendientes de estos endpoints.


## Resumen de paciente para otras áreas (`PatientSummaryResource`)

Cuando un paciente aparece anidado en respuestas de otras áreas
(laboratorio, admisiones, notas SOAP, prescripciones, alertas), se debe
usar `App\Http\Resources\PatientSummaryResource` para que todas devuelvan
la misma forma.

### Campos

| Campo        | Tipo    | Ejemplo        |
|--------------|---------|----------------|
| `id`         | integer | `7`            |
| `code`       | string  | `"PAC-0007"`   |
| `first_name` | string  | `"Ana"`        |
| `last_name`  | string  | `"Pérez"`      |
| `birth_date` | string (`YYYY-MM-DD`) | `"1990-05-14"` |
| `gender`     | string (`M`, `F`, `otro`) | `"F"` |

### Datos excluidos a propósito

No incluye DPI, NIT, teléfono, email, dirección, seguro, contacto de
emergencia, tipo de sangre ni notas. Son datos personales sensibles y el
resumen viaja en respuestas de áreas cuyos roles no necesariamente tienen
el permiso `pacientes.ver`. Para el detalle completo usar
`GET /api/v1/patients/{patient}` (requiere `pacientes.ver`).

### Uso desde otra área

    'patient' => new PatientSummaryResource($this->whenLoaded('patient')),

`whenLoaded` incluye el paciente solo si el controlador cargó la relación
con `->load('patient')` o `->with('patient')`, evitando consultas extra.