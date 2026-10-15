# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code (Anthropic) con el modelo Claude Opus 5.5.

**Propósito:** apoyo para estructurar el micro-HIS en cuatro capas, escribir el código PHP vanilla con PDO, las pruebas sobre SQLite en memoria y con dobles, los diagramas C4/UML (Mermaid) y esta documentación, siguiendo el formato de la Semana 3 que ya usan otros módulos del equipo.

**Prompt relevante:** «Desarrolla la semana 3 de mis 3 módulos», aplicado a `ASII-11`, «registro de nota SOAP y asociación de diagnóstico clínico».

**Contenido aceptado o modificado:** las clases siguen la división SRP de la Semana 2. `SoapSectionsValidator` pasó a ser el objeto de valor `SoapSections`. El esquema es un subconjunto de la migración hospital `000400`. La simplificación de `care_assignments`, la tabla `icd10_codes` y la tabla mínima `local_access_audits` son supuestos documentados en [dependencias.md](./dependencias.md). Las pruebas se ejecutaron con PHP 8.3.33 y su salida se copió sin cambios.

**Validación humana:** realizada el 2026-10-14. Josué Fernando Hicho García revisó el código, ejecutó las pruebas, revisó la documentación y esta declaración, y la aprueba.
