# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code, modelo `claude-sonnet-5-5` (ejecutado sobre Windows). Las capturas se generaron con Chrome en modo headless.

**Propósito:** construir un prototipo navegable en HTML (desktop y móvil) del flujo «registro de nota SOAP y asociación de diagnóstico», con camino feliz y un error crítico (`NOTE_SIGNED`), mapa de navegación y capturas.

**Revisado y ajustado:**

- El prototipo implementa las pantallas de las Semanas 8 y 10 y aplica las correcciones B-01 a B-11 del backlog de la Semana 9; los mensajes y códigos son los del contrato de la Semana 5.
- Se ejecutó el camino completo en Chrome y se comprobaron ruta, título, `role="alert"` y foco en cada paso. No se probó con lector de pantalla ni con axe.
- Los datos son de ejemplo, viven en memoria y no hay llamadas al servidor; la firma y la corrección siguen marcadas como propuestas.
- En esta entrega no se modificó código del micro-HIS ni del HIS.

**Validación humana:** realizada el 2026-10-20. Josué Fernando Hicho García revisó el prototipo navegable, el mapa de navegación, las capturas del camino feliz y del error crítico, y la consistencia de roles, estados y accesibilidad, y aprueba el documento.

**Responsable:** Josué Fernando Hicho García es responsable de esta entrega.
