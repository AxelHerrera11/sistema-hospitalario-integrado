# Semana 8 — User flow y wireframes

Estado: `PROPUESTO`. Estos flujos y pantallas describen una experiencia a
validar; no representan una UI o un endpoint existente.

## Entregables

- [Flujos por rol](flujos.md)
- [Wireframes de baja fidelidad](wireframes.md)
- [Reglas de interacción](reglas-interaccion.md)
- [Criterios de accesibilidad a verificar](accesibilidad.md)

## Límites de producto

- `mild`: advertir y permitir que el usuario revise la advertencia antes de
  continuar según el contrato aprobado.
- `moderate` y `unknown`: mostrar rechazo 422 con `exception_allowed: true`;
  no guardar.
- `severe` y `life_threatening`: mostrar rechazo 422 sin excepción; no guardar.
- No incluir una pantalla, botón o endpoint de excepción de alergia: esa
  capacidad sigue pendiente.
- No definir unidad, formato ni límites de dosis desde estos wireframes.

Las acciones permitidas a cada rol y los campos definitivos deben validarse
con RBAC y el contrato API antes de implementar.
