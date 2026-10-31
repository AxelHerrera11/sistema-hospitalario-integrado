# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code (Anthropic) con el modelo Claude Sonnet 5.5.

**Propósito:** apoyo para evaluar los wireframes de la Semana 8 con las heurísticas de Nielsen y WCAG 2.2, completar el checklist, redactar los hallazgos y el backlog priorizado, y escribir el script de contraste `contraste.php`, siguiendo el formato de la Semana 9 que ya usan otros módulos del equipo.

**Prompt relevante:** «Semana 9: Evaluación del diseño, usabilidad y accesibilidad», aplicado a `ASII-13`, «captura de signos vitales, evaluación de umbrales y generación de alerta».

**Contenido aceptado o modificado:** los hallazgos salen de leer los wireframes y las reglas de la Semana 8, no de una pantalla implementada. Las razones de contraste se calcularon con la fórmula de luminancia relativa de WCAG 2.x y su salida se copió sin cambios (PHP 8.3.33); los colores son los de la paleta Tailwind, no una decisión del módulo. El comportamiento de `parseFloat("5,5")` es el de JavaScript. Los criterios de aceptación y los umbrales de las advertencias de unidad (glucosa < 20 en mg/dL o > 40 en mmol/L) son propuestas sin validar con usuarios clínicos. No se evaluó con usuarios reales ni con un lector de pantalla. No se modificó código.

**Validación humana:** realizada el 2026-10-20. Josué Fernando Hicho García revisó los hallazgos, el backlog y esta declaración, y la aprueba.
