# Caso práctico de diseño — cambio solicitado (ASII-17)

**Pedido:** Laboratorio pide **mostrar en la lista la próxima versión programada** (por ejemplo, «GLU v2 vigente desde 01/11») para que Órdenes sepa que los rangos van a cambiar.

## Impacto por capa

| Pieza | ¿Cambia? | Qué cambia |
|---|---|---|
| Domain | No | Las versiones futuras ya existen: `valid_from` posterior a hoy |
| Application | Sí | `FindActiveLabTests` devuelve también la próxima versión de cada código, si existe |
| Contrato API | Sí, compatible | Campo opcional `next_version: { version_no, valid_from }` en cada fila |
| UI | Sí | Etiqueta «v2 desde 01/11» junto a la versión vigente, con texto y no solo color (WCAG 1.4.1) |
| Accesibilidad | Revisar | La etiqueta debe leerse como parte de la fila, no como un aviso aparte |
| Riesgo | Medio | Que Órdenes elija la versión futura: la acción «Ordenar» sigue usando la vigente |

## Cómo lo absorbe el diseño

El cambio entra por una extensión compatible del contrato y por la UI. Las reglas que protegen al paciente (las de la [matriz](./matriz-decision-rf-rnf.md)) no se tocan. Antes de implementarlo hay que actualizar el flujo UX (Semana 8) y repetir el recorrido del prototipo (Semana 11) con el estado nuevo.
