# Criterios de accesibilidad a verificar

Estado: criterios de diseño `PROPUESTO`; no son resultados de auditoría.

- Cada control tendrá etiqueta persistente y nombre accesible descriptivo.
- Los errores se asociarán programáticamente con el campo y se anunciarán
  cuando aparezcan; `aria-invalid` solo reflejará errores de ese campo.
- Mensajes de advertencia, rechazo y éxito usarán texto, no solo color o icono.
- El orden de tabulación seguirá el flujo visual; todos los controles serán
  operables por teclado y tendrán foco visible.
- El diálogo de confirmación conservará foco, permitirá cancelar y devolverá
  el foco al origen al cerrarse.
- La identidad del paciente permanecerá visible en revisión y firma para
  reducir errores de contexto.
- Se verificará reflujo en pantalla estrecha, ampliación y contraste con
  herramientas y revisión manual; no se declara conformidad WCAG.
