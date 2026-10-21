# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code (Anthropic) con el modelo Claude Opus 5.5.

**Propósito:** apoyo para derivar los RF/RNF de los casos de uso de la Semana 1, redactar los criterios de aceptación, proponer el diseño antes y después con ISP, escribir los diagramas de clases (Mermaid) y la demo PHP, y seguir el formato de la Semana 2 ya integrado en `develop`.

**Prompt relevante:** «Desarrolla la semana 2 de mis módulos», aplicado a `ASII-04`, «registro de médico, asociación de especialidad y consulta de disponibilidad», con ISP como principio asignado.

**Contenido aceptado o modificado:** la definición de ISP se cita de la fuente del curso. Los códigos de error, las reglas de especialidad y las interfaces `DoctorScheduleProvider` y `AppointmentReader` vienen de la Semana 1 y de la migración `000100`. La demo se ejecutó con PHP 8.3 y su salida se copió sin cambios. El umbral de rendimiento es una propuesta que debe confirmarse con el grupo.

**Validación humana:** realizada el 2026-10-13. Josué Fernando Hicho García revisó los requerimientos, el diseño, la documentación y esta declaración, y la aprueba.
