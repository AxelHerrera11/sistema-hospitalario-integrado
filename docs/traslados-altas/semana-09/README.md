# Traslados, altas y liberación de camas — Semana 9

## Ficha

- **Estudiante:** María Yamilet Lindo Pablo · **GitHub:** [`Yamilet1235`](https://github.com/Yamilet1235)
- **Módulo:** `ASII-08` · **Issue:** `Refs #9` · **Destino:** `develop`
- **Asignación:** checklist de heurísticas y WCAG (teclado, foco, contraste, etiquetas, mensajes y prevención de errores), al menos 6 hallazgos reales con evidencia y un backlog priorizado. Sin inventar defectos, mediciones ni pruebas con usuarios.

## Contenido y cumplimiento

| Pedido | Evidencia | Estado |
|---|---|---|
| Checklist de heurísticas y WCAG | [checklist.md](./checklist.md): 14 criterios | Cumplido |
| ≥ 6 hallazgos reales con evidencia | [hallazgos.md](./hallazgos.md): **7**, cada uno con una sonda que no cumple | Cumplido |
| Backlog priorizado | [hallazgos.md](./hallazgos.md#backlog) | Cumplido |
| Evidencia reproducible | [evidencia/sondas-ux.mjs](./evidencia/sondas-ux.mjs) y [salida-sondas.txt](./evidencia/salida-sondas.txt) | 10 sondas: 3 cumplen y 7 no |
| [Declaración de uso de IA](./DECLARACION_IA.md) | — | — |

**No se realizó:** ninguna prueba con usuarios ni con lector de pantalla real. Los criterios de lectores de pantalla se evalúan por atributos del DOM.

## Reproducir

Levante el prototipo como en la [Semana 8](../semana-08/README.md#cómo-reproducir), con una base recién sembrada, y ejecute `node evidencia/sondas-ux.mjs http://127.0.0.1:<puerto>` (requiere puppeteer-core y Chrome).
