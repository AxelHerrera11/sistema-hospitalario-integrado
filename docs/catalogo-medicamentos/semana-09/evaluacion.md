# Evaluación de usabilidad y accesibilidad del diseño de Semana 8

**Objeto evaluado:** [flujo, reglas R1–R12 y wireframes W1–W5](../semana-08/README.md). No hay UI construida.

**Tipos de verificación:** **E** = estática, por inspección del diseño; **X** = ejecutada; **N/E** = no evaluable todavía.

## Checklist completado

| # | Criterio | Fuente | Tipo | Resultado |
|---|---|---|---|---|
| C1 | Contraste del texto de error ≥ 4.5:1 | WCAG 1.4.3 | X | Cumple: `#B91C1C` 6.47:1 ([evidencia](./evidencia/contraste.php)) |
| C2 | La vigencia no depende solo del color | WCAG 1.4.1 | E | Cumple: W2 dice "Activo" o "Inactivo" en texto (R6) |
| C3 | Todo campo tiene etiqueta visible | WCAG 3.3.2 | E | Parcial: hay etiquetas, pero el `*` de obligatorio no se explica (H1) |
| C4 | Los errores identifican el campo y cómo corregirlo | WCAG 3.3.1, 3.3.3 | E | Cumple: W3 junto al campo y con la regla; se conservan los datos (R7) |
| C5 | Mensajes en lenguaje del usuario | Nielsen 2 | E | No cumple: W3 muestra "(409)" y "(422)" al usuario (H2) |
| C6 | Propósito de cada control comprensible fuera de contexto | WCAG 2.4.6 | E | No cumple: "Cambiar vigencia" se repite en cada fila sin nombrar el medicamento (H3) |
| C7 | Tabla con encabezados asociados | WCAG 1.3.1 | E | No especificado: W2 no indica `th`/`scope` ni título de tabla (H4) |
| C8 | Diálogos: foco inicial, foco atrapado y retorno | WCAG 2.4.3, 2.1.2 | E | No especificado para W3 ni W4 (H5) |
| C9 | Prevención de errores en acciones con efecto | Nielsen 5, WCAG 3.3.4 | E | Parcial: W4 confirma con el registro completo, pero no define qué botón recibe el foco (H6) |
| C10 | Cambios de estado anunciados sin mover el foco | WCAG 4.1.3 | E | Parcial: R4 usa `aria-busy`; el número de resultados y el éxito no tienen región `status` (H7) |
| C11 | Operable solo con teclado | WCAG 2.1.1 | E | No especificado: falta indicar que Enter envía la búsqueda (H8) |
| C12 | Sesión, hospital y alcance visibles | Nielsen 1 | E | Cumple: W1 muestra el hospital activo (R1) |
| C13 | Lector de pantalla real (NVDA) | WCAG 4.1.2 | N/E | Requiere UI construida (Semana 11) |
| C14 | Contraste de la paleta final, foco visible y reflow | WCAG 1.4.11, 2.4.7, 1.4.10 | N/E | La paleta y los estilos se definen en el prototipo (Semanas 10–11) |
| C15 | Prueba con usuarios | — | N/E | No se realizó ninguna prueba con usuarios |

## Hallazgos sustentados

| ID | Hallazgo | Evidencia en el diseño | Criterio | Severidad |
|---|---|---|---|---|
| H1 | `*` de obligatorio sin leyenda | W3: "Código\*", "Nombre\*"… sin texto que lo explique | WCAG 3.3.2 | Media |
| H2 | Códigos HTTP visibles para el usuario | W3: "Este código ya está registrado (409)", "(422)" | Nielsen 2 | Media |
| H3 | Botones ambiguos por fila | W2: dos botones "Cambiar vigencia" idénticos | WCAG 2.4.6 | Alta |
| H4 | Tabla sin semántica especificada | W2: encabezados solo visuales | WCAG 1.3.1 | Media |
| H5 | Gestión de foco de los diálogos no definida | W3 y W4, R7–R8: no dicen dónde empieza el foco ni a dónde vuelve | WCAG 2.4.3, 2.1.2 | Alta |
| H6 | Foco por defecto en la confirmación sin definir | W4: "Cancelar" y "Inactivar AMX-500" sin indicar cuál recibe el foco | Nielsen 5 | Media |
| H7 | Resultados y éxito no anunciados | W2 "2 resultados" y R10 sin `role="status"` | WCAG 4.1.3 | Media |
| H8 | Envío con teclado no especificado | W1: solo el botón Buscar | WCAG 2.1.1 | Baja |

El backlog priorizado con la corrección y el criterio verificable de cada hallazgo está en [backlog.md](./backlog.md).
