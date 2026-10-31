# Notas SOAP y diagnósticos — Semana 9: evaluación de usabilidad y accesibilidad

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-11 — Notas SOAP y diagnósticos · **Entrega:** checklist completado, hallazgos con evidencia y backlog priorizado.

## Ficha de la asignación

- **Semana:** Semana 9 — Evaluación del diseño, usabilidad y accesibilidad
- **Tarea individual:** Evalúe el flujo UX de Notas SOAP y diagnósticos con heurísticas de usabilidad y WCAG: teclado, foco, contraste, etiquetas, mensajes y prevención de errores. Priorice correcciones según impacto en notas SOAP, diagnósticos, autoría clínica, versionado y expediente.
- **Entregable / evidencia:** checklist completado, al menos 6 hallazgos con evidencia y backlog priorizado con corrección propuesta y criterio verificable.
- **Issue:** Refs #22
- **Alcance:** evaluación del diseño. **No se modifica código**: la pantalla aún no existe, así que se evalúan los [seis wireframes y el flujo de la Semana 8](../semana-08/README.md). Cada corrección queda como *propuesta* con su criterio de aceptación.

## Método

1. **Objeto evaluado:** el flujo «registro de nota SOAP y asociación de diagnóstico» de la Semana 8 (`wireframes.md`, user flow 1 y 2, estados y reglas de interacción).
2. **Marcos:** las 10 heurísticas de Nielsen (H1–H10) y WCAG 2.2 nivel AA. Como los wireframes son de baja fidelidad, se evalúa lo que el diseño *especifica o deja sin especificar*: un criterio que el wireframe no define se marca como brecha, porque el desarrollo lo resolverá sin guía.
3. **Contraste:** razones calculadas con la fórmula de luminancia relativa de WCAG 2.x sobre fondo blanco (`#FFFFFF`):

   | Color | Uso previsto en el diseño | Razón | Texto normal (4.5:1) |
   |---|---|---|---|
   | `#F59E0B` | contador en ámbar | 2.15:1 | No cumple |
   | `#9CA3AF` | esqueleto, `trace_id` en gris claro | 2.54:1 | No cumple |
   | `#6B7280` | texto secundario | 4.83:1 | Cumple |
   | `#B45309` | ámbar corregido | 5.02:1 | Cumple |
   | `#92400E` | ámbar oscuro | 7.09:1 | Cumple (AAA) |

   Los tonos son los típicos de una paleta Tailwind: el repositorio todavía no define los colores del módulo, por lo que la tabla fija el mínimo que deberá respetar.
4. **Severidad:** *Crítica* (puede asociar datos clínicos al paciente equivocado), *Alta* (bloquea una tarea o puede perder la nota), *Media* (degrada la tarea sin bloquearla), *Baja* (pulido).
5. **Prioridad del backlog:** P0 antes de implementar el editor, P1 en la primera versión, P2 en la segunda, P3 deseable. Se ordena por impacto en nota, diagnóstico, autoría, versionado y expediente.

## Checklist

Leyenda: ✅ cumple · ⚠️ cumple en parte o sin especificar · ❌ no cumple. El número remite a [hallazgos.md](./hallazgos.md).

### Teclado y foco

| # | Criterio | Ref. | Estado | Hallazgo |
|---|---|---|---|---|
| 1 | Todo el flujo se completa sin ratón | WCAG 2.1.1 | ⚠️ | H-02 |
| 2 | Sin trampas de teclado; los diálogos se cierran con `Esc` | 2.1.2 | ⚠️ | H-01 |
| 3 | Orden de foco acorde con el orden visual | 2.4.3 | ⚠️ | H-01 |
| 4 | El foco es visible en cada control | 2.4.7 / 2.4.11 | ⚠️ | H-01 |
| 5 | El foco vuelve al disparador al cerrar un diálogo | 2.4.3 | ❌ | H-01 |
| 6 | Tras un error, el foco va al primer campo con problema | 3.3.1 | ⚠️ | H-03 |
| 7 | Los controles se pueden activar con una sola pulsación, sin gestos | 2.5.1 | ✅ | — |

### Contraste y color

| # | Criterio | Ref. | Estado | Hallazgo |
|---|---|---|---|---|
| 8 | Texto normal con 4.5:1 y texto grande con 3:1 | 1.4.3 | ❌ | H-04 |
| 9 | Bordes y estados de controles con 3:1 | 1.4.11 | ⚠️ | H-04 |
| 10 | El color no es la única señal (estado, contador) | 1.4.1 | ❌ | H-04, H-10 |
| 11 | El texto se amplía al 200 % sin perder contenido | 1.4.4 | ⚠️ | H-06 |
| 12 | Reflujo a 320 px sin desplazamiento en dos ejes | 1.4.10 | ⚠️ | H-05 |

### Etiquetas e instrucciones

| # | Criterio | Ref. | Estado | Hallazgo |
|---|---|---|---|---|
| 13 | Cada campo tiene una etiqueta visible y programática | 1.3.1 / 3.3.2 | ⚠️ | H-05 |
| 14 | El nombre accesible contiene el texto visible del control | 2.5.3 | ⚠️ | H-05 |
| 15 | Los botones tienen un nombre único y consistente | 2.4.6 / H4 | ❌ | H-05, H-11 |
| 16 | Los campos obligatorios se explican, no solo se marcan con `*` | 3.3.2 | ⚠️ | H-05 |
| 17 | El idioma de la interfaz es uniforme | 3.1.2 / H2 | ❌ | H-02 |
| 18 | El destino de cada enlace o botón se entiende sin contexto | 2.4.4 | ✅ | — |

### Mensajes y estados

| # | Criterio | Ref. | Estado | Hallazgo |
|---|---|---|---|---|
| 19 | Los mensajes de error dicen qué pasó y qué hacer | 3.3.3 / H9 | ✅ | — |
| 20 | Los cambios de estado se anuncian a un lector de pantalla | 4.1.3 | ❌ | H-06 |
| 21 | Los errores se identifican en texto, junto al campo | 3.3.1 | ⚠️ | H-03 |
| 22 | El estado del sistema siempre es visible (carga, envío, éxito) | H1 | ⚠️ | H-06 |
| 23 | Los códigos técnicos no sustituyen al mensaje | H2 | ⚠️ | H-06 |

### Prevención de errores

| # | Criterio | Ref. | Estado | Hallazgo |
|---|---|---|---|---|
| 24 | Confirmación antes de enviar datos clínicos | 3.3.4 | ⚠️ | H-07 |
| 25 | El paciente correcto está a la vista al confirmar | H5 | ❌ | H-07 |
| 26 | No se pierde el trabajo por salir, caducar o recargar | 2.2.1 / H5 | ❌ | H-08 |
| 27 | Las opciones imposibles no se ofrecen (segundo principal) | H5 | ❌ | H-12 |
| 28 | Las acciones destructivas tienen salida segura | H3 | ⚠️ | H-01 |
| 29 | La nota firmada ofrece un camino para corregir | H3 | ❌ | H-09 |
| 30 | El orden del flujo es coherente (nota → diagnóstico) | H4 | ❌ | H-11 |

### Dominio: autoría, versionado y expediente

| # | Criterio | Ref. | Estado | Hallazgo |
|---|---|---|---|---|
| 31 | La autoría se muestra con nombre legible y sin supuestos | H2 | ⚠️ | H-10 |
| 32 | La versión vigente se distingue de las anteriores | H1 | ❌ | H-09 |
| 33 | El expediente sale del contexto, sin selector manual | H5 | ✅ | — |
| 34 | El texto clínico no se guarda en almacenamiento persistente | RNF-SOAP-05 | ✅ | — |

**Resumen:** 34 criterios · 5 ✅ · 17 ⚠️ · 12 ❌ → 12 hallazgos en [hallazgos.md](./hallazgos.md) y 12 tareas en [backlog.md](./backlog.md).

## Archivos

- [Hallazgos con evidencia](./hallazgos.md)
- [Backlog priorizado](./backlog.md)
- [Declaración de uso de IA](./DECLARACION_IA.md)
