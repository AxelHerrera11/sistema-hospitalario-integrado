# Backlog priorizado de mejoras

Prioridad: **P1** bloquea el uso con teclado o lector de pantalla; **P2** afecta la comprensión o la prevención de errores; **P3** mejora menor. Destino: el prototipo de Semana 11, donde cada criterio se comprobará y se registrará.

| Prioridad | Hallazgo | Corrección propuesta | Criterio verificable |
|---|---|---|---|
| P1 | H5 Foco de los diálogos | Al abrir W3 o W4 el foco va al primer campo o al título del diálogo; Tab queda dentro del diálogo; Esc cierra; al cerrar vuelve al botón que lo abrió | Recorrido solo con teclado: el foco nunca sale del diálogo abierto y vuelve al botón de origen |
| P1 | H3 Botones ambiguos | Nombre accesible con el código: "Cambiar vigencia de AMX-500" (`aria-label` o texto oculto) | Cada botón de la tabla tiene un nombre accesible distinto que incluye el código |
| P2 | H6 Foco por defecto en la confirmación | "Cancelar" recibe el foco inicial; la acción conserva el código en su texto | Al abrir W4, Enter sin mover el foco cancela |
| P2 | H7 Anuncio de resultados y éxito | Región `role="status"` para "N resultados", "Sin coincidencias", "Medicamento registrado" y "Vigencia actualizada" | Los mensajes aparecen en un elemento con `role="status"` sin mover el foco |
| P2 | H2 Códigos HTTP visibles | Textos sin códigos: "Ya existe un medicamento con este código", "Complete el principio activo (2 a 120 caracteres)" | Ningún texto visible contiene 4xx o 5xx |
| P2 | H4 Semántica de la tabla | `<table>` con `<caption>` y `<th scope="col">` | La tabla del prototipo tiene caption y encabezados con scope |
| P3 | H1 Leyenda de obligatorios | Texto "* campo obligatorio" al inicio del formulario y `required` en los campos | El formulario muestra la leyenda y los campos tienen el atributo `required` |
| P3 | H8 Envío con teclado | La búsqueda es un `<form>`: Enter la envía | Enter en el campo dispara la búsqueda |

Estas correcciones actualizan el diseño de Semana 8, sin agregar funcionalidad nueva.
