# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code (Anthropic) con el modelo Claude Sonnet 5.5.

**Propósito:** apoyo para corregir los defectos D-01 (reglas de temperatura y presión que faltaban) y D-02 (límite crítico de la glucosa con decimales) registrados por las pruebas de la Semana 15, escribir sus pruebas y mutaciones, regenerar las capturas y redactar la documentación.

**Prompt relevante:** «Utilicemos el 2 siempre teniendo el cuidado de que no soy un médico certificado», aplicado a `ASII-13`, «captura de signos vitales, evaluación de umbrales y generación de alerta».

**Contenido aceptado o modificado:** las bandas son las del Anexo B de la Semana 1, copiadas sin cambios; **no están validadas clínicamente**, el autor no es médico certificado y el perfil no es apto para uso asistencial. El contraste con NEWS2 lo hizo la IA de memoria, sin poder consultar la fuente, y debe confirmarse contra la tabla oficial; las bandas de sistólica 180–219 y de diastólica no vienen de NEWS2. Las pruebas y las mutaciones se ejecutaron con PHP 8.3.33 y su salida se copió sin cambios. Los defectos D-03 a D-06 no se corrigen.

**Validación humana:** realizada el 2026-10-20. Josué Fernando Hicho García revisó el código, las pruebas, la documentación y esta declaración, y la aprueba, con la limitación de que el perfil de umbrales no tiene revisión clínica profesional.
