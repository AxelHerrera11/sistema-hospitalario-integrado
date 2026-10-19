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

## Checklist WCAG 2.1 AA

| Criterio | Resultado | Nota |
|---|---|---|
| 1.4.1 Uso del color | Parcial | Libre y ocupada se distinguen por color y por ✓/✕; el 🔧 de mantenimiento necesita texto |
| 1.4.3 Contraste mínimo | Pendiente de verificar | Los mosaicos de cama deben llegar a 4,5:1 |
| 1.3.1 Información y relaciones | Parcial | El tablero debe ser una lista de opciones (`radiogroup`) y no mosaicos sueltos |
| 2.1.1 Teclado | Parcial | Con flechas dentro del tablero y Tab para salir |
| 2.4.3 Orden del foco | Cumple | Paciente → motivo → tipo → tablero → Admitir |
| 3.3.1 Identificación de errores | Cumple | El error nombra el campo |
| 3.3.4 Prevención de errores (datos importantes) | Cumple | Pantalla de confirmación con paciente, cama y motivo |
| 4.1.3 Mensajes de estado | Parcial | «Paciente admitido en MED-101» debe anunciarse con `role="status"` |

«Pendiente de verificar» significa que el contraste se mide sobre el prototipo de la Semana 11, porque los wireframes no tienen colores finales.

## Hallazgos priorizados

| # | Severidad | Hallazgo | Impacto | Mejora propuesta |
|---|---|---|---|---|
| H1 | Alta | El mensaje «sin respuesta» puede leerse como error y llevar a repetir el alta | Admisión duplicada o paciente en dos camas | Texto explícito «No sabemos si se guardó» y botón «Verificar cama» antes de permitir repetir |
| H2 | Media | El tablero no es un `radiogroup` | Con lector de pantalla no se sabe cuál está seleccionada | Usar `role="radiogroup"` con `aria-checked` |
| H3 | Media | El ícono 🔧 de mantenimiento no tiene texto | No se entiende por qué no se puede elegir | Texto «en mantenimiento» visible y accesible |
| H4 | Media | No hay atajo para la cama sugerida | Más clics en horas de alta demanda | Preseleccionar la primera libre del tipo, cambiable |
| H5 | Baja | La lista de espera no se explica | Duda cuando no hay camas | Mensaje con el procedimiento manual hasta implementarla |

## Plan de mejoras

1. **Antes del prototipo (Semana 11):** H1 y H2, porque pueden inducir una decisión clínica errónea.
2. **En el prototipo:** H3 y H4, y la medición de contraste de los criterios pendientes.
3. **Después:** H5.

Las mejoras se incorporan en la adaptación móvil (Semana 10) y en el prototipo navegable (Semana 11). Uso de IA: [DECLARACION_IA.md](./DECLARACION_IA.md).
