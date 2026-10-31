# Checklist técnico — 26 criterios

Resultados limitados al material revisado: Cumple = evidencia suficiente para
el alcance escrito; No cumple = discrepancia observable; Parcial = diseño/muestra
incompletos; No verificable = interacción/material no disponible. No son certificado.
Métodos: S = fuente HTML/reglas, V = render visual, K = eventos de teclado en Edge,
M = medición DOM/CSS. Procedimiento, versión y material fijado en [README](./README.md).

## Usabilidad

| ID | Criterio | WF | Método | Resultado | Evidencia / observación |
|---|---|---|---|---|---|
| U01 | Navegación con destino reconocible | 04/03 | S/V | No cumple | “Consultar diseño” lleva a “Diseñar caso manual” sin caso precargado; UX-01 |
| U02 | Consistencia entre pantallas | Todos | S/V | Parcial | Volver es claro; WF-06 mezcla cancelación/consulta, UX-02 |
| U03 | Visibilidad del estado | 01/02/05 | S/V | Parcial | Vacío/error/éxito son catálogo de alternativas; estado dinámico no comprobable |
| U04 | Jerarquía visual | Todos | S/V | Cumple | Un h1, seis h2, h3 subordinados; contexto antes de datos/acciones |
| U05 | Reconocimiento frente a memoria | 04/05/06 | S/V | Cumple | Origen campaña/caso/ejecución visible como ejemplo; no acredita datos reales |
| U06 | Claridad de acciones | 06 | S/V | No cumple | “Cancelar / consultar trazabilidad” promete dos objetivos; UX-02 |
| U07 | Prevención de errores | 03/05/06 | S | Parcial | Campos obligatorios con *, sin explicación; UX-03; guardado está disabled |
| U08 | Mensajes y recuperación | 03/05 | S | Parcial | Error de pasos visible; asociación programática y recuperación incierta pendientes, UX-04/06 |
| U09 | Estados vacíos útiles | 01/02/04 | S/V | Cumple | Orientan a diseñar, limpiar filtros o asociar; evaluado solo texto de diseño |
| U10 | Confirmación y recuperación de operaciones | 03/05/06 | S | Parcial | Mensaje propone ID; falta diseño para respuesta perdida, UX-06 |
| U11 | Flujo para ejecutar pruebas | 04/05 | S | Parcial | Pasos/esperado/observación presentes; referencia de evidencia sin ejemplo, UX-05 |
| U12 | Flujo para registrar defecto | 05/06 | S | Parcial | Origen fallido indicado; cancelación ambigua y severidad pendiente, UX-02 |
| U13 | Trazabilidad | 04/06 | S | Parcial | Cadena escrita, pero vínculos son placeholders; relación real no verificable |

## Accesibilidad orientada a WCAG 2.2

| ID | Criterio / referencia orientativa | WF | Método | Resultado | Evidencia / observación |
|---|---|---|---|---|---|
| A01 | Encabezados, 1.3.1 / 2.4.6 | Todos | S/M | Cumple | h1/h2/h3 jerárquicos, seis section con aria-labelledby válido |
| A02 | Etiquetas visibles, 3.3.2 | 02/03/05/06 | S/M | Cumple | Doce labels visibles para doce controles; instrucciones obligatorias incompletas se separan en UX-03 |
| A03 | Asociación label-control, 1.3.1 | 02/03/05/06 | M | Cumple | Doce for resuelven ID; cero duplicados |
| A04 | Alternativas de imágenes, 1.1.1 | Todos | S/M | No verificable | Cero imágenes en esta maqueta; sin contenido no textual que auditar en este punto |
| A05 | Identificación de errores, 3.3.1 / 1.3.1 | 03/05/06 | S | Parcial | Texto legible; cero aria-describedby/errormessage/invalid, UX-04; sin error dinámico real |
| A06 | Contraste de texto/componentes, 1.4.3 / 1.4.11 | Todos | S/M | Parcial | Pares de texto activos >4.5:1; foco 12.63:1; no se midieron todos los bordes/estados. Controles inactivos no se declaran infracción |
| A07 | Foco visible, 2.4.7 | Índice | K/M | Cumple | Seis Tab sobre enlaces con outline solid 3 px; alcance solo índice |
| A08 | Teclado, 2.1.1 | Índice/formularios | K/S | Parcial | Tab y Enter sobre enlaces funcionan; campos/botones disabled no permiten evaluar operación completa |
| A09 | Orden de lectura, 1.3.2 / 2.4.3 | Todos | S/K | Parcial | DOM WF-01–06 y primeras seis paradas coherentes; sin lector de pantalla ni recorrido completo |
| A10 | Semántica, 4.1.2 | Todos | S/M | Parcial | HTML nativo, main/nav/labels; estado dinámico de controles no implementado |
| A11 | Tamaño de objetivos, 2.5.8 | Enlaces | S | No verificable | No se midieron hitboxes/espaciado; tamaño de fuente no demuestra cumplimiento ni fallo; existen excepciones para enlaces inline |
| A12 | Zoom/reflow, 1.4.4 / 1.4.10 | Todos | M/V | Parcial | Viewports 1280/390/320 sin scroll horizontal; no equivale a prueba de zoom real 200%/400% |
| A13 | Color como indicador, 1.4.1 | Todos | S/V | Cumple | Resultados, errores y acciones se expresan en texto, no solo color |

Referencias primarias consultadas: [WCAG 2.2 W3C](https://www.w3.org/TR/WCAG22/),
[etiquetas/instrucciones](https://www.w3.org/WAI/WCAG22/Understanding/labels-or-instructions.html),
[identificación de errores](https://www.w3.org/WAI/WCAG22/Understanding/error-identification.html),
[contraste mínimo](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html) y
[tamaño mínimo](https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html).
La falta de ARIA por sí sola no demuestra incumplimiento de 3.3.1: el texto ya
identifica el error; se registra como riesgo de asociación en futura interacción.
No se interpreta disabled o ausencia de backend como defecto de accesibilidad.
