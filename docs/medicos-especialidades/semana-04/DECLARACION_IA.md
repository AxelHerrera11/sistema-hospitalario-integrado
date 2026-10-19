# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code (Anthropic) con el modelo Claude Opus 5.5.

**Propósito:** apoyo para reorganizar la entrada del micro-HIS con MVC, definir `DoctorRepository` con sus adaptadores PDO e InMemory, ampliar las pruebas para que corran contra los dos adaptadores, y redactar esta documentación y el análisis del repositorio compartido.

**Prompt relevante:** «Confirmado, adelante con la semana 4», sobre el módulo `ASII-04`, partiendo de la rama de la Semana 3 `adapters-tests` integrada sobre `origin/develop`.

**Contenido aceptado o modificado:** `DoctorRepository` agrupa los puertos de ISP de la Semana 2 sin cambiar lo que recibe cada caso de uso. El análisis del repositorio compartido se basa en las migraciones hospital `000100` a `000401` de `develop`, citadas por archivo y línea. Las pruebas se ejecutaron con PHP 8.3.33 y su salida se copió sin cambios, resumida solo donde el README lo indica.

**Validación humana:** pendiente. Josué Fernando Hicho García debe revisar el código, ejecutar las pruebas y aprobar esta declaración antes de abrir el PR.
