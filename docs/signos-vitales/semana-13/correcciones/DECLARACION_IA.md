# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code (Anthropic) con el modelo Claude Sonnet 5.5.

**Propósito:** apoyo para corregir dos hallazgos de la revisión técnica de la Semana 13 (P-2: el fallo del canal de notificación tras el `COMMIT`, y P-3: la fecha de medición futura), escribir sus pruebas y las mutaciones que comprueban que las detectan, y actualizar el contrato y el OpenAPI.

**Prompt relevante:** «Arregla lo que mencionas de las correcciones», aplicado a `ASII-13`, «captura de signos vitales, evaluación de umbrales y generación de alerta».

**Contenido aceptado o modificado:** el campo `notification` y la tolerancia de 5 minutos del reloj son decisiones de diseño sin validar con un responsable clínico. La corrección de P-2 deja el reintento de la notificación como pendiente (la cola con reintentos del RF-SV-07 no existe todavía). La fecha antigua (por ejemplo 1900) no se corrige porque el requisito solo prohíbe la futura. Las pruebas y las mutaciones se ejecutaron con PHP 8.3.33 y su salida se copió sin cambios.

**Validación humana:** realizada el 2026-10-20. Josué Fernando Hicho García revisó el código, las pruebas, la documentación y esta declaración, y la aprueba.
