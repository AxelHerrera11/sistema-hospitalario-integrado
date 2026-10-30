# Semana 9 — Evaluación del diseño, usabilidad y accesibilidad

**Entregable:** checklist completado, evidencia de al menos 6 hallazgos y backlog priorizado con corrección propuesta y criterio verificable.

**Método (21-oct-2026):** el prototipo de `micro-his/qa-calidad-versiones/public/prototipo` se evaluó en Chromium (navegador integrado) con scripts de inspección del DOM (etiquetas, nombres accesibles, `aria-describedby`, foco activo, contraste calculado con la fórmula de luminancia relativa de WCAG) y con capturas de Chrome *headless*. Mediciones: [evidencias/mediciones.md](evidencias/mediciones.md).

## Checklist (heurísticas de Nielsen + WCAG 2.1 AA)

| # | Criterio | Resultado inicial | Tras corrección |
|---|---|---|---|
| 1 | Visibilidad del estado del sistema (carga, procesando) | Parcial: sin estado «procesando» en acciones | Cumple (H-02) |
| 2 | Lenguaje del usuario (mensajes sin jerga técnica) | Cumple | Cumple |
| 3 | Control y libertad (cancelar, volver) | Cumple: diálogo con Cancelar, `Esc`, «Volver» | Cumple |
| 4 | Consistencia (mismos estados y textos en todas las vistas) | Parcial: hallazgo con texto duplicado | Cumple (H-08) |
| 5 | Prevención de errores | **No cumple**: doble envío y `bytes` inventado | Cumple (H-02, H-03) |
| 6 | Ayuda y documentación contextual | Cumple: ayuda plegable en el plan y texto por veredicto | Cumple |
| 7 | 2.1.1 Teclado: todo operable sin ratón | Cumple (controles nativos, diálogo nativo) | Cumple |
| 8 | 2.4.3 Orden del foco / 2.4.7 foco visible | Parcial: el foco saltaba al inicio tras cada acción; franja de foco en `<main>` | Cumple (H-06, H-07) |
| 9 | 1.4.3 Contraste de texto ≥ 4.5:1 | Cumple (mínimo medido 6.72:1) | Cumple |
| 10 | 1.4.11 Contraste de componentes ≥ 3:1 | **No cumple**: borde de campos 1.57:1 | Cumple: 4.46:1 (H-01) |
| 11 | 1.3.1 / 3.3.1 Etiquetas y errores asociados | Parcial: 3 mensajes sin `aria-describedby` | Cumple (H-04, H-05) |
| 12 | 3.3.3 Sugerencia de corrección | Cumple: cada error dice cómo corregir | Cumple |
| 13 | 4.1.2 Nombre, rol y valor | Cumple: 0 controles sin nombre | Cumple |
| 14 | 3.1.1 Idioma de la página | Cumple (`lang="es"`) | Cumple |
| 15 | Color no es el único medio (insignias) | Cumple: el veredicto siempre tiene texto | Cumple |

## Hallazgos con evidencia y backlog priorizado

Prioridad por impacto en la integridad de ejecuciones, evidencia y quality gates (P1 > P2 > P3).

| ID | Hallazgo (evidencia) | Impacto | Prioridad | Corrección aplicada | Criterio verificable | Estado |
|---|---|---|---|---|---|---|
| H-02 | Doble clic en «Evaluar quality gate» envió **2** POST → 2 decisiones | Historial de decisiones duplicado | P1 | `busy()` deshabilita los botones mientras la petición está en curso | Doble clic → 1 POST a `gate-decisions` (medido: 1) | Corregido |
| H-03 | La UI enviaba `bytes: 1` en toda evidencia (medido: `[1, 1]`) | Metadato de evidencia falso (RNF-03) | P1 | Campo «Tamaño (bytes)» obligatorio + validación | Resultado guardado con `bytes` reales (medido: `[2370, 911]`); VM03 | Corregido |
| H-07 | `.alert{display:flex}` anulaba `hidden`: el aviso rosado con «Reintentar» se veía siempre (captura previa) | Falso aviso de error en todas las pantallas | P1 | `[hidden]{display:none !important}` | Sin error, el aviso no se ve (capturas d1, m1) | Corregido |
| H-01 | Borde de campos `#c9cfd8` sobre blanco = **1.57:1** | No se distinguen los controles (1.4.11) | P2 | `--border: #6f7886`; separadores decorativos con `--divider` | Contraste del borde ≥ 3:1 (medido 4.46:1) | Corregido |
| H-04 | Error del grupo de suites: foco quedaba en `main`, `fieldset` sin `aria-describedby` | El lector de pantalla no anuncia por qué no se guardó | P2 | `aria-describedby="suites-error"` y foco al primer nombre de suite | Tras enviar un plan sin E2E obligatoria, foco en `.s-name` (medido) | Corregido |
| H-05 | `r-total` y `r-failed` sin `aria-describedby` hacia su error | Mensaje no asociado al campo | P2 | Atributos agregados; error de evidencia asociado a ruta, bytes y SHA | Script: 0 campos con mensaje de error sin asociar | Corregido |
| H-06 | Tras cada acción el foco volvía a `main` | Usuario de teclado pierde su lugar | P2 | El foco va al panel del gate tras evaluar y al título tras otras acciones | Tras «Evaluar», `document.activeElement.id === 'gate-panel'` (medido) | Corregido |
| H-08 | Hallazgo mostraba texto repetido («No se registró resultado. No se registró resultado…») | Ruido al leer el veredicto | P3 | Se muestra severidad · suite: detalle; el texto general queda en `title` | Captura d6 sin repetición | Corregido |
| H-09 | Cambiar «Perfil» recarga la vista al instante (3.2.2) | Cambio de contenido sin botón | P3 | Aceptado: es el selector de contexto de la demo; en Laravel la sesión no se elige | — | Aceptado con justificación |

Los hallazgos H-01..H-08 se corrigieron en los archivos del propio módulo (`styles.css`, `index.html`, `app.js`, `view-model.js`). No se revisó con lector de pantalla real (NVDA/JAWS): queda como verificación pendiente.
