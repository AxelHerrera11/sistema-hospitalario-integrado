# Caso sintético de demostración

Usar identificadores ficticios y datos no atribuibles a una persona. Este caso
sirve para explicar respuestas del sistema; no es una recomendación clínica.

## Recorrido

1. Presentar una solicitud de prueba y confirmar que el catálogo reconoce el
   medicamento ficticio.
2. Ejecutar escenarios separados, sin combinar reglas:
   - Sin conflicto: creación permitida.
   - Conflicto `mild`: creación con advertencia visible.
   - Conflicto `moderate` o `unknown`: HTTP 422 y
     `exception_allowed: true`.
   - Conflicto `severe` o `life_threatening`: HTTP 422 sin excepción.
3. Verificar que cada escenario 422 deja el repositorio de prueba sin cambios.
4. Mostrar un fallo de dependencia como error explícito, no como lista vacía
   ni resultado exitoso.

## Condiciones de uso

- No usar historias clínicas, nombres, fechas o identificadores reales.
- No afirmar que los datos o respuestas son de producción.
- No ofrecer un flujo de excepción de alergia: continúa pendiente y sin
  endpoint.
- Sustituir este guion por resultados reales solo después de ejecutar y guardar
  la evidencia correspondiente.
