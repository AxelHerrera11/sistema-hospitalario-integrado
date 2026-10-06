# Contrato común de la API

Formato de respuesta que **todas las áreas** siguen en `/api/v1`. Documenta el
estándar que ya usan las áreas 2 (pacientes) y 3 (médicos y citas), para que
las nuevas áreas no inventen uno propio (issue #7).

Los ejemplos son respuestas reales de la API.

## 1. Cabeceras

| Cabecera | Cuándo | Nota |
|---|---|---|
| `X-Tenant-ID` | Siempre | UUID del hospital. Debe coincidir con el hospital del token. |
| `Authorization: Bearer <token>` | Rutas privadas | Token JWT de `/auth/login`. |
| `Accept: application/json` | Siempre | Para que los errores lleguen en JSON. |

## 2. Listados (paginados)

`GET` de colección: se responde **directamente el paginator de Laravel**, sin
envoltorio propio.

```php
return response()->json(
    Modelo::query()->...->paginate($perPage)->withQueryString()
);
```

```json
{
  "current_page": 1,
  "data": [ { "id": 1, "code": "PAC-SANMAR-0001", "...": "..." } ],
  "first_page_url": "http://localhost/api/v1/patients?per_page=1&page=1",
  "from": 1,
  "last_page": 1,
  "last_page_url": "http://localhost/api/v1/patients?per_page=1&page=1",
  "links": [ { "url": null, "label": "&laquo; Anterior", "page": null, "active": false }, "..." ],
  "next_page_url": null,
  "path": "http://localhost/api/v1/patients",
  "per_page": 1,
  "prev_page_url": null,
  "to": 1,
  "total": 1
}
```

Parámetros de consulta comunes:

| Parámetro | Regla |
|---|---|
| `page` | Página (desde 1). |
| `per_page` | Por defecto 15, **máximo 50** (`min((int) $request->integer('per_page', 15), 50)`). |
| `q` | Búsqueda de texto con `whereSearch()`. Pacientes acepta también `search`. |
| `sort_by` / `sort_dir` | Opcional. Solo columnas de una **lista blanca** del controller; nunca input crudo. |
| Filtros propios | Por nombre de columna (`status`, `doctor_id`, `date_from`, …), documentados en el doc del área. |

## 3. Recurso individual

`GET /recurso/{id}`, `POST` y `PUT` devuelven el recurso **envuelto con su
nombre en singular** (`patient`, `appointment`, `doctor`, `specialty`, …), con
sus relaciones cargadas.

| Acción | Código |
|---|---|
| Crear | `201 Created` |
| Consultar / editar / cambiar estado | `200 OK` |

```json
{
  "patient": {
    "id": 3,
    "code": "PAC-SANMAR-0002",
    "first_name": "Ana",
    "last_name": "Ríos",
    "birth_date": "1992-03-08T00:00:00.000000Z",
    "gender": "F",
    "tenant_id": "ede8c819-899b-4fb5-afa9-86c8b200c43a",
    "created_at": "2026-10-06T02:32:40.000000Z",
    "updated_at": "2026-10-06T02:32:40.000000Z",
    "medical_record": null
  }
}
```

### Paciente anidado en respuestas de otras áreas

Cuando un recurso de otra área incluye al paciente (orden de laboratorio, cita,
admisión…), se exponen como mínimo `id`, `code`, `first_name`, `last_name`,
`birth_date` y `gender`. Cuando se integre `PatientSummaryResource` (área 2,
issue #13) se usará ese Resource para el objeto anidado; anidado dentro de otra
respuesta no agrega envoltorio `data`.

## 4. Errores

Todos los errores responden JSON con al menos `message`.

| Código | Cuándo | Cuerpo |
|---|---|---|
| `400` | Falta `X-Tenant-ID` o no es un UUID | `{"message": "La cabecera X-Tenant-ID es obligatoria."}` |
| `401` | Sin token, token inválido o vencido | `{"message": "Token inválido o expirado."}` |
| `403` | Sin el permiso de la ruta | `{"message": "No tiene permiso para realizar esta acción."}` |
| `403` | El token es de otro hospital que el de `X-Tenant-ID` | `{"message": "El tenant indicado no coincide con el usuario del token."}` |
| `404` | El id no existe, **pertenece a otro hospital** o la ruta no existe (no se revela cuál) | `{"message": "Recurso no encontrado."}` |
| `422` | Validación de datos **o regla de negocio** | `{"message": "...", "errors": {"campo": ["..."]}}` |

### 422 de validación

```json
{
  "message": "El campo apellido es obligatorio. (y 2 errores más)",
  "errors": {
    "last_name": ["El campo apellido es obligatorio."],
    "birth_date": ["El campo fecha de nacimiento es obligatorio."],
    "gender": ["El campo género es obligatorio."]
  }
}
```

Los mensajes salen en español (`lang/es/validation.php`). El nombre legible de
cada campo se toma de `attributes`: **cada área agrega los suyos en su bloque**
de ese archivo (p. ej. `'scheduled_at' => 'fecha y hora'`). Si falta, el
mensaje usa el nombre técnico ("El campo scheduled at es obligatorio.").

### 422 de regla de negocio

Las reglas de negocio (transición de estado inválida, médico y especialidad que
no coinciden, horario ocupado…) usan el **mismo formato**, asociando el error al
campo responsable. Así el frontend lo muestra igual que una validación:

```php
throw ValidationException::withMessages([
    'specialty_id' => ['La especialidad indicada no coincide con la especialidad del médico.'],
]);
```

Si el error no corresponde a un campo concreto, usar la clave `status` (o la
acción) como campo, p. ej. `'status' => ['Una cita completada no se puede cancelar.']`.

## 5. Reglas para las áreas

- No crear envoltorios propios (`{success, data}`, `{ok: true}`, …).
- No responder `200` con un error dentro del cuerpo.
- No devolver `403` para registros de otro hospital: es `404` (lo hace solo el
  global scope de `BelongsToTenant`).
- No armar mensajes 404 propios: `bootstrap/app.php` responde siempre
  `Recurso no encontrado.` para no revelar clases internas ni si el id existe.
- Mensajes de negocio escritos por el área, en español.
- Documentar en `docs/modulo-<area>.md` los endpoints, los filtros propios y los
  errores de negocio de cada endpoint.
