# Alergias clínicas y alerta visual en expediente — Semana 11: prototipo navegable

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-12 · **Entrega:** prototipo navegable de escritorio y móvil.

## Cómo abrirlo

Abra [`prototipo/index.html`](./prototipo/index.html) en cualquier navegador. Es un solo archivo, funciona sin conexión y no llama a ningún servidor. Para verlo como teléfono, use el modo de dispositivo del navegador (390 px).

- **Pantallas:** expediente con el banner de alergias y formulario «Registrar alergia».
- **Simulador:** la barra «Respuesta simulada del servidor» elige qué responde el servidor (Correcta y Servidor caído), con los mismos códigos del contrato `/api/v1`, para recorrer los caminos de error sin backend.

## Accesibilidad incluida

- Objetivos táctiles de 44 px como mínimo y foco visible (contorno ámbar de 3 px).
- Etiquetas en todos los campos y `fieldset` con `legend` en los grupos.
- Mensajes con texto, no solo color.
- `role="alert"` para lo crítico y `role="status"` para las confirmaciones.
- El foco va al título de cada pantalla y, ante un error, al campo afectado.

## Mejoras de las Semanas 9 y 10 aplicadas

- **H1 (contraste de «no disponible»):** el banner usa gris oscuro (`#303437` sobre `#e3e5e8`) y el texto «verificar con el paciente antes de prescribir».
- **Falla segura:** mientras carga o si el servidor cae, el banner **nunca** dice «Sin alergias» (lo verifica el recorrido).
- **H4 (`role="alert"` repetido):** el banner se repinta solo al abrir el expediente, al consultar de nuevo o al registrar.
- **Semana 10:** banner arriba del expediente y acciones a todo el ancho en el teléfono.

## Recorrido automatizado (Chrome headless)

El prototipo se recorrió con Chrome headless (puppeteer), que hace clic y escribe como un usuario y verifica el texto visible: **7 pasos OK y ningún error de JavaScript**. La salida completa está en el PR.

## Capturas

**Banner «no disponible» con el servidor caído**

![Banner «no disponible» con el servidor caído](./capturas/01-no-disponible-desktop.png)

**Banner crítico tras registrar penicilina**

![Banner crítico tras registrar penicilina](./capturas/02-banner-critico-desktop.png)

**Expediente en el teléfono (390 px)**

![Expediente en el teléfono (390 px)](./capturas/03-expediente-movil.png)

**Limitaciones:** el diseño visual final depende del layout base de Lis. La prueba con usuarios reales y la medición de contraste con herramienta quedan como siguiente paso. Uso de IA: [DECLARACION_IA.md](./DECLARACION_IA.md).
