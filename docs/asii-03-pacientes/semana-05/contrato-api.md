# Contrato REST de Pacientes

La especificación normativa es [`openapi.yaml`](../../../../micro-his/pacientes/openapi.yaml) (OpenAPI 3.1). Las rutas son relativas al servidor HIS y todas comienzan con `/api/v1`.

| Método y ruta | Caso de uso | Permiso | Resultado |
|---|---|---|---|
| `POST /api/v1/patients` | Registro | `pacientes.registrar` | `201`, envelope `patient` |
| `GET /api/v1/patients/{code}` | Consulta exacta por código | `pacientes.ver` | `200`, `404` si no existe |
| `PATCH /api/v1/patients/{code}` | Actualización parcial | `pacientes.editar` | `200`, `404` si no existe |

## Identidad y datos

El alta recibe `global_id`, `code`, `given_name`, `family_name`, `birth_date` y `sex`; `contact` es nullable y `status` toma `active` si se omite. El contrato refleja los enums y la fecha no futura que valida el dominio y la tabla `local_patients`. La restricción única del código y `global_id` evita duplicados.

La respuesta pública incluye `patient_id`, `global_id`, `code` y los datos demográficos, pero omite marcas de tiempo y `mpi_link_status`, que no gestiona el modelo actual. `patient_id` es el nombre público del UUID local estable usado por las relaciones clínicas internas. `patient_id`, `code` y `global_id` son de solo lectura y no se aceptan en `PatientCreate` ni en `PatientUpdate`. `PATCH` solo admite `given_name`, `family_name`, `birth_date`, `sex`, `contact` y `status`; campos extra se rechazan. Los campos omitidos conservan su valor. Un cambio de `contact` a `null` lo limpia.

## Headers y autorización

- `X-Tenant-ID` es obligatorio; falta: `400 TENANT_REQUIRED`, tenant distinto al hospital: `403 TENANT_MISMATCH`.
- `X-User-Ref` es obligatorio y lo aporta la capa que ya autenticó al usuario; ausencia/identidad inválida: `401`.
- `X-Request-Id` es opcional. El servidor conserva el recibido o genera uno; todo error lo devuelve en `request_id`.
- Los permisos de cada operación están declarados como `x-required-permission` en OpenAPI. No se define JWT ni mecanismo de autenticación en este módulo.

## Errores y compatibilidad

Los errores usan `application/problem+json` y exactamente `title`, `status`, `code`, `field` (string o null) y `request_id`. El contrato contempla `400` solicitud inválida/tenant ausente, `401` identidad, `403` tenant o permiso, `404` paciente, `409` colisión de código o `global_id`, `422` regla de dominio y `500` persistencia. `code` es estable y `field` identifica el campo cuando aplica.

El controlador actual usa envelopes internos `body.error` y traduce duplicados a `422`; no produce HTTP. Un adaptador futuro debe convertir esos resultados al contrato —incluida la colisión como `409`— y generar el request ID, sin duplicar reglas de dominio ni SQL. La implementación de Semana 05 no añade ese adaptador.

El Laravel general del repositorio tiene middleware de tenant/autenticación con JWT y respuestas JSON basadas en `message`; no es una dependencia del bootstrap independiente de este Micro-HIS. Por el alcance de esta actividad, este contrato no incorpora JWT y define `X-User-Ref` y `Problem`. La integración con Laravel deberá acordar explícitamente el adaptador entre ambos esquemas; no se cambian esos middlewares ni se afirma que ya apliquen estas respuestas.
