# Casos unitarios propuestos

Cada caso describe comportamiento esperado, no prueba ya ejecutada. Los
nombres de métodos se omiten hasta confirmar el diseño integrado.

| ID | Entrada relevante | Resultado esperado |
|---|---|---|
| U-01 | Sin conflicto de alergia y datos válidos | El caso de uso puede completar el resultado permitido |
| U-02 | Conflicto `mild` | Emite advertencia según contrato; no clasifica como rechazo severo |
| U-03 | Conflicto `moderate` | Rechazo 422 con `exception_allowed: true`; ninguna escritura |
| U-04 | Severidad `unknown` | Rechazo 422 con `exception_allowed: true`; ninguna escritura |
| U-05 | Conflicto `severe` | Rechazo 422 sin excepción; ninguna escritura |
| U-06 | Conflicto `life_threatening` | Rechazo 422 sin excepción; ninguna escritura |
| U-07 | Puerto de alergias falla | Error explícito; no tratarlo como ausencia de alergias ni guardar |
| U-08 | Puerto de persistencia falla | Error explícito; no devolver éxito |

El mapeo HTTP de errores técnicos depende del contrato aprobado. Estas pruebas
no proponen un endpoint de excepción ni modifican `isGrave()`.
