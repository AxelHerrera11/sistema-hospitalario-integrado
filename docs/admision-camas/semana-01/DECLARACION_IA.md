# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code (Anthropic) con el modelo Claude Opus 5.5.

**Propósito:** apoyo para revisar el esquema HOSPITAL existente (`care_episodes`, `care_assignments`, `wards`, `beds`, `bed_stays`), organizar la estructura documental siguiendo el formato de las entregas de Semana 1 ya integradas en `develop`, redactar la narrativa y escribir las fuentes de los diagramas (PlantUML y Mermaid) y su versión SVG.

**Prompt relevante:** «Sigamos con la semana uno de cada módulo», aplicado a `ASII-07`, «Admisión hospitalaria y asignación de cama», con la indicación de que cada PR lleve «Semana N» en el título o en la rama para que Odoo lo asocie a la tarea.

**Contenido aceptado o modificado:** las reglas se basan en las restricciones reales de las migraciones `000200` y `000300` (exclusión GiST de estancias por cama, una estancia abierta por episodio, trigger de periodo del episodio, trigger de especialidad del médico). Las reglas que el esquema no cubre (una sola hospitalización abierta por paciente, sincronía entre `beds.status` y `bed_stays`, lista de espera) se marcan como propuestas y riesgos (R1–R6) para validarlas con el grupo y con los responsables de ASII-06 y ASII-08.

**Validación humana:** pendiente. Axel Eliú Herrera Sánchez debe revisar los tres diagramas, la narrativa y esta declaración antes de que el PR se integre a `develop`.
