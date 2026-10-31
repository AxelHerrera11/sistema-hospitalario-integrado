# Órdenes de laboratorio desde EMR — Semana 9: usabilidad y accesibilidad

**Módulo:** Órdenes de laboratorio desde EMR (ASII-16) · **Entrega académica:** Fajardo López, Javier Alexander (`Javifa2324`, ASII-09)
**Objeto evaluado:** wireframes de la [semana 8](../semana-08/README.md) y la vista HTML
`micro-his/ordenes-laboratorio/src/Presentation/LabOrderView.php`.
**Método:** inspección heurística de Nielsen + lista WCAG 2.2 AA + revisión de código. **No** se
ejecutó prueba con usuarios ni lector de pantalla; se declara como limitación.

## 1. Checklist heurístico (Nielsen)

| # | Heurística | Resultado | Evidencia |
|---|---|---|---|
| H1 | Visibilidad del estado | ✔ | Bandeja con estados, detalle con historial, confirmación con resumen |
| H2 | Lenguaje del usuario | ⚠ | «Idempotency-Key» aparece en la ayuda de confirmación |
| H3 | Control y libertad | ⚠ | Cancelar existe, pero no hay wireframe de confirmación de cancelación |
| H4 | Consistencia y estándares | ✔ | Mismos estados y acciones en bandeja, detalle y confirmación |
| H5 | Prevención de errores | ⚠ | El contexto es de solo lectura, pero no se bloquea doble envío en el wireframe |
| H6 | Reconocer antes que recordar | ✔ | Resumen de confirmación con paciente, episodio y pruebas |
| H7 | Flexibilidad y eficiencia | ⚠ | Falta atajo para repetir una orden previa del mismo episodio |
| H8 | Diseño estético y minimalista | ✔ | Jerarquía clara; una acción primaria por pantalla |
| H9 | Ayuda para reconocer y recuperarse de errores | ✔ | Error con código, texto y `request_id`; datos conservados |
| H10 | Ayuda y documentación contextual | ⚠ | No hay ayuda sobre qué significa cada estado |

## 2. Checklist WCAG 2.2 AA (selección)

| Criterio | Nivel | Resultado | Observación |
|---|---|---|---|
| 1.1.1 Contenido no textual | A | ⚠ | Los wireframes tienen `title`; el prototipo debe tener `alt` en iconos |
| 1.3.1 Información y relaciones | A | ✘ | `LabOrderView` usa `<table>` sin `<caption>` ni `scope` |
| 1.4.1 Uso del color | A | ✔ | Estado con texto + icono, no solo color |
| 1.4.3 Contraste mínimo | AA | ⚠ | Texto secundario gris claro en varios wireframes; verificar 4.5:1 |
| 1.4.10 Reflow | AA | ✘ | La bandeja es una tabla de 6 columnas; a 320 px desborda |
| 2.1.1 Teclado | A | ⚠ | Foco anotado, pero falta orden explícito y skip link |
| 2.4.3 Orden del foco | A | ⚠ | El modal de confirmación no define trampa/retorno de foco |
| 2.4.7 Foco visible | AA | ⚠ | Anotado, no especificado en CSS |
| 2.5.8 Tamaño del objetivo | AA | ✘ | El botón «+» de resultados mide ~18 px |
| 3.3.1 Identificación del error | A | ✔ | Código + texto + campo afectado |
| 3.3.2 Etiquetas o instrucciones | A | ✘ | La búsqueda de pruebas usa solo placeholder |
| 3.3.3 Sugerencia ante error | AA | ✔ | «Quitar prueba» y sugerencias activas |
| 3.3.7 Entrada redundante | A | ✔ | Paciente/episodio se toman del expediente |
| 4.1.3 Mensajes de estado | AA | ⚠ | `role="alert"` anotado; falta `aria-live` en el prototipo |

## 3. Hallazgos priorizados

| ID | Severidad | Hallazgo | Evidencia | Impacto | Corrección propuesta | Criterio verificable |
|---|---|---|---|---|---|---|
| UX-01 | Alta | Tabla de bandeja sin alternativa a 320 px | `01-bandeja.svg` (6 columnas) | Horizontal scroll y pérdida de acciones en móvil | Convertir filas en tarjetas con etiquetas; conservar tabla en ≥768 px | Sin scroll horizontal a 320 px; todas las acciones visibles |
| UX-02 | Alta | Modal de confirmación sin trampa ni retorno de foco | `03-confirmacion.svg` anotación B | Usuario de teclado puede salir del modal o perderse | `role="dialog"`, `aria-modal`, foco inicial, Tab cíclico, Escape y retorno al botón | Prueba solo teclado: foco no sale del diálogo y vuelve al origen |
| UX-03 | Alta | Cancelación sin confirmación ni motivo visible | `04-detalle.svg` anotación C | Cancelación accidental de una orden clínica | Diálogo de confirmación con motivo obligatorio y consecuencia explícita | Cancelar exige motivo ≥5 caracteres; no es la acción por defecto |
| UX-04 | Alta | Búsqueda de pruebas solo con placeholder | `02-emision.svg` | Lector de pantalla no anuncia el propósito; el texto desaparece al escribir | `<label>` visible o `aria-label` + texto de ayuda persistente | Axe/Lighthouse sin error de etiqueta; label asociado por `for/id` |
| UX-05 | Media | Texto secundario con contraste límite | Wireframes: `#64748b` sobre `#f3f4f6` | Dificultad de lectura para baja visión | Usar `#475569` o más oscuro para texto pequeño | Contraste ≥4.5:1 medido en todos los textos <18 pt |
| UX-06 | Media | Objetivo «+» menor a 24×24 px | `02-emision.svg` | Dificultad para pulsar en móvil o con temblor | Área táctil ≥44×44 px aunque el icono sea pequeño | Inspección CSS: `min-width/height: 44px` en el botón |
| UX-07 | Media | Jerga técnica en la confirmación | «clave de idempotencia» | El médico no necesita conocer el mecanismo interno | «Si reintentas, no se duplicará la orden» | Prueba de comprensión: ≥90% explica el mensaje |
| UX-08 | Media | Estados sin ayuda contextual | Bandeja y detalle | El usuario no sabe qué puede hacer en `RESULTED` | Tooltip/ayuda «¿Qué significa este estado?» con texto y siguiente acción | Cada estado tiene descripción y acción permitida en la UI |
| UX-09 | Baja | Sin atajo para repetir una orden previa | Bandeja | Más pasos en controles recurrentes | Acción «Repetir orden» que precarga pruebas del mismo episodio | Orden repetida en ≤3 pasos, con confirmación |
| UX-10 | Baja | `request_id` no se puede copiar | `05-error.svg` | Soporte pide el código y el usuario lo transcribe | Botón «Copiar código» con confirmación accesible | Copia al portapapeles y anuncia «Copiado» |

## 4. Backlog priorizado

| Orden | ID | Corrección | Semana | Responsable | Estado |
|---|---|---|---|---|---|
| 1 | UX-01 | Bandeja responsive con tarjetas a 320–430 px | 10–11 | Javier | Propuesto |
| 2 | UX-02 | Diálogo accesible con foco gestionado | 11 | Javier | Propuesto |
| 3 | UX-03 | Confirmación de cancelación con motivo | 11 | Javier | Propuesto |
| 4 | UX-04 | Etiqueta persistente en búsqueda | 11 | Javier | Propuesto |
| 5 | UX-05 | Ajustar paleta a contraste AA | 11 | Javier | Propuesto |
| 6 | UX-06 | Objetivos táctiles ≥44 px | 10–11 | Javier | Propuesto |
| 7 | UX-07 | Reescribir mensajes sin jerga | 11 | Javier | Propuesto |
| 8 | UX-08 | Ayuda contextual por estado | 12 | Javier | Propuesto |
| 9 | UX-09 | Acción «Repetir orden» | Posterior | Javier | Fuera de alcance |
| 10 | UX-10 | Copiar `request_id` | 11 | Javier | Propuesto |

## 5. Corrección concreta en código

`LabOrderView` debe declarar la tabla con título y encabezados asociados:

```php
// antes
'<table><thead><tr><th>Código</th><th>Prueba</th></tr></thead>...'
// después
'<table><caption>Pruebas solicitadas</caption><thead><tr>'
. '<th scope="col">Código</th><th scope="col">Prueba</th></tr></thead>...'
```

Criterio: un lector de pantalla anuncia «Pruebas solicitadas, tabla de 2 columnas» y cada celda
identifica su encabezado. Se aplicará en el prototipo de la semana 11.

## 6. Limitaciones

- La evaluación es heurística y de código; no sustituye una prueba con usuarios reales.
- No se ejecutó un lector de pantalla (NVDA/VoiceOver) ni una auditoría automatizada completa.
- Los porcentajes de contraste deben medirse con una herramienta antes de declarar conformidad AA.
- Los hallazgos se basan en los wireframes de la semana 8, no en el prototipo de la semana 11.

## 7. Declaración de IA

[`DECLARACION_IA.md`](./DECLARACION_IA.md), con validación humana pendiente.
