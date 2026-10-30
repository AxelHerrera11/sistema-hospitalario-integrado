# Respuestas y errores

Estado: contrato propuesto; validar los códigos y esquemas con la API que se
implemente.

| Operación | Respuesta | Semántica |
|---|---|---|
| Crear sin conflicto | 201 | Prescripción creada. |
| Crear con conflicto `mild` | 201 | Creada con advertencia visible. |
| Crear con conflicto `moderate` o `unknown` | 422 | `ALLERGY_CONFLICT`, `exception_allowed: true`; no guardar. |
| Crear con conflicto `severe` o `life_threatening` | 422 | `ALLERGY_CONFLICT`, `exception_allowed: false`; no guardar. |
| Crear con datos inválidos o medicamento inactivo | 422 | Error explícito; no guardar. |
| Dependencia clínica no disponible | 503 | No afirmar creación exitosa. |
| Listar o consultar detalle | 200 | Devolver los recursos autorizados del tenant. |
| Firmar | 200 | Firma completada. |
| Firmar en estado incompatible | 409 | Rechazo sin transición de estado. |
| Sin autenticación / permiso | 401 / 403 | Rechazo sin revelar datos de otro tenant. |
| Recurso inexistente o no visible | 404 | No disponible en el contexto autorizado. |

`exception_allowed` informa la política, no autoriza una operación de
excepción. No se documenta una ruta para registrar excepciones. La excepción
de alergia sigue pendiente.
