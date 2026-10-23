# Principios de diseño móvil — `PROPUESTO`

- Priorizar una columna y una acción principal visible por estado.
- Mantener paciente y hospital activos identificables durante revisión y
  confirmación; no depender solo del color o de la posición de la pantalla.
- Dejar los errores junto al campo y el rechazo clínico en un bloque
  persistente que anuncie que no se guardó.
- Evitar que el teclado virtual o el desplazamiento oculten la acción requerida.
- Mantener el estado del formulario ante navegación accidental cuando sea
  seguro; no conservar datos tras cerrar sesión ni en un dispositivo ajeno.
- Tratar pérdida de conectividad como error explícito: no asumir que una
  solicitud fallida se guardó ni ofrecer reenvío automático sin idempotencia.
- No presentar capacidades offline ni sincronización que no estén diseñadas.

Los tamaños de control, breakpoints y compatibilidad concreta deben
establecerse mediante pruebas de la implementación; aquí no se declaran
mediciones.
