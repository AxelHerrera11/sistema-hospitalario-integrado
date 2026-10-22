# Semana 9 — Evaluación de usabilidad y accesibilidad

## 1. Método (lo que se hizo de verdad)

| Técnica | Herramienta | Alcance |
|---|---|---|
| Revisión heurística y WCAG 2.2 (criterios aplicables, nivel AA) | Inspección manual del prototipo de la Semana 11 en el navegador integrado de Claude Code, a 1280×720 y 375×812 | `micro-his/gobernanza-auditoria/public/prototipo/*` |
| Recorrido con teclado | Pulsaciones reales de Tab, Enter y Esc, registrando `focusin` | Orden de foco, diálogo y retorno del foco |
| Contraste | Cálculo de luminancia relativa WCAG con un script PHP sobre los colores de `styles.css` | 10 pares de colores |
| Estados | Cambio de sesión y simulación de fallos en el prototipo | 200, 401, 403, 422, 503, vacío y cargando |

**No se hicieron pruebas con usuarios ni con lectores de pantalla reales (NVDA/JAWS/VoiceOver), ni se usaron validadores automáticos (axe, Lighthouse).** Los hallazgos salen de la inspección indicada; no hay resultados de sesiones con usuarios.

## 2. Checklist WCAG aplicable

| Criterio | Verificación | Resultado |
|---|---|---|
| 1.3.1 Información y relaciones | `fieldset`/`legend`; `label for` en los 9 campos (se comprobó que ningún campo queda sin etiqueta); tabla con `th scope` y `caption`. | Cumple |
| 1.4.3 Contraste (texto) | Ver la tabla del §3: todos los textos ≥ 4.5:1. | Cumple |
| 1.4.10 Reflujo | A 375 px, `scrollWidth = 375`: sin desplazamiento horizontal y tabla convertida en tarjetas. | Cumple |
| 1.4.11 Contraste de componentes | Borde de campo 4.74:1 y foco 4.72:1 (≥ 3:1). | Cumple |
| 2.1.1 Teclado / 2.1.2 Sin trampa | Tab recorre: saltar → sesión → simular → 7 campos → Buscar → Limpiar → «Ver detalle». El diálogo se abre con Enter y se cierra con Esc. | Cumple |
| 2.4.1 Saltar bloques | Enlace «Saltar a los resultados» (es el primer foco). | Cumple |
| 2.4.3 Orden del foco | El orden sigue el visual. Al cerrar el diálogo, el foco vuelve al botón que lo abrió (verificado). | Cumple |
| 2.4.7 Foco visible | `outline` sólido de 3 px `#b35c00`. | Cumple |
| 2.5.8 Tamaño del objetivo | `min-height: 44px` en campos y botones. | Cumple |
| 3.3.1 Identificación de errores | Resumen con `role=alert` que recibe el foco, mensaje por campo y `aria-invalid`. | Cumple (tras corregir F-04) |
| 3.3.2 Etiquetas o instrucciones | Pistas de formato (`modulo`, `accion`, `correlacion`). | Cumple parcialmente (ver F-06) |
| 4.1.2 Nombre, rol y valor | Botones «Ver detalle» con `aria-label` único por evento; diálogo con `aria-labelledby`. | Cumple |
| 4.1.3 Mensajes de estado | `role=status` en el total y la página. | Cumple parcialmente (ver F-07) |
| 3.1.1 Idioma | `<html lang="es">`. | Cumple |

## 3. Contraste calculado

| Par | Texto | Fondo | Relación |
|---|---|---|---|
| Texto | #1b1f24 | #ffffff | 16.56:1 |
| Texto atenuado | #4a5360 | #ffffff | 7.79:1 |
| Texto atenuado sobre fondo | #4a5360 | #f6f7f9 | 7.26:1 |
| Botón primario | #ffffff | #0b5cad | 6.67:1 |
| Error | #a4161a | #fdecec | 6.79:1 |
| Éxito | #1d6b33 | #e7f4ea | 5.78:1 |
| Advertencia | #7a4b00 | #fff4dc | 6.78:1 |
| Banner de demostración | #ffffff | #2b2f36 | 13.44:1 |
| Borde de campo | #6b7480 | #ffffff | 4.74:1 |
| Foco | #b35c00 | #ffffff | 4.72:1 |

## 4. Hallazgos

| Id | Severidad | Evidencia | Problema | Recomendación | Estado |
|---|---|---|---|---|---|
| F-01 | Alta | [s11-04-movil-375-tarjetas.jpg](../evidencias/s11-04-movil-375-tarjetas.jpg) | En el móvil, los resultados aparecen **después** de los 9 campos de filtro: hay que desplazarse aproximadamente una pantalla y media para ver el primer evento. | Filtros plegables en pantallas de 600 px o menos, con un resumen de «N filtros activos» (propuesta de la Semana 10). | Pendiente (backlog B-01) |
| F-02 | Media | [s11-02-acceso-denegado-403.jpg](../evidencias/s11-02-acceso-denegado-403.jpg) | Con 403 el formulario de filtros sigue visible, lo que contradice W5 y RV-01 (Semana 8): sugiere que el usuario puede hacer algo que no puede. | Ocultar la sección de filtros con 401 y 403. | **Corregido en la Semana 13** (H-GOB-10) |
| F-03 | Alta | Inspección: con 403 seguía visible «Página 1 de 3» | `.pager { display:flex }` anulaba el atributo `hidden`, así que la paginación de una consulta anterior quedaba en pantalla en los estados de error. | Regla global `[hidden]{display:none!important}`. | **Corregido** (`styles.css`). Verificado: `display=none` en `#pager` y `#table-wrap` con 403 |
| F-04 | Alta | Inspección: con «Hasta» anterior a «Desde» se mostraba «Sin conexión» | `list.append(el('li')).append(link)` lanzaba `TypeError` (`append` devuelve `undefined`). El 422 se presentaba como fallo de red y no se veía el resumen de errores. | Construir el `li` antes de agregarlo. | **Corregido** (`app.js`). Verificado: resumen visible y con foco ([s11-01](../evidencias/s11-01-error-validacion-422.jpg)) |
| F-05 | Media | [s11-03](../evidencias/s11-03-escritorio-resultados-detalle.jpg) | Las columnas «Usuario» y «Recurso» muestran identificadores crudos (UUID): cuesta reconocer a una persona o comparar filas. El filtro «Usuario» exige el identificador exacto. | Ampliar el contrato con `actor_display_name` (de `central_users.display_name`, por medio de Tenants) y un buscador de usuarios. | Pendiente (B-03), requiere Tenants |
| F-06 | Media | Código: `AuditFilterParser('America/Guatemala')` y `formatDate` | Las fechas de los filtros se interpretan en la zona del servidor y la tabla muestra la hora local **sin indicar la zona**. Un auditor en otra zona puede filtrar mal. | Rotular «Hora de Guatemala (UTC−6)» en la cabecera de la tabla y en la pista de «Desde/Hasta». | Pendiente (B-04) |
| F-07 | Baja | Código: `#state` no es región viva; `#status` sí | En 403 y 503, la región `role=status` solo anuncia el título («Acceso denegado»). La explicación y la referencia `trace_id` no se anuncian a un lector de pantalla. | Incluir el mensaje completo en `#status`, o dar a `#state` `role=alert` en los errores. | Pendiente (B-05) |

## 5. Backlog priorizado de mejoras

| Prioridad | Id | Mejora | Origen | Esfuerzo |
|---|---|---|---|---|
| 1 | B-02 | Ocultar los filtros con 401 y 403 | F-02 | Bajo, **hecho en la Semana 13** |
| 2 | B-01 | Filtros plegables en el móvil | F-01 | Medio |
| 3 | B-05 | Anunciar el mensaje de error completo | F-07 | Bajo |
| 4 | B-04 | Indicar la zona horaria | F-06 | Bajo |
| 5 | B-03 | Nombre visible del actor y buscador de usuarios | F-05 | Alto (contrato y Tenants) |
| — | — | F-03 y F-04 | Corregidos | — |
