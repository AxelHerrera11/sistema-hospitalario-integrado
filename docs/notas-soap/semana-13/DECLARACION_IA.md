# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code, modelo `claude-sonnet-5-5` (ejecutado sobre Windows). Para comprobar los puntos de atención se usaron PHP 8.3.33 y el módulo `node:sqlite`.

**Propósito:** preparar la revisión técnica formal de Notas SOAP y diagnósticos: plan, checklist de 15 ítems, roles, criterios de entrada y salida, formato de hallazgos y puntos de atención del autor.

**Revisado y ajustado:**

- El código revisado es el de la rama de la Semana 5 (commit `93b7b47`); se leyeron `ApiKernel`, el controlador, los casos de uso, el dominio, `PdoClinicalNoteStore`, el esquema de pruebas y la migración `000400`.
- Las dos suites de pruebas (`run.php` y `api.php`) se ejecutaron y pasan. El OpenAPI y la migración en PostgreSQL **no** se ejecutaron en esta entrega.
- P-1 y P-2 se reprodujeron en el esquema SQLite de pruebas; P-3 a P-6 se comprobaron leyendo el código y la migración, y así figura en el documento.
- Los revisores y el moderador están «por confirmar»: no se asignó a nadie sin su aceptación.
- En esta entrega no se modificó código del micro-HIS ni del HIS.

**Validación humana:** pendiente del visto bueno de Josué Fernando Hicho García.

**Responsable:** Josué Fernando Hicho García es responsable de esta entrega.
