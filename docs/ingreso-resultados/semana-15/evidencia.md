# Evidencia de pruebas

La evidencia debe adjuntar salida real del runner, cobertura de las ramas relevantes y casos de error. No se incluyen salidas simuladas ni datos clinicos reales.

## Criterios de aprobacion

- Cada regla tiene al menos un caso positivo y uno negativo.
- Las respuestas 401, 403, 409 y 422 se distinguen.
- Un fallo de persistencia deja el estado anterior intacto.
- La correccion conserva motivo, autor y version previa.
