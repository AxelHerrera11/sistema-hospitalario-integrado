# Alertas críticas y notificaciones internas — Semana 9: evaluación de usabilidad y accesibilidad

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-21 · **Entrega:** checklist, hallazgos y mejoras propuestas.

## Alcance

Se evalúan las pantallas de la [Semana 8](../semana-08/README.md): bandeja de alertas, diálogo de lectura de verificación y aviso de alerta ya acusada.

## Método

- **Evaluación heurística:** las 10 heurísticas de Nielsen sobre las pantallas de la Semana 8.
- **Inspección WCAG 2.1 AA:** teclado, foco, contraste, etiquetas, mensajes, zoom al 200 % y uso de color.
- **Recorridos simulados:** solo con teclado (Tab, Enter, Esc) y con lector de pantalla (VoiceOver o NVDA, leyendo el orden previsto del DOM).
- **Severidad:**
  - **Alta:** puede inducir una decisión clínica errónea o bloquear la tarea;
  - **Media:** la dificulta;
  - **Baja:** es cosmética.
- **Prioridad:** severidad × frecuencia de uso del rol.

## Heurísticas de Nielsen

| # | Heurística | Resultado | Observación |
|---|---|---|---|
| 1 | Visibilidad del estado | Cumple | Contador en el menú y minutos restantes por alerta |
| 2 | Relación con el mundo real | Cumple | «Bajo» o «sobre el umbral crítico» con el valor y la unidad |
| 3 | Control y libertad | Cumple | El diálogo se cancela sin acusar; la alerta sigue pendiente |
| 4 | Consistencia y estándares | Cumple | Mismos códigos de error que el contrato |
| 5 | Prevención de errores | Cumple | La lectura de verificación obliga a mirar el valor antes de acusar |
| 6 | Reconocer antes que recordar | Parcial | El diálogo no repite el valor de la alerta a propósito; hay que leerlo en la bandeja |
| 7 | Flexibilidad y eficiencia | Parcial | No se pueden acusar varias alertas del mismo paciente a la vez (decisión de seguridad) |
| 8 | Diseño minimalista | Cumple | Una línea por alerta, con lo necesario para decidir |
| 9 | Ayudar a reconocer y corregir errores | Cumple | El 422 indica que el valor no coincide y deja reintentar |
| 10 | Ayuda y documentación | Parcial | Falta el procedimiento cuando no hay relación de cuidado (403) |
