# Traslados, altas y liberación de camas — Semana 10

## Ficha

- **Estudiante:** María Yamilet Lindo Pablo · **GitHub:** [`Yamilet1235`](https://github.com/Yamilet1235)
- **Módulo:** `ASII-08` · **Issue:** `Refs #9` · **Destino:** `develop`
- **Asignación:** adaptación de 320 a 430 px: 3–5 pantallas responsive, breakpoints y 2 escenarios móviles. Debe cubrir navegación, formularios, confirmaciones y recuperación ante conexión limitada.

## Contenido y cumplimiento

| Pedido | Evidencia | Estado |
|---|---|---|
| Breakpoints | [movilidad.md](./movilidad.md#breakpoints) | Cumplido |
| 3–5 pantallas responsive | 5 capturas reales a 320, 375 y 430 px | Cumplido |
| 2 escenarios móviles | Sin red y red lenta con resultado incierto: `evidencia/movil.mjs` 4/4 | Cumplido |
| Navegación, formularios, confirmaciones y recuperación | [movilidad.md](./movilidad.md) | Cumplido |
| Corrección de los hallazgos de la Semana 9 | #866; [salida de las sondas](./evidencia/salida-sondas.txt): 9 de 10 | H1–H4, H6 y H7 corregidos; H5 pendiente de integración |
| [Declaración de uso de IA](./DECLARACION_IA.md) | — | — |

## Resultados (21/10/2026)

| Prueba | Resultado |
|---|---|
| `node --test micro-his/traslados-altas/tests/ui-mensajes.test.mjs` | 7/7 |
| `evidencia/recorrido-ui.mjs` a 320, 375 y 430 px | 9/9 en cada ancho |
| `evidencia/movil.mjs` (375 px, red emulada) | 4/4 |
| Sondas de la Semana 9 repetidas | 9 de 10 cumplen |

El entorno es Chrome sin interfaz con la emulación de red de DevTools; **no se usó un dispositivo físico ni una red móvil real**.
