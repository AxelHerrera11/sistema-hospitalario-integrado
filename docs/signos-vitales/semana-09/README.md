# Signos vitales y alertas por valores anormales — Semana 9: evaluación de usabilidad y accesibilidad

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-13 — Signos vitales y alertas por valores anormales · **Entrega:** checklist completado, hallazgos con evidencia y backlog priorizado.

## Ficha de la asignación

- **Semana:** Semana 9 — Evaluación del diseño, usabilidad y accesibilidad
- **Tarea individual:** Evalúe el flujo UX de Signos vitales y alertas por valores anormales con heurísticas de usabilidad y WCAG: teclado, foco, contraste, etiquetas, mensajes y prevención de errores. Priorice correcciones según impacto en signos vitales, unidades, umbrales, valores anormales y alertas clínicas.
- **Entregable / evidencia:** checklist completado, evidencia de al menos 6 hallazgos y backlog priorizado con corrección propuesta y criterio verificable.
- **Issue:** Refs #23
- **Alcance:** evaluación del diseño. **No se modifica código**: la pantalla aún no existe, así que se evalúan los [seis wireframes y el flujo de la Semana 8](../semana-08/README.md). Cada corrección queda como *propuesta*.

## Método

1. **Objeto evaluado:** el flujo «captura de signos vitales, evaluación de umbrales y generación de alerta» de la Semana 8 (`wireframes.md`, user flows 1 y 2, estados y reglas de interacción).
2. **Marcos:** las 10 heurísticas de Nielsen (H1–H10) y WCAG 2.2 nivel AA. Como los wireframes son de baja fidelidad, se evalúa lo que el diseño *especifica o deja sin especificar*: un criterio que el wireframe no define se marca ⚠️.
3. **Contraste:** razones calculadas con la fórmula de luminancia relativa de WCAG 2.x sobre fondo blanco con el script [`evidencia/contraste.php`](./evidencia/contraste.php). Los tonos son los de la paleta Tailwind 4; el repositorio aún no define los colores del módulo, así que la tabla fija el mínimo que deberán respetar. Resultado (PHP 8.3.33): **6 de 16 combinaciones no cumplen**.

   | Uso en el diseño | Color | Razón | Mínimo | Estado |
   |---|---|---|---|---|
   | «Advertencia» en ámbar 500 | `#F59E0B` | 2.15:1 | 4.5 | No cumple |
   | «Advertencia» en ámbar 700 | `#B45309` | 5.02:1 | 4.5 | Cumple |
   | «Sin valores fuera de umbral» en verde 600 | `#16A34A` | 3.30:1 | 4.5 | No cumple |
   | «Sin valores fuera de umbral» en verde 700 | `#15803D` | 5.02:1 | 4.5 | Cumple |
   | «No evaluado» en gris 400 | `#9CA3AF` | 2.54:1 | 4.5 | No cumple |
   | «No evaluado» en gris 600 | `#4B5563` | 7.56:1 | 4.5 | Cumple |
   | «Crítica» en rojo 600 | `#DC2626` | 4.83:1 | 4.5 | Cumple |
   | Borde de campo en gris 300 | `#D1D5DB` | 1.47:1 | 3.0 | No cumple |
   | Borde de campo en gris 500 | `#6B7280` | 4.83:1 | 3.0 | Cumple |
   | Insignia «Advertencia», texto blanco sobre ámbar 500 | `#FFFFFF` / `#F59E0B` | 2.15:1 | 4.5 | No cumple |
   | Insignia «Advertencia», texto negro sobre ámbar 500 | `#000000` / `#F59E0B` | 9.78:1 | 4.5 | Cumple |

4. **Severidad** (según el daño clínico posible): *Crítica* (puede ocultar o inventar una alerta, o asociar datos al paciente equivocado), *Alta* (el médico puede no ver una alerta o perder la medición), *Media* (degrada la tarea sin bloquearla), *Baja* (pulido).
5. **Prioridad del backlog:** P0 antes de implementar el formulario, P1 en la primera versión, P2 en la segunda, P3 deseable. Se ordena por impacto en signos vitales, unidades, umbrales, valores anormales y alertas.

## Checklist

Leyenda: ✅ cumple · ⚠️ cumple en parte o sin especificar · ❌ no cumple. El número remite a [hallazgos.md](./hallazgos.md).

### Teclado y foco

| # | Criterio | Ref. | Estado | Hallazgo |
|---|---|---|---|---|
| 1 | Todo el flujo se completa sin ratón | WCAG 2.1.1 | ⚠️ | H-04 |
| 2 | Sin trampas de teclado; los diálogos se cierran con `Esc` | 2.1.2 | ⚠️ | H-04 |
| 3 | El foco es visible en cada control | 2.4.7 / 2.4.11 | ⚠️ | H-06 |
| 4 | Tras enviar, el foco va al resultado | 2.4.3 / 3.2.2 | ❌ | H-04 |
| 5 | Al cerrar la alerta, el foco vuelve a un lugar lógico | 2.4.3 | ❌ | H-04 |
| 6 | Tras un error, el foco va al primer campo con problema | 3.3.1 | ✅ | — |
| 7 | Los controles se activan con una sola pulsación, sin gestos | 2.5.1 | ✅ | — |

### Contraste y color

| # | Criterio | Ref. | Estado | Hallazgo |
|---|---|---|---|---|
| 8 | Texto normal con 4.5:1 y texto grande con 3:1 | 1.4.3 | ❌ | H-06 |
| 9 | Bordes y estados de controles con 3:1 | 1.4.11 | ❌ | H-06 |
| 10 | El color no es la única señal de severidad | 1.4.1 | ✅ | — |
| 11 | «Sin alerta» y «no evaluado» se distinguen sin color | 1.4.1 / H1 | ⚠️ | H-06 |
| 12 | El texto se amplía al 200 % sin perder contenido | 1.4.4 | ⚠️ | H-11 |
| 13 | Reflujo a 320 px sin desplazamiento en dos ejes | 1.4.10 | ❌ | H-11 |

### Etiquetas e instrucciones

| # | Criterio | Ref. | Estado | Hallazgo |
|---|---|---|---|---|
| 14 | Cada campo tiene una etiqueta visible y programática | 1.3.1 / 3.3.2 | ⚠️ | H-07 |
| 15 | La unidad forma parte del nombre del campo | 1.3.1 / 2.5.3 | ❌ | H-07 |
| 16 | El destino de cada botón se entiende sin contexto | 2.4.4 | ✅ | — |
| 17 | El formato esperado se explica y es el del idioma | 3.3.2 / H2 | ❌ | H-09, H-02 |
| 18 | Los objetivos táctiles miden al menos 24 × 24 px | 2.5.8 | ⚠️ | H-11 |
| 19 | El idioma de la interfaz es uniforme | 3.1.2 / H2 | ✅ | — |

### Mensajes y estados

| # | Criterio | Ref. | Estado | Hallazgo |
|---|---|---|---|---|
| 20 | Los mensajes de error dicen qué pasó y qué hacer | 3.3.3 / H9 | ✅ | — |
| 21 | El resultado y los errores se anuncian a un lector de pantalla | 4.1.3 | ❌ | H-03 |
| 22 | La carga se anuncia y el esqueleto no muestra valores | 4.1.3 / H1 | ⚠️ | H-03 |
| 23 | Los errores se identifican en texto, junto al campo | 3.3.1 | ✅ | — |
| 24 | Los códigos técnicos acompañan al mensaje, no lo sustituyen | H2 | ✅ | — |
| 25 | El flujo no termina en un callejón sin salida | H9 / H7 | ❌ | H-12 |

### Prevención de errores

| # | Criterio | Ref. | Estado | Hallazgo |
|---|---|---|---|---|
| 26 | Confirmación antes de enviar datos clínicos | 3.3.4 | ✅ | — |
| 27 | El paciente correcto está a la vista al confirmar | H5 | ❌ | H-05 |
| 28 | La unidad no se asume en silencio y su cambio se nota | 3.3.4 / H5 | ❌ | H-01 |
| 29 | Un número escrito se interpreta como el usuario lo escribió | 3.3.1 / H5 | ❌ | H-02 |
| 30 | No se pierde el trabajo por salir, caducar o recargar | 2.2.1 / H5 | ❌ | H-08 |
| 31 | El valor por defecto de la fecha no induce un error | H5 | ⚠️ | H-09 |

### Dominio: unidades, umbrales y alertas

| # | Criterio | Ref. | Estado | Hallazgo |
|---|---|---|---|---|
| 32 | La alerta crítica no desaparece sola | 2.2.1 / H1 | ✅ | — |
| 33 | Los valores no salen en notificaciones, toasts ni URL | RNF-SV | ✅ | — |
| 34 | El texto no asume género a partir del nombre | H2 | ❌ | H-13 |

**Resumen:** 34 criterios · 11 ✅ · 9 ⚠️ · 14 ❌ → 13 hallazgos en [hallazgos.md](./hallazgos.md) y 13 tareas en [backlog.md](./backlog.md).

## Archivos

- [Hallazgos con evidencia](./hallazgos.md)
- [Backlog priorizado](./backlog.md)
- [Script de contraste](./evidencia/contraste.php): `php docs/signos-vitales/semana-09/evidencia/contraste.php`
- [Declaración de uso de IA](./DECLARACION_IA.md)
