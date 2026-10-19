# Catálogo de pruebas de laboratorio — Semana 9: evaluación de usabilidad y accesibilidad

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-17 · **Entrega:** checklist, hallazgos y mejoras propuestas.

## Alcance

Se evalúan las pantallas de la [Semana 8](../semana-08/README.md): lista de pruebas vigentes, formulario «Nueva versión» y detalle de prueba.

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
| 1 | Visibilidad del estado | Cumple | La fecha «Vigentes al» y el hospital están siempre visibles |
| 2 | Relación con el mundo real | Parcial | «Crítico bajo» y «Ref. baja» son términos del laboratorio, pero el orden de los campos no sigue la recta numérica |
| 3 | Control y libertad | Cumple | «Cancelar» no guarda nada y «Limpiar filtro» vuelve a la lista completa |
| 4 | Consistencia y estándares | Cumple | Mismos códigos de error que el contrato, mostrados junto al campo |
| 5 | Prevención de errores | Parcial | El calendario impide fechas pasadas, pero los rangos solo se validan al guardar |
| 6 | Reconocer antes que recordar | Cumple | El tipo de muestra se elige de una lista |
| 7 | Flexibilidad y eficiencia | Falla | No se puede crear una versión nueva a partir de la vigente; hay que reescribir todo |
| 8 | Diseño minimalista | Cumple | La tabla muestra solo lo que Órdenes necesita para decidir |
| 9 | Ayudar a reconocer y corregir errores | Parcial | El 409 dice que hay una versión vigente, pero no ofrece cerrarla |
| 10 | Ayuda y documentación | Falla | No hay ayuda contextual sobre qué es un valor crítico |

## Checklist WCAG 2.1 AA

| Criterio | Resultado | Nota |
|---|---|---|
| 1.3.1 Información y relaciones | Parcial | Los rangos se presentan como cuatro campos sueltos; deben agruparse en un `fieldset` con `legend` |
| 1.4.3 Contraste mínimo | Pendiente de verificar | Los avisos ⚠ deben llegar a 4,5:1 sobre el fondo |
| 1.4.1 Uso del color | Cumple | Los errores tienen texto, no solo color |
| 2.1.1 Teclado | Cumple | Todos los controles son nativos y alcanzables con Tab |
| 2.4.3 Orden del foco | Parcial | Tras un error 422, el foco debe ir al campo indicado en `field` |
| 3.3.1 Identificación de errores | Cumple | El error nombra el campo y la regla |
| 3.3.2 Etiquetas o instrucciones | Parcial | Falta indicar la unidad junto a cada rango |
| 4.1.3 Mensajes de estado | Falla | La confirmación «GLU v2 registrada» no se anuncia: requiere `role="status"` |

«Pendiente de verificar» significa que el contraste se mide sobre el prototipo de la Semana 11, porque los wireframes no tienen colores finales.
