# Criterios de aceptación y *quality gates*

| Gate | Cuándo | Condición para pasar |
|---|---|---|
| G1 · PR | Al abrir el PR | CI «Validate ASII PR» en verde; menos de 400 líneas; plantilla completa; pruebas con código 0 pegadas en el PR |
| G2 · Contrato | Si cambia la API | `openapi.yaml` sin advertencias y la prueba «cada ruta del contrato existe en el servidor» en verde |
| G3 · Revisión | Antes del merge | Checklist de la Semana 13 sin hallazgos **Alta** abiertos |
| G4 · Integración | Al integrar en Laravel | La misma prueba de contrato corre con el adaptador Eloquent contra PostgreSQL |
| G5 · Entrega | Semana 16 | Regresión manual de Madelin y E2E de Cindy sin defectos **Alta** |

**Definición de terminado de una historia:** pasa G1 a G3, la documentación coincide con el código y lo no implementado está marcado como «propuesto».
