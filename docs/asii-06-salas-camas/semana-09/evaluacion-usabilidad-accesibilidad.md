# Evaluación de usabilidad y accesibilidad — ASII-06

## Base y método

Se inspeccionaron el
[flujo UX de Semana 8](../semana-08/flujo-ux.md), los contratos de
`GET /api/v1/wards` y `GET /api/v1/wards/{ward}/beds`, el permiso
`camas.ver`, la navegación, guardas, layout, Axios y la página Vue de
Pacientes. Se contrastó el formato de entrega con la evaluación de Semana 9
de ASII-03.

Es una inspección documental/estática, no una evaluación con usuarios. El
frontend está hecho con Vue 3, Vue Router, Pinia y Axios, pero ASII-06 no tiene
vista, ruta ni entrada de navegación. No se probó teclado, lector de pantalla,
contraste de una pantalla ASII-06 ni cumplimiento WCAG en ejecución. **N/V**
significa no verificable con el artefacto actual; **Parcial** indica que el
flujo lo especifica, pero aún no hay UI que demuestre el comportamiento.

## Heurísticas de usabilidad

| Heurística | Estado | Evidencia y evaluación |
|---|---|---|
| Visibilidad del estado del sistema | Parcial | Semana 8 describe carga, vacío, éxito y error; no hay vista que anuncie o represente esos estados. |
| Correspondencia con el mundo real | Parcial | “Ward” y “cama” son los conceptos del módulo; `available`, `occupied`, `maintenance`, `inactive`, `active` e `inactive` siguen siendo códigos técnicos sin etiquetas UX acordadas. |
| Control y libertad del usuario | Parcial | El flujo conserva la selección al reintentar camas, pero no especifica cómo volver al listado o cambiar de ward desde el detalle. |
| Consistencia y estándares | Parcial | La API tiene respuestas consistentes y Pacientes/HIS usa layout compartido; ASII-06 aún no tiene entrada ni ruta Vue para mantener ese patrón. |
| Prevención de errores | Parcial | Las consultas usan `ward_id` de la respuesta y la API valida ward inexistente; no hay UI que evite una selección obsoleta ni pruebe el flujo. |
| Reconocimiento antes que recuerdo | Parcial | Semana 8 propone mostrar código, nombre, ubicación y estado; no define cómo se conserva el contexto de la ward al mostrar camas. |
| Flexibilidad y eficiencia | N/V | No hay vista para evaluar navegación, atajos, filtros o selección con teclado. |
| Diseño estético y minimalista | N/V | No existe composición visual de ASII-06 para evaluar jerarquía o densidad. |
| Reconocer, diagnosticar y recuperarse de errores | Parcial | Se describen `401`, `403`, `404` y `503`; faltan presentaciones verificables para fallos de red y reintento en la interfaz. |
| Ayuda y documentación | Parcial | La documentación técnica existe; no hay ayuda contextual para explicar estados o la relación ward-camas en pantalla. |

## Checklist de accesibilidad (referencia WCAG 2.1 AA)

| Criterio | Estado | Observación |
|---|---|---|
| Operable por teclado (2.1.1) | N/V | No hay controles de ASII-06 renderizados. El prototipo deberá permitir seleccionar una ward y consultar camas sin ratón. |
| Orden y visibilidad del foco (2.4.3, 2.4.7) | N/V | No hay orden de foco ni componentes; Pacientes usa `:focus-visible`, patrón potencialmente reutilizable. |
| Encabezados y relaciones semánticas (1.3.1) | N/V | No está decidido si el catálogo se presenta como lista o tabla accesible. |
| Contraste de texto (1.4.3) | N/V | No hay paleta ni pantalla del catálogo que medir. |
| Uso del color (1.4.1) | Parcial | Semana 8 pide que estado de cama no dependa solo del color; falta definir los indicadores visuales y textuales en UI. |
| Reflow/zoom (1.4.10) | N/V | No existe implementación del catálogo que revisar en 320 CSS px o zoom. |
| Etiquetas e instrucciones (3.3.2) | Parcial | La consulta no requiere campos de entrada, pero la selección y acción de ver camas aún no tienen controles rotulados. |
| Navegación coherente y nombre de página (2.4.2, 3.2.3) | N/V | No existe ruta Vue ni enlace navegable del catálogo. |
| Mensajes de estado y errores (4.1.3) | Parcial | Semana 8 pide anunciar carga, vacío y errores; no especifica región viva, prioridad ni foco para cada transición. |
| Nombre, función y valor (4.1.2) | N/V | No hay controles seleccionables de ward con semántica comprobable. |
| Tamaño de objetivos táctiles (recomendación para el prototipo) | N/V | No hay controles renderizados; medirlos al construir UI. No se evalúa como criterio de WCAG 2.1 AA. |

## Conclusión

La propuesta de Semana 8 cubre estados funcionales importantes, pero la
mayoría de criterios interactivos dependen de un prototipo o interfaz. Los
hallazgos de esta evaluación son acciones de diseño para antes o durante esa
fase, no defectos de una UI inexistente. No se declara conformidad WCAG ni
validación con usuarios o lectores de pantalla.
