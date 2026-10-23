# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code, modelo `claude-sonnet-5-5` (ejecutado sobre Windows, PHP 8.3.33, Node 22, PostgreSQL 16 en un contenedor desechable).

**Propósito:** implementar la firma y la corrección de notas (#1083 y #1087), el catálogo CIE-10 provisional (#1089) y la pantalla Vue (#1090) con sus pruebas; consolidar la trazabilidad de las 18 semanas contra el estado real de los PR; ejecutar la demo y redactar el guion de defensa.

**Prompt relevante:** «hacer lo que se pueda sin depender de nadie» sobre el estado dejado por las Semanas 16 y 17, y «Semana 18: evaluación final y defensa».

**Revisado y ajustado:**

- La demo se ejecutó con PHP 8.3.33 y PostgreSQL 16.15, y su salida se copió sin cambios a `evidencia-demo.txt`. La trazabilidad refleja `gh pr list` y se comprobó que los 21 PR abiertos no tienen revisiones ni comentarios.
- Un fallo propio quedó registrado: las pruebas de corrección contra PostgreSQL esperaban el error de `clinical_notes_supersedes_unique`, pero la base respondió con `clinical_notes_record_version_unique`, que se evalúa antes; la prueba y el contrato se corrigieron en vez de forzar el resultado.
- Se decidió, sin que el enunciado lo pida, que la corrección reciba las cuatro secciones corregidas además del motivo, porque el módulo no tiene ruta para editar un borrador. Está documentado como decisión para revisar.
- El catálogo CIE-10 provisional son 20 códigos escritos de memoria, sin revisión clínica ni contraste con la fuente oficial: debe revisarse antes de usarse y viene apagado por defecto.
- La pantalla Vue se ejercitó en Chrome real con un script de `puppeteer-core` (20 comprobaciones contra el API y PostgreSQL, ver `evidencia-pantalla.txt`). Esa prueba reveló que los campos y botones salían sin borde y se corrigió; no se probó con otros navegadores ni con lector de pantalla.
- El cambio solicitado de la defensa («más de una nota por expediente») es una **hipótesis**: el enunciado oficial no lo especifica.
- No se implementó el `outbox_events` que prevé la Semana 1, ni se midió el rendimiento. Los pendientes se escriben tal como están.

**Validación humana:** realizada el 2026-10-23. Josué Fernando Hicho García revisó el código de #1083, #1087, #1089 y #1090, la evidencia, el guion y este documento, y aprueba el documento.

**Responsable:** Josué Fernando Hicho García es responsable de esta entrega.
