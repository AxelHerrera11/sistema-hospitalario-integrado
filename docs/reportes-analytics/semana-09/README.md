# ASII-23 Semana 9 — Evaluación de usabilidad y accesibilidad

**Issue:** `Refs #152`. Alcance: la pantalla de reportes y su entrada en el menú, con WCAG 2.1 AA como referencia.

**Método:** revisión con Chrome headless y pruebas automáticas, sin usuarios reales. Los hallazgos salen del recorrido de la auditoría del 30/10/2026.

## Checklist

| Criterio | Comprobación | Resultado |
|---|---|---|
| 1.3.1 Información y relaciones | `label for`, `h1`, ayuda con `aria-describedby` | Cumple (`reportes.a11y.test.mjs`) |
| 1.4.1 Uso del color | Los errores llevan texto | Cumple |
| 1.4.3 Contraste | [`evidencia/contraste.mjs`](./evidencia/contraste.mjs) | Cumple; ver [`evidencia/salida-contraste.txt`](./evidencia/salida-contraste.txt) |
| 1.4.10 Ajuste | Sin desplazamiento horizontal a 320 px | Cumple (`semana-10/evidencia/movilidad.mjs`) |
| 1.4.11 Contraste no textual | Borde de campos y foco | Cumple |
| 2.1.1 Teclado | Formulario y botón nativos | Cumple por estructura; sin prueba completa con teclado |
| 2.4.7 Foco visible | `:focus-visible` con contorno | Cumple en CSS |
| 2.5.5 Objetivo táctil | 44 px | Cumple (`recorrido-e2e.mjs`) |
| 3.3.1 Identificación de errores | `role="alert"` y texto | Cumple |
| 4.1.2 Nombre, función y valor | Controles nativos | Cumple por estructura |
| Lector de pantalla | NVDA o TalkBack | **No probado** |

## Hallazgos y backlog

| ID | Hallazgo | Severidad | Corrección | Criterio de cierre | Estado |
|---|---|---|---|---|---|
| H1 | La cifra anterior queda junto al error de un periodo inválido | Alta | `limpiar()` antes de validar | E2E sin cifra tras invertir | Corregido, #1565 |
| H2 | Los campos no tienen borde y parecen texto | Alta | Estilos propios | Borde de 1 px o más medido | Corregido, #1565 |
| H3 | «Calcular» parece texto | Alta | Botón con fondo | Fondo medido en E2E | Corregido, #1565 |
| H4 | No se avisa que «Hasta» no se incluye | Alta | Etiqueta y ayuda con ejemplo | a11y y E2E | Corregido, #1565 |
| H5 | «Indica desde y hasta.» aparece al cargar | Media | Se valida al calcular | E2E sin error inicial | Corregido, #1565 |
| H6 | El 403 repite «No tienes permiso» | Media | Un solo mensaje | Revisión del código | Corregido, #1565 |
| H7 | Cambiar un filtro deja el resultado anterior | Media | `watch` de los filtros | E2E | Corregido, #1565 |
| H8 | Otro hospital se confunde con falta de permiso | Media | Mensaje propio | E2E | Corregido, #1565 |
| H9 | La hora del cálculo sale en formato ISO | Baja | Formato local legible | Prueba del formateador | Abierto |
| H10 | El resultado no repite el periodo calculado | Baja | Mostrar desde y hasta | E2E | Abierto |
| H11 | Sin prueba con lector de pantalla | Media | NVDA o TalkBack | Registro de la prueba | Requiere a la estudiante |

## Reproducir

```bash
node resources/js/modules/reportes/reportes.a11y.test.mjs
node docs/reportes-analytics/semana-09/evidencia/contraste.mjs
```
