# Diseño de caja negra — `PROPUESTO`

## Particiones de equivalencia

| Partición | Condición | Salida contractual esperada |
|---|---|---|
| Datos válidos, sin conflicto | Solicitud válida y consulta completada | Resultado de creación permitido por el contrato |
| Advertencia | Conflicto `mild` | Advertencia visible |
| Rechazo sin excepción disponible | `moderate` o `unknown` | HTTP 422, `exception_allowed: false`, no guardado |
| Rechazo sin excepción | `severe` o `life_threatening` | HTTP 422, sin excepción, no guardado |
| Datos inválidos | Campo requerido ausente o valor fuera del contrato | Error de validación; no guardar |
| Dependencia no disponible | Fallo en consulta de alergias o persistencia | Error explícito; no resultado exitoso |

## Límites

La lista de campos válidos, límites de dosis, rutas, códigos técnicos y
contenido exacto de las respuestas dependen del contrato aprobado. No se
inventan para esta especificación.
