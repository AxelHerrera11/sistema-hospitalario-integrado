# Semana 9 — Usabilidad y accesibilidad: hallazgos verificables

## Método y alcance

Se hizo inspección estática del árbol `origin/develop` y de los wireframes
propuestos en la semana 8. No hay una página de Prescripciones integrada que
permita afirmar una prueba de uso de esa pantalla; tampoco se realizaron
sesiones con usuarios, pruebas de contraste automatizadas ni auditoría
completa de conformidad WCAG.

## Hallazgos reales

- [Hallazgos con evidencia](hallazgos.md): ausencia de ruta de Prescripciones
  en el frontend actual y una asociación incorrecta entre error de búsqueda
  y estado inválido en el formulario de referencia de Medicamentos.
- [Lista de verificación](verificacion.md): comprobaciones que sí pueden
  observarse en el código y las que siguen pendientes.
- [Mejoras priorizadas](mejoras.md): acciones propuestas, sin declarar que
  hayan sido implementadas.

Los hallazgos de Medicamentos se identifican expresamente como referencia del
patrón de UI existente; no se atribuyen a una pantalla de Prescripciones que
aún no existe. No se reportan porcentajes, puntuaciones ni pruebas de usuarios.
