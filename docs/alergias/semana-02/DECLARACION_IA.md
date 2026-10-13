# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code (Anthropic) con el modelo Claude Opus 5.5.

**Propósito:** apoyo para derivar los RF/RNF a partir de los casos de uso de la Semana 1, redactar los criterios de aceptación, proponer el diseño antes y después con OCP, escribir los diagramas de clases (Mermaid) y la demo PHP ejecutable, y organizar la evidencia según el formato de la Semana 2 ya integrada en `develop`.

**Prompt relevante:** «Sigue con la semana 2», aplicado a `ASII-12`, «Alergias clínicas y alerta visual en expediente», con OCP como principio asignado y la fuente obligatoria de MVP Cluster.

**Contenido aceptado o modificado:** la definición de OCP se cita de la fuente indicada por el curso. Las reglas de la alerta se basan en los valores reales de la migración `000401` (`severity`, `allergy_type`, `status`). La demo se ejecutó localmente con PHP 8.4 y su salida se copió sin cambios en `evidencia-verificable.md`. Los umbrales de rendimiento de los RNF son propuestas que deben confirmarse con el grupo.

**Validación humana:** pendiente. Axel Eliú Herrera Sánchez debe revisar los requerimientos, el diseño, ejecutar la demo y revisar esta declaración antes de que el PR se integre a `develop`.
