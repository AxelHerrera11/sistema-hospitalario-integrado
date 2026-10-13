# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code (Anthropic) con el modelo Claude Opus 5.5.

**Propósito:** apoyo para revisar el esquema HOSPITAL existente (`allergies`, `care_assignments`, `local_patients`), organizar la estructura documental siguiendo el formato de las entregas de Semana 1 ya integradas en `develop`, redactar la narrativa y escribir las fuentes de los diagramas (PlantUML y Mermaid) y su versión SVG.

**Prompt relevante:** «Sigamos con la semana uno de cada módulo», aplicado a `ASII-12`, «Alergias clínicas y alerta visual en expediente», con la indicación de que cada PR lleve «Semana N» en el título o en la rama para que Odoo lo asocie a la tarea.

**Contenido aceptado o modificado:** las reglas se basan en las restricciones reales de la migración `2026_08_11_000401_create_allergy_and_vital_sign_tables.php` (índice único de alergia activa equivalente, coherencia entre estado y fecha de resolución, prohibición de borrado). Las decisiones que el esquema no cubre (falla segura del banner, auditoría de lectura que no bloquea la consulta, relación de cuidado de enfermería) se marcan como propuestas y riesgos (R1–R5) para validarlas con el grupo.

**Validación humana:** realizada el 2026-10-13. Axel Eliú Herrera Sánchez revisó los tres diagramas, la narrativa y esta declaración, y la aprueba.
