# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code, modelo `claude-sonnet-5-5` (ejecutado sobre Windows).

**Propósito:** adaptar a móvil (320–430 px) el flujo «registro de nota SOAP y asociación de diagnóstico clínico»: cinco pantallas, reglas de breakpoint, navegación, tablas y formularios, confirmaciones, recuperación ante conexión limitada y dos escenarios.

**Revisado y ajustado:**

- Las pantallas parten de los wireframes de la Semana 8 y aplican las correcciones B-01 a B-09 del backlog de la Semana 9; los mensajes usan los `code` del contrato de la Semana 5.
- La regla de que el texto clínico no se guarda en el dispositivo (RNF-SOAP-05) se mantuvo también para la conexión limitada; la recuperación completa depende de `clinical_note_drafts`, pendiente de Javier.
- La firma y la corrección siguen marcadas como propuestas.
- En esta entrega no se modificó código del micro-HIS ni del HIS.

**Validación humana:** realizada el 2026-10-20. Josué Fernando Hicho García revisó las cinco pantallas móviles, las reglas de breakpoint, la recuperación ante conexión limitada y los dos escenarios, y aprueba el documento.

**Responsable:** Josué Fernando Hicho García es responsable de esta entrega.
