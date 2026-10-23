# Criterios de aceptación

Los casos describen el resultado esperado del límite de aplicación. Deben
convertirse en pruebas automatizadas cuando el servicio se integre.

| Caso | Condición | Resultado | Persistencia |
|---|---|---|---|
| AC-01 | Sin conflicto de alergia | Creación permitida | Guardar una prescripción |
| AC-02 | Conflicto `mild` | Creación con advertencia | Guardar una prescripción |
| AC-03 | Conflicto `moderate` | HTTP 422, `exception_allowed: true` | No guardar |
| AC-04 | Severidad `unknown` | HTTP 422, `exception_allowed: true` | No guardar |
| AC-05 | Conflicto `severe` | HTTP 422, sin excepción permitida | No guardar |
| AC-06 | Conflicto `life_threatening` | HTTP 422, sin excepción permitida | No guardar |
| AC-07 | Medicamento inexistente o inactivo | Error de dominio diferenciado | No guardar |
| AC-08 | Consulta de alergias no disponible | Error explícito de dependencia | No guardar |
| AC-09 | Auditoría no disponible | Error explícito; no respuesta de éxito fabricada | No afirmar una creación no confirmada |

## Invariantes

1. Toda respuesta 422 deja sin cambios el repositorio de prescripciones.
2. Solo `mild` convierte el conflicto en una advertencia y permite continuar.
3. `moderate` y `unknown` pueden indicar que la excepción está permitida, pero
   esta decisión no implementa ni expone una operación de excepción.
4. `severe` y `life_threatening` nunca permiten excepción.
5. Los criterios no redefinen ni modifican `isGrave()`.
