# Métricas de calidad — ASII-17

| Métrica | Cómo se mide | Meta |
|---|---|---|
| Reglas de dominio con prueba | 6 de 6 reglas de `LabTest` (muestra, tipo, unidad, orden de rangos, tipo cualitativo y vigencia) | 100 % |
| Paridad de adaptadores | La misma batería con PDO e InMemory | 100 % de la batería en ambos |
| Rutas con permiso | 2 de 2 (`laboratorio.catalogo.ver` y `.administrar`), propuesto en la Semana 7 | 100 % |
| Deriva del contrato | Rutas de `openapi.yaml` que no existen en el servidor | 0 |
| Tiempo de búsqueda | `GET /lab-tests?search=` con 2 000 versiones (RNF-LAB-05) | ≤ 300 ms p95 |
| Defectos Alta abiertos | Del registro de la Semana 13 | 0 antes de la entrega |

Las métricas se revisan en cada *quality gate*; una meta incumplida abre un defecto según [gestion-defectos.md](./gestion-defectos.md).
