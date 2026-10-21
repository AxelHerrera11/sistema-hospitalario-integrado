# Gobernanza y Auditoría — Semana 9: usabilidad y accesibilidad

## Objetivo

Evaluar la experiencia propuesta (wireframes de la Semana 8 y prototipo de la Semana 11) frente a los criterios WCAG 2.2 AA aplicables y frente a heurísticas de usabilidad. Documentar los hallazgos con severidad, evidencia y recomendación, y un backlog priorizado.

## Entregables

| Entregable | Archivo |
|---|---|
| Revisión WCAG aplicable (teclado, etiquetas, contraste, errores) | [evaluacion-usabilidad-accesibilidad.md §2–3](evaluacion-usabilidad-accesibilidad.md) |
| Comprensión de filtros y resultados | Hallazgos F-05 y F-06 |
| 7 hallazgos con severidad, evidencia y recomendación | [§4](evaluacion-usabilidad-accesibilidad.md#4-hallazgos) |
| Backlog priorizado | [§5](evaluacion-usabilidad-accesibilidad.md#5-backlog-priorizado-de-mejoras) |

## Archivos creados o modificados

- **Creados:** `README.md` y `evaluacion-usabilidad-accesibilidad.md`.
- **Corregidos a raíz de F-03 y F-04:** `micro-his/gobernanza-auditoria/public/prototipo/styles.css` y `app.js`.

## Relación con semanas anteriores

Evalúa W1–W6 de la Semana 8, implementados en el prototipo (Semana 11). La evaluación se hizo en paralelo con la construcción del prototipo.

## Instrucciones

Levantar el prototipo y repetir el recorrido con teclado indicado en el §1:

```bash
php -S 127.0.0.1:8091 -t micro-his/gobernanza-auditoria/public micro-his/gobernanza-auditoria/public/index.php
```

## Pruebas ejecutadas y resultados reales

- **Recorrido con teclado:** sin trampas de foco; el foco vuelve al botón al cerrar el diálogo con Esc.
- **Contraste:** 10 pares, todos ≥ 4.5:1 (texto) o ≥ 3:1 (componentes).
- **Reflujo a 375 px:** sin desplazamiento horizontal.
- **Estados:** 200, 403, 422, 503, vacío y cargando, verificados en el prototipo.

## Dependencias pendientes

F-05 depende de Tenants (nombre visible del actor).

## Limitaciones conocidas

No hubo usuarios reales, lectores de pantalla ni herramientas automáticas. Los resultados corresponden únicamente a la inspección descrita.
