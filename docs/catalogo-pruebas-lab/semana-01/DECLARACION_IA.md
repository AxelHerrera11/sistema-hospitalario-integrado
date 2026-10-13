# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code (Anthropic) con el modelo Claude Opus 5.5.

**Propósito:** apoyo para revisar el plan del esquema federado (slice H6 y tarea 3.6 de `openspec/changes/postgresql-federated-schema-rebuild/`), organizar la estructura documental siguiendo el formato de las entregas de Semana 1 ya integradas en `develop`, redactar la narrativa, proponer las columnas de `lab_tests` (Anexo A) y escribir las fuentes de los diagramas (PlantUML y Mermaid) y su versión SVG.

**Prompt relevante:** «Sigamos con la semana uno de cada módulo», aplicado a `ASII-17`, «Catálogo de pruebas de laboratorio», con la indicación de que cada PR lleve «Semana N» en el título o en la rama para que Odoo lo asocie a la tarea.

**Contenido aceptado o modificado:** como `lab_test` aún no tiene migración, el modelo de versiones y vigencia, las reglas de rangos y el Anexo A son **propuestas** que siguen las convenciones de las migraciones HOSPITAL existentes (exclusión GiST con `btree_gist`, `CHECK` de dominio, protección contra borrado). Los puntos abiertos (rangos por sexo y edad, paneles, relación con órdenes) se registran como riesgos R1–R6 para validarlos con el grupo y con los módulos de laboratorio.

**Validación humana:** pendiente. Axel Eliú Herrera Sánchez debe revisar los tres diagramas, la narrativa, el Anexo A y esta declaración antes de que el PR se integre a `develop`.
