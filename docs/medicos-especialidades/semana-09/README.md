# Médicos, especialidades y disponibilidad — Semana 9: evaluación de usabilidad y accesibilidad

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-04 · **Entrega:** checklist, hallazgos y mejoras propuestas.

**Tarea individual:** Evalúe el flujo UX de Médicos, especialidades y disponibilidad con heurísticas de usabilidad y WCAG: teclado, foco, contraste, etiquetas, mensajes y prevención de errores. Priorice correcciones según impacto en médicos, especialidades, horarios, disponibilidad y conflictos de agenda.

## Alcance

Se evalúan las tres pantallas de la [Semana 8](../semana-08/README.md): alta de médico, ficha con asociación de especialidad y disponibilidad.

## Método

- **Evaluación heurística:** las 10 heurísticas de Nielsen sobre los wireframes y el user flow.
- **Inspección WCAG 2.1 AA:** teclado, foco, contraste, etiquetas, mensajes, zoom al 200 % y uso de color.
- **Recorridos simulados:** solo con teclado (Tab, Enter, Esc) y con lector de pantalla (NVDA), siguiendo el orden previsto del DOM.
- **Severidad:** **alta** si puede inducir un dato o una cita errónea o bloquear la tarea; **media** si la dificulta; **baja** si es cosmética.
- **Prioridad:** severidad × frecuencia de uso del rol; cada hallazgo indica el área afectada (médicos, especialidades, horarios, disponibilidad o conflictos de agenda) (la recepcionista consulta disponibilidad muchas veces al día; el alta es ocasional).

## Heurísticas de Nielsen

| # | Heurística | Resultado | Observación |
|---|---|---|---|
| 1 | Visibilidad del estado | Cumple | Carga, vacío, sin horario y éxito tienen su estado propio |
| 2 | Relación con el mundo real | Cumple | «Número de colegiado», nombres de especialidad y horas como en la agenda |
| 3 | Control y libertad | Parcial | Una especialidad mal asociada no se puede cerrar desde la ficha; el contrato aún no tiene ese endpoint |
| 4 | Consistencia y estándares | Cumple | Los mismos códigos y mensajes que el contrato API |
| 5 | Prevención de errores | Parcial | Solo se ofrecen especialidades activas y los botones se deshabilitan al enviar, pero el aviso de principal existente no tiene un lugar definido en el wireframe |
| 6 | Reconocer antes que recordar | Parcial | El usuario se elige de una lista; la especialidad se muestra con nombre y código |
| 7 | Flexibilidad y eficiencia | Parcial | La recepcionista debe elegir especialidad y fecha cada vez; no hay «hoy» por defecto ni «día siguiente» |
| 8 | Diseño minimalista | Cumple | La disponibilidad muestra solo franjas libres |
| 9 | Ayudar a reconocer y corregir errores | Cumple | Cada 409 y 422 explica el motivo y sugiere la acción |
| 10 | Ayuda y documentación | Parcial | «Sin horario configurado» no dice a quién acudir ni cuándo se resolverá |

## Checklist WCAG 2.1 AA

| Criterio | Resultado | Nota |
|---|---|---|
| 1.3.1 Información y relaciones | Parcial | Las franjas deben ser un grupo de opciones (`radiogroup`) por médico, no botones sueltos |
| 1.4.1 Uso del color | Parcial | La especialidad principal se marca con ●; debe decir «principal» en texto (ya lo dice en el wireframe) y no depender del color |
| 1.4.3 Contraste mínimo | Pendiente de verificar | Se mide en el prototipo de la Semana 11, porque los wireframes no tienen colores finales |
| 1.4.4 Cambio de tamaño del texto | Pendiente de verificar | Zoom al 200 % sobre la lista de franjas en el prototipo |
| 2.1.1 Teclado | Parcial | Flechas dentro de las franjas de un médico y Tab para pasar al siguiente |
| 2.4.3 Orden del foco | Cumple | Usuario → licencia → especialidad → vigencia → Registrar |
| 2.4.7 Foco visible | No cumple | Los wireframes no definen cómo se ve el foco en campos, botones y franjas |
| 3.3.1 Identificación de errores | Cumple | El error nombra el campo y el motivo |
| 3.3.2 Etiquetas o instrucciones | Parcial | El formato de fecha (`AAAA-MM-DD`) y que «Hasta» es opcional deben estar en la etiqueta |
| 3.3.4 Prevención de errores (datos importantes) | Parcial | Hay resumen antes de confirmar, pero «Corregir» vuelve al formulario completo y no al dato equivocado |
| 4.1.3 Mensajes de estado | Parcial | «Médico registrado» y «No hay médicos vigentes» deben anunciarse con `role="status"` |

## Hallazgos priorizados

| # | Severidad | Área | Hallazgo | Impacto | Mejora propuesta |
|---|---|---|---|---|---|
| H1 | Alta | Médicos | Ante «sin respuesta» en el alta, el usuario puede repetirla | Intento de alta duplicada (la base lo rechaza con 409, pero confunde) | «No sabemos si se guardó» con botón «Buscar licencia» antes de permitir repetir |
| H2 | Alta | Conflictos de agenda | Una franja seleccionada puede parecer reservada | La recepcionista promete una hora que Citas aún no confirmó | Texto «Elegir no reserva: se confirma en Citas» junto al botón |
| H3 | Media | Disponibilidad | Las franjas no son un `radiogroup` | Con lector de pantalla no se sabe a qué médico pertenece cada hora | `role="radiogroup"` por médico con `aria-label` «Franjas de Dra. Ana López» |
| H4 | Media | Especialidades | El aviso de principal existente no tiene un lugar en el wireframe | Se pasa por alto y llega un 409 evitable | Mostrar «Ya tiene principal: Cardiología» junto a la casilla al marcarla |
| H5 | Media | Disponibilidad | Sin «hoy» por defecto ni «día siguiente» | Más clics en la tarea más frecuente | Fecha de hoy por defecto y botones «‹ día anterior · día siguiente ›» |
| H6 | Media | Médicos y especialidades | «Corregir» en el resumen del alta vuelve al formulario completo | Corregir un dato cuesta más pasos y se tiende a confirmar sin corregir | «Corregir» en cada línea del resumen, que lleva directo a ese campo |
| H9 | Media | Todas | Sin foco visible definido (2.4.7) | Quien usa teclado no sabe dónde está, sobre todo entre franjas | Contorno de 3 px de alto contraste en todo control enfocable |
| H7 | Baja | Horarios | «Sin horario configurado» no orienta | Llamadas a soporte | Agregar «Pida a administración que cargue el horario» |
| H8 | Baja | Especialidades | Falta el formato de fecha en la etiqueta | Errores 422 de fecha | Etiqueta «Vigente desde (AAAA-MM-DD)» y selector de fecha |

## Plan de mejoras

1. **Antes del prototipo (Semana 11):** H1 y H2, porque pueden generar un dato o una cita errónea.
2. **En el prototipo:** H3 a H6 y H9, y la medición de contraste y zoom de los criterios pendientes.
3. **Después:** H7 y H8.

Las mejoras se incorporan en la adaptación móvil (Semana 10) y en el prototipo navegable (Semana 11). Uso de IA: [DECLARACION_IA.md](./DECLARACION_IA.md).
