# Flujo móvil — `PROPUESTO`

1. Abrir Prescripciones desde navegación autenticada, cuando exista ruta y
   permiso aprobado.
2. Confirmar el contexto del paciente antes de introducir datos.
3. Buscar y seleccionar el medicamento.
4. Completar los campos definidos por el contrato; avanzar de forma lineal y
   mostrar la validación al lado del campo.
5. Revisar el resultado de alergias:
   - `mild`: advertencia visible y legible antes de continuar.
   - `moderate` o `unknown`: rechazo 422, sin guardar y sin acción de excepción.
   - `severe` o `life_threatening`: rechazo 422, sin guardar y sin excepción.
6. Revisar el resumen antes de guardar o firmar según permisos y contrato.
7. En pérdida de red, mostrar el error técnico y mantener claro que el estado
   de persistencia debe confirmarse; no convertirlo en éxito.

La operación concreta de búsqueda, la recuperación ante interrupciones y el
contenido de los formularios deben validarse con API y pruebas móviles.
