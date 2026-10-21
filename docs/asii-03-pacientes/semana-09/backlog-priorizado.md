# Backlog priorizado

Ordenado según riesgo de acceso, bloqueo/recuperación de tareas, accesibilidad
y claridad de uso. Todas las acciones de diseño marcadas como previas a
frontend deben resolverse antes de construir pantallas funcionales.

| Prioridad | Hallazgo | Acción propuesta | Beneficio esperado |
|---|---|---|---|
| P1 — Antes de frontend | UX-01 | Acordar fuente verificada de permisos efectivos; definir visibilidad de acciones por `pacientes.ver`, `pacientes.registrar` y `pacientes.editar`; conservar autorización servidor por operación. | Evita ofrecer acciones protegidas por rol supuesto o sin permiso comprobado. |
| P2 — Antes de frontend | UX-05 | Acordar mapeo del adaptador para el conflicto `409` del contrato y el `422` del módulo; diseñar mensaje y corrección de campo. | Permite una recuperación coherente de altas duplicadas. |
| P3 — Antes de frontend y validación visual | A11Y-02 | Especificar anuncios, destino/restauración de foco y asociación de errores para cada estado dinámico. | Hace predecible la navegación por teclado y tecnología asistiva. |
| P4 — Antes de frontend y aceptación visual | A11Y-01 | Definir tokens/estados visuales, no depender solo del color y medir contraste en prototipo/interfaz. | Hace evaluable la percepción de texto y estados antes de publicar. |
| P5 — Antes de frontend | UX-02 | Añadir ayuda de formato UUID y procedencia de `global_id`; validar y explicar error sin exponer datos de terceros. | Reduce errores de entrada en un identificador sensible. |
| P6 — Antes de frontend | UX-03 | Acordar etiquetas y significado para estados permitidos con responsable funcional. | Reduce selección accidental e interpretación desigual. |
| P7 — Antes de frontend | UX-04 | Definir confirmación de descarte solo si edición contiene cambios pendientes. | Previene pérdida accidental sin añadir pasos innecesarios. |

## Dependencias y alcance

P1 y P2 dependen de la futura integración/autorización HTTP, que no forma
parte de esta entrega. P3 y P4 requieren una interfaz o prototipo visual para
validar comportamiento; la especificación puede cerrarse antes de implementarla.
P5–P7 son decisiones de diseño que no requieren crear componentes en Semana 9.

No se propone código nuevo: no hay frontend funcional de Pacientes donde
corregir problemas observables sin inventar una interfaz.
