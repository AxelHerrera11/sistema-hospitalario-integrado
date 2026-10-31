# Semana 09 — Evaluación del diseño, usabilidad y accesibilidad

**Tarea:** evaluar el flujo UX con heurísticas de usabilidad y WCAG (teclado, foco, contraste, etiquetas, mensajes y
prevención de errores) y priorizar correcciones según su impacto en expediente longitudinal, episodios clínicos,
acceso autorizado, integridad y datos sensibles.

## Entregables

| Archivo | Contenido |
|---|---|
| [`checklist-evaluacion.md`](checklist-evaluacion.md) | Checklist completado: 14 criterios WCAG, 10 heurísticas y 3 controles de datos sensibles |
| [`hallazgos-backlog.md`](hallazgos-backlog.md) | 8 hallazgos con evidencia y backlog priorizado con corrección y criterio verificable |
| [`evidencias/contraste.txt`](evidencias/contraste.txt) · [`herramientas/contraste.mjs`](herramientas/contraste.mjs) | Contraste calculado de 16 pares de color |
| [`evidencias/auditoria-navegador.json`](evidencias/auditoria-navegador.json) | Orden de tabulación, etiquetas, encabezados y comportamiento tras un código inválido |

## Cómo verificar

```bash
node docs/expedientes-medicos/semana-09/herramientas/contraste.mjs
```

Para repetir la auditoría de navegador: levantar el prototipo (README del micro-HIS), consultar P-0001 como la
Dra. López, escribir «P 0002» y pulsar «Consultar»; antes de la Semana 12 el expediente de P-0001 seguía visible.

## Estado

4 hallazgos corregidos en la Semana 12 (H-01, H-02, H-03, H-04); H-05, H-07 y H-08 esperan decisiones externas;
H-06 aceptado. No se hizo prueba con lector de pantalla real (pendiente manual).
