# Matriz decisión → RF/RNF → evidencia (semanas 8 a 12)

Complementa la [matriz de la Semana 6](../semana-06/matriz-decisiones.md) con las decisiones de experiencia de usuario.

| Decisión | RF/RNF | Evidencia | Estado |
|---|---|---|---|
| Confirmación obligatoria con resumen antes de enviar | RF-TA-01, RNF-TA-02 | `recorrido-ui.mjs`: confirmación con foco | Probado |
| Bloquear el botón y anunciar «Procesando…» mientras se envía | RNF-TA-04 (una sola operación por intento) | `movil.mjs` E2: sin doble envío | Probado |
| Mostrar acciones solo si la estancia sigue vigente | RF-TA-04 | Recorrido: origen no vigente sin acciones | Probado |
| Traducir cada motivo de rechazo a lenguaje claro | RF-TA-08 | `ui-mensajes.test.mjs` y la captura del rechazo | Probado |
| Reintentar solo ante 503 o sin red; ante incierto, solo consultar | RNF-TA-06 | `ui-mensajes.test.mjs` y `movil.mjs` E2 (el traslado sí se había confirmado) | Probado |
| Tratar un tiempo agotado en un POST como incierto | RNF-TA-06 | `movil.mjs` E2 | Probado |
| Validar actor y estancia antes de consultar (H1) | RF-TA-01 | Sonda S2: pasó a cumplir | Probado |
| Foco y anuncio tras cada paso (H2 y Semana 12) | Accesibilidad (WCAG 2.4.3 y 4.1.3) | Sonda S3 y `teclado.mjs` 4/4 | Probado |
| Errores con `aria-invalid` y `role=alert` (H3) y campos obligatorios (H4) | Accesibilidad (WCAG 3.3.1 y 3.3.2) | Sondas S4 y S5 | Probado |
| Éxito con la nueva estancia y la hora (H6) | RF-TA-09 | Sonda S8 y la captura del éxito | Probado |
| Limpiar el formulario tras el éxito (H7) | RNF-TA-02 | Sonda S9 | Probado |
| Solo identificadores en pantalla | RNF-TA-07 | `simulaciones.md` y las capturas | Revisado |
| Un punto de corte en 30rem; objetivos de 44 px | Movilidad (S10) | Recorrido 9/9 a 320, 375 y 430 px | Probado |
| Elegir la cama de una lista (H5) | Usabilidad | — | **Pendiente**: depende de la integración (ASII-06 o ASII-09) |
