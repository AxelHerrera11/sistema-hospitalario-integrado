# Checklist heurístico y de accesibilidad

**Base:** flujo y wireframes esquemáticos de Semana 8, contrastados con el
contrato disponible. “Parcial” significa que el diseño lo contempla, pero no
hay una interfaz real para comprobar el comportamiento. “No verificable aún”
se reserva para lo que depende de presentación o tecnología asistiva.

## Usabilidad

| Criterio | Estado | Evidencia | Observación |
|---|---|---|---|
| Visibilidad del estado del sistema | Parcial | Semana 8, flujo y wireframes: carga, vacío, éxito y error | Estados descritos, no implementados ni probados. |
| Lenguaje comprensible | Parcial | Wireframes: “No encontramos…” y “No se pudo completar…” | Lenguaje llano previsto; falta validar mensajes concretos de validación/conflicto. |
| Prevención de errores | Parcial | Wireframes de registro/edición; OpenAPI define campos y enums | Se indica fecha no futura e inmutabilidad; falta guía para `global_id` y significado de `status`. |
| Consistencia | Parcial | Flujo y seis wireframes usan permisos y acciones en español | La respuesta de conflicto del contrato no coincide con el error del módulo (UX-05). |
| Control del usuario | Parcial | Búsqueda nueva, corregir código, cancelar formularios | Cancelar edición no define qué pasa con cambios sin guardar (UX-04). |
| Recuperación ante errores | Parcial | Decisiones UX: conservar datos, corregir o reintentar | No hay pantalla ejecutable; el mapeo de duplicados requiere aclaración (UX-05). |
| Reducción de carga cognitiva | Parcial | Búsqueda exacta por código; detalle omite IDs técnicos | `global_id` obligatorio carece de orientación visible (UX-02). |
| Claridad de acciones | Parcial | Botones “Buscar paciente”, “Guardar paciente”, “Guardar cambios” | Nombres especificados; no puede revisarse su presentación ni contexto real. |
| Confirmaciones cuando corresponda | Parcial | El diseño anuncia éxito después de guardar | La salida del formulario con cambios pendientes no define confirmación (UX-04). |
| Visibilidad de acciones según permiso | Parcial | Flujo y decisiones condicionan búsqueda/alta/edición a permisos | No hay permisos efectivos en la sesión frontend ni pantalla conectada (UX-01). |
| Protección de datos sensibles | Parcial | Decisiones UX minimizan nombres, nacimiento, contacto, estado e identificadores | No hay interfaz/logs del módulo que permitan comprobar exposición real; errores deben ser neutros. |

## Accesibilidad básica

| Criterio | Estado | Evidencia | Observación |
|---|---|---|---|
| Navegación por teclado | No verificable aún | Wireframes dicen que la tabulación sigue orden visual | No hay pantalla funcional para recorrer con teclado. |
| Foco visible | No verificable aún | Semana 8 lo requiere como intención de diseño | No hay estilos ni componentes que inspeccionar. |
| Etiquetas de campos | Parcial | Wireframes nombran cada campo y Semana 8 pide etiqueta visible | Asociación programática etiqueta-control no comprobable en ASCII. |
| Orden lógico de foco | Parcial | Orden de campos y controles representado en los wireframes | El orden del diseño no demuestra el orden real de tabulación. |
| Mensajes de error comprensibles | Parcial | Decisiones UX piden errores por campo y recuperación | Falta revisar mensajes renderizados y asociación con controles. |
| Contraste | No verificable aún | Wireframes sin color, tipografía ni tokens visuales | No se pueden medir relaciones de contraste (A11Y-01). |
| No depender únicamente del color | Parcial | Decisiones UX piden texto además de color | No hay interfaz visual para comprobarlo. |
| Nombres claros de botones | Parcial | Acciones escritas con verbos y propósito | El texto está especificado, no renderizado. |
| Estados de carga anunciables | Parcial | “Buscando paciente…” y anuncio de progreso especificados | No se puede verificar región viva ni anuncio con lector de pantalla. |
| Relación etiquetas-controles | No verificable aún | Se requiere asociación explícita en Semana 8 | Sin HTML/Vue no se puede comprobar asociación semántica. |
| Foco ante resultados, errores y permisos | Parcial | Decisiones UX enfocan el resumen de errores; wireframe de alta enfoca el primer campo inválido | No se resuelve la diferencia ni se define destino/restauración por estado (A11Y-02). |

## Lectura de resultados

Los estados y requisitos indicados son compromisos del diseño, no evidencia
de que estén implementados. Los hallazgos UX-02 a UX-05 y A11Y-01/02 describen
vacíos concretos del diseño o del contrato que conviene resolver antes de
construir la interfaz. La ausencia de código funcional impide afirmar que un
criterio de accesibilidad se cumple en ejecución.
