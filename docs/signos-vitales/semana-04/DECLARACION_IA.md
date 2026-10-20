# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code (Anthropic) con el modelo Claude Sonnet 5.5.

**Propósito:** apoyo para separar el Repository y sus adaptadores PDO e InMemory, organizar la entrada con MVC, escribir las pruebas de contrato y el MVC sobre SQLite en memoria, los diagramas Mermaid y esta documentación, siguiendo el formato de la Semana 4 que ya usan otros módulos del equipo.

**Prompt relevante:** «Desarrolla la siguiente semana: Semana 4: Arquitectura en capas y patrón repositorio», aplicado a `ASII-13`, «captura de signos vitales, evaluación de umbrales y generación de alerta».

**Contenido aceptado o modificado:** `RecordVitalSigns::execute()` recibe ahora un arreglo de mediciones y construye `VitalSigns` por dentro, para que el controlador no cree objetos de dominio. `VitalSignRepository` ganó `find()`. El análisis del repositorio compartido se apoya en la migración hospital `000401` y las migraciones `000200` y `000400`; el adaptador PostgreSQL y las decisiones SD-SV-12 a SD-SV-14 son propuestas sin validar con quien verifica la base de datos. Las pruebas se ejecutaron con PHP 8.3.33 y su salida se copió sin cambios.

**Validación humana:** realizada el 2026-10-20. Josué Fernando Hicho García revisó el código, ejecutó las pruebas, revisó la documentación y esta declaración, y la aprueba.
