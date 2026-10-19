# Alergias clínicas y alerta visual en expediente — Semana 9: evaluación de usabilidad y accesibilidad

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-12 · **Entrega:** checklist, hallazgos y mejoras propuestas.

## Alcance

Se evalúan las pantallas de la [Semana 8](../semana-08/README.md): expediente con banner crítico, banner «no disponible» y formulario «Registrar alergia».

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
| 1 | Visibilidad del estado | Cumple | Los cuatro niveles del banner son distinguibles y el de carga nunca dice «Sin alergias» |
| 2 | Relación con el mundo real | Cumple | La severidad se muestra en lenguaje clínico («Amenaza la vida») |
| 3 | Control y libertad | Cumple | El formulario se cancela sin guardar; el banner crítico no se oculta, a propósito |
| 4 | Consistencia y estándares | Cumple | El mismo banner en el Expediente y en Prescripciones |
| 5 | Prevención de errores | Parcial | El duplicado se detecta al guardar; no se sugiere mientras se escribe |
| 6 | Reconocer antes que recordar | Parcial | El alérgeno es texto libre; un buscador del catálogo de medicamentos reduciría errores |
| 7 | Flexibilidad y eficiencia | Cumple | Registrar una alergia toma un solo formulario |
| 8 | Diseño minimalista | Parcial | Con cinco o más alergias el banner crece y empuja el expediente |
| 9 | Ayudar a reconocer y corregir errores | Cumple | El 409 enlaza la alergia existente |
| 10 | Ayuda y documentación | Parcial | Falta explicar qué hacer con «no disponible» |

## Checklist WCAG 2.1 AA

| Criterio | Resultado | Nota |
|---|---|---|
| 1.4.1 Uso del color | Cumple | Cada nivel tiene texto propio, no solo color |
| 1.4.3 Contraste mínimo | Pendiente de verificar | El texto blanco sobre rojo y el gris del «no disponible» deben llegar a 4,5:1 |
| 1.3.1 Información y relaciones | Cumple | Tipo y severidad como grupos de radio y lista con `legend` |
| 2.1.1 Teclado | Cumple | El banner no atrapa el foco; «Ver todas» y «Reintentar» son botones |
| 2.2.1 Tiempo ajustable | Cumple | El banner no desaparece solo |
| 3.3.1 Identificación de errores | Cumple | El error nombra el campo |
| 4.1.2 Nombre, función y valor | Parcial | El banner debe exponer el nivel en texto accesible, no solo en la clase CSS |
| 4.1.3 Mensajes de estado | Parcial | El banner crítico usa `role="alert"`; al actualizarse tras un alta, no debe repetirse en cada navegación |

«Pendiente de verificar» significa que el contraste se mide sobre el prototipo de la Semana 11, porque los wireframes no tienen colores finales.

## Hallazgos priorizados

| # | Severidad | Hallazgo | Impacto | Mejora propuesta |
|---|---|---|---|---|
| H1 | Alta | El banner «no disponible» puede confundirse con «sin datos» si el gris es muy suave | El médico podría ignorarlo y prescribir | Gris oscuro con ícono y el texto «verificar con el paciente» siempre visible |
| H2 | Alta | El alérgeno en texto libre permite «Penisilina» o «PNC» | Prescripciones no reconocería la alergia | Buscador del catálogo de medicamentos (ASII-14) y texto libre solo como respaldo |
| H3 | Media | El banner crece con muchas alergias | Empuja el contenido y cansa | Mostrar las 2 más graves y «Ver todas (n)» |
| H4 | Media | `role="alert"` se repite al navegar entre pestañas | El lector de pantalla lo anuncia una y otra vez | Anunciar solo al abrir el expediente o al cambiar el nivel |
| H5 | Baja | Falta ayuda sobre qué hacer con «no disponible» | Duda del personal nuevo | Enlace «¿Qué hago?» con el procedimiento |

## Plan de mejoras

1. **Antes del prototipo (Semana 11):** H1 y H2, porque pueden inducir una decisión clínica errónea.
2. **En el prototipo:** H3 y H4, y la medición de contraste de los criterios pendientes.
3. **Después:** H5.

Las mejoras se incorporan en la adaptación móvil (Semana 10) y en el prototipo navegable (Semana 11). Uso de IA: [DECLARACION_IA.md](./DECLARACION_IA.md).
