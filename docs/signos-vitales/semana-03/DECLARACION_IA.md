# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code (Anthropic) con el modelo Claude Opus 5.5.

**Propósito:** apoyo para estructurar el micro-HIS en cuatro capas, escribir el código PHP vanilla con PDO, las pruebas sobre SQLite en memoria, los diagramas C4/UML (Mermaid) y esta documentación, siguiendo el formato de la Semana 3 que ya usan otros módulos del equipo.

**Prompt relevante:** «Desarrolla la semana 3 de mis 3 módulos», aplicado a `ASII-13`, «captura de signos vitales, evaluación de umbrales y generación de alerta».

**Contenido aceptado o modificado:** las reglas siguen el contrato LSP de la Semana 2, con `name()` agregado para reportar los hallazgos. Los umbrales son los del perfil ilustrativo `adulto-2026.1`, pendiente de validación clínica. La plausibilidad viene de los `CHECK` de la migración `000401`. El cálculo de edad, la cola de notificación y la tabla mínima `local_access_audits` son supuestos documentados en [dependencias.md](./dependencias.md). Las pruebas se ejecutaron con PHP 8.3.33 y su salida se copió sin cambios.

**Validación humana:** pendiente. El estudiante debe revisar el código, ejecutar las pruebas y aprobar esta declaración antes de integrar.
