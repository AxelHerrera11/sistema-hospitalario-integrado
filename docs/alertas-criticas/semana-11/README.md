# Alertas críticas y notificaciones internas — Semana 11: prototipo navegable

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-21 · **Entrega:** prototipo navegable de escritorio y móvil.

## Cómo abrirlo

Abra [`prototipo/index.html`](./prototipo/index.html) en cualquier navegador. Es un solo archivo, funciona sin conexión y no llama a ningún servidor. Para verlo como teléfono, use el modo de dispositivo del navegador (390 px).

- **Pantallas:** bandeja de alertas pendientes y diálogo de lectura de verificación.
- **Simulador:** la barra «Respuesta simulada del servidor» elige qué responde el servidor (Correcta, Sin pendientes, Servidor caído, 403 sin relación y 409 ya acusada), con los mismos códigos del contrato `/api/v1`, para recorrer los caminos de error sin backend.

## Accesibilidad incluida

- Objetivos táctiles de 44 px como mínimo y foco visible (contorno ámbar de 3 px).
- Etiquetas en todos los campos y `fieldset` con `legend` en los grupos.
- Mensajes con texto, no solo color.
- `role="alert"` para lo crítico y `role="status"` para las confirmaciones.
- El foco va al título de cada pantalla y, ante un error, al campo afectado.
