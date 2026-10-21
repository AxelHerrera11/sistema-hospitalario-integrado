# Formato común de respuestas y errores — acuerdo transversal

- **Versión:** 1.0 (propuesta)
- **Fecha:** 20/10/2026
- **Módulo:** ASII-24 — Contratos API: OpenAPI/Postman y documentación técnica
- **Redacta:** Lis Ivette Rosales Colindrez (`Lis671`)
- **Revisan:** Yamilet (integración), Javier (base de datos), María (RBAC)
- **Estado:** propuesto para aprobación del grupo; se aplica a los módulos nuevos y se adopta en los existentes cuando se toquen.
- **Base:** `/api/v1` (`routes/api.php` y los providers de cada módulo).

## 1. Reglas generales

1. Toda respuesta es JSON UTF-8 (`Content-Type: application/json`), salvo las descargas (`text/csv`).
2. No se devuelven trazas, SQL, nombres de tablas ni rutas internas.
3. Todo error 4xx/5xx usa el mismo sobre (`§3`) y un `code` estable.
4. `trace_id` correlaciona la respuesta con los logs; no sustituye al `code`.
5. Los códigos no cambian entre versiones: si cambia la semántica, se publica un `code` nuevo.
6. Un recurso de otro tenant responde igual que uno inexistente (`404`), sin revelar su existencia.
7. `401` y `403` no explican qué permiso falta ni qué rol se requiere.

## 2. Respuesta exitosa

Objeto único:

```json
{ "data": { "appointment_id": "…", "status": "scheduled" } }
```

Colección paginada:

```json
{
  "data": [ { "appointment_id": "…" } ],
  "meta": { "current_page": 1, "per_page": 25, "total": 120, "last_page": 5 }
}
```

- `201 Created` devuelve el recurso creado en `data`.
- `200 OK` para consultas y transiciones.
- No se usa `{ "success": true }`; la presencia de `data` indica éxito.
- Las descargas (`/export`) no usan sobre: devuelven el archivo y sus cabeceras.

## 3. Respuesta de error

```json
{
  "message": "La cita se solapa con otra cita activa del mismo médico.",
  "code": "appointments_overlap",
  "trace_id": "trace-4f2a9c1b7e30"
}
```

Validación (`422`) agrega `errors`:

```json
{
  "message": "Los datos enviados no son válidos.",
  "code": "validation_failed",
  "errors": { "ends_at": ["El fin debe ser posterior al inicio."] },
  "trace_id": "trace-4f2a9c1b7e30"
}
```

- `message`: texto en español para el usuario final; no expone detalles internos.
- `code`: `snake_case`, estable y documentado en el contrato del módulo.
- `trace_id`: se reutiliza `X-Request-Id` si cumple `^[A-Za-z0-9._-]{8,64}$`; si no, se genera `trace-` + 12 hex.
- `errors`: solo en `422`; mapa `campo → lista de mensajes`.
