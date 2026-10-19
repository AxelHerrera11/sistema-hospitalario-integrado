# Admisión hospitalaria y asignación de cama — Semana 9: evaluación de usabilidad y accesibilidad

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-07 · **Entrega:** checklist, hallazgos y mejoras propuestas.

## Alcance

Se evalúan las pantallas de la [Semana 8](../semana-08/README.md): datos de la admisión, tablero de camas disponibles y error de cama tomada.

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
| 1 | Visibilidad del estado | Cumple | El tablero muestra libre, ocupada y mantenimiento, y cuántas hay |
| 2 | Relación con el mundo real | Cumple | Códigos de cama como en la sala (MED-101) |
| 3 | Control y libertad | Parcial | Tras confirmar no hay forma de deshacer desde la pantalla; depende de Traslados (ASII-08) |
| 4 | Consistencia y estándares | Cumple | Los mismos códigos de error que el contrato |
| 5 | Prevención de errores | Cumple | Las camas de otro tipo no se ofrecen y «Admitir» se deshabilita durante el envío |
| 6 | Reconocer antes que recordar | Cumple | El tablero es visual; no hay que recordar el código |
| 7 | Flexibilidad y eficiencia | Parcial | No hay atajo para la cama sugerida (la primera libre del tipo) |
| 8 | Diseño minimalista | Cumple | Solo las camas del tipo pedido |
| 9 | Ayudar a reconocer y corregir errores | Cumple | El 409 refresca el tablero y explica qué pasó |
| 10 | Ayuda y documentación | Parcial | Falta explicar la lista de espera, que aún es propuesta |
