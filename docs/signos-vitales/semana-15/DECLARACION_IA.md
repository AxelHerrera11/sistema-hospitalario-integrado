# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code (Anthropic) con el modelo Claude Sonnet 5.5.

**Propósito:** apoyo para diseñar y escribir las pruebas unitarias, de caja blanca y de caja negra, ejecutar las mutaciones que comprueban su sensibilidad, generar las capturas de la salida de las suites y redactar la matriz RF/RNF → prueba y el registro de defectos, siguiendo el formato de la Semana 15 que ya usan otros módulos del equipo.

**Prompt relevante:** «Semana 15: Pruebas unitarias, caja blanca y caja negra», aplicado a `ASII-13`, «captura de signos vitales, evaluación de umbrales y generación de alerta».

**Contenido aceptado o modificado:** las pruebas se ejecutaron con PHP 8.3.33 y su salida se copió sin cambios; las capturas son de esa salida renderizada en Chrome. Los defectos D-01 y D-02 son hallazgos nuevos de esta semana: D-01 (temperatura y presión sin regla de umbral) se confirmó enviando valores críticos, y D-02 (banda de glucosa con decimales) se deriva de comparar `≤ 53` con el `< 54` del Anexo B. Las bandas del Anexo B son ilustrativas y no están validadas clínicamente. No se corrigió ningún defecto nuevo; D-01 requiere la decisión del responsable clínico. Las mutaciones se aplicaron con un guion temporal que restauró cada archivo y no se incluye.

**Validación humana:** pendiente. Josué Fernando Hicho García debe revisar las pruebas, los defectos y esta declaración antes de aprobarla.
