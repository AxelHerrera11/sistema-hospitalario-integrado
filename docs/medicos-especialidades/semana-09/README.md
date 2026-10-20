# Médicos, especialidades y disponibilidad — Semana 9: evaluación de usabilidad y accesibilidad

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-04 · **Entrega:** checklist, hallazgos y mejoras propuestas.

## Alcance

Se evalúan las tres pantallas de la [Semana 8](../semana-08/README.md): alta de médico, ficha con asociación de especialidad y disponibilidad.

## Método

- **Evaluación heurística:** las 10 heurísticas de Nielsen sobre los wireframes y el user flow.
- **Inspección WCAG 2.1 AA:** teclado, foco, contraste, etiquetas, mensajes, zoom al 200 % y uso de color.
- **Recorridos simulados:** solo con teclado (Tab, Enter, Esc) y con lector de pantalla (NVDA), siguiendo el orden previsto del DOM.
- **Severidad:** **alta** si puede inducir un dato o una cita errónea o bloquear la tarea; **media** si la dificulta; **baja** si es cosmética.
- **Prioridad:** severidad × frecuencia de uso del rol (la recepcionista consulta disponibilidad muchas veces al día; el alta es ocasional).

## Heurísticas de Nielsen

| # | Heurística | Resultado | Observación |
|---|---|---|---|
| 1 | Visibilidad del estado | Cumple | Carga, vacío, sin horario y éxito tienen su estado propio |
| 2 | Relación con el mundo real | Cumple | «Número de colegiado», nombres de especialidad y horas como en la agenda |
| 3 | Control y libertad | Parcial | Una especialidad mal asociada no se puede cerrar desde la ficha; el contrato aún no tiene ese endpoint |
| 4 | Consistencia y estándares | Cumple | Los mismos códigos y mensajes que el contrato API |
| 5 | Prevención de errores | Parcial | Solo se ofrecen especialidades activas y los botones se deshabilitan al enviar, pero «Principal» no avisa antes de enviar si ya existe una |
| 6 | Reconocer antes que recordar | Parcial | El usuario se elige de una lista; la especialidad se muestra con nombre y código |
| 7 | Flexibilidad y eficiencia | Parcial | La recepcionista debe elegir especialidad y fecha cada vez; no hay «hoy» por defecto ni «día siguiente» |
| 8 | Diseño minimalista | Cumple | La disponibilidad muestra solo franjas libres |
| 9 | Ayudar a reconocer y corregir errores | Cumple | Cada 409 y 422 explica el motivo y sugiere la acción |
| 10 | Ayuda y documentación | Parcial | «Sin horario configurado» no dice a quién acudir ni cuándo se resolverá |

Uso de IA: [DECLARACION_IA.md](./DECLARACION_IA.md).
