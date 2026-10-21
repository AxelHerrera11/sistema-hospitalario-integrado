# Métricas de calidad — ASII-17

| Métrica | Cómo se mide | Meta |
|---|---|---|
| Reglas de dominio con prueba | Hoy 5 de 7 caminos de `LabTest` (obligatorios, muestra, orden de rangos, cualitativa con rangos y vigencia); faltan tipo no soportado y numérica sin unidad, que se agregan en la Semana 15 | 100 % |
| Paridad de adaptadores | La misma batería con PDO e InMemory | 100 % de la batería en ambos |
| Rutas con permiso | 2 de 2 (`laboratorio.catalogo.ver` y `.administrar`), propuesto en la Semana 7 | 100 % |
| Deriva del contrato | Rutas de `openapi.yaml` que no existen en el servidor | 0 |
| Tiempo de búsqueda | `GET /lab-tests?search=` con 2 000 versiones (RNF-LAB-05) | ≤ 300 ms p95 |
| Defectos Alta abiertos | Del registro de la Semana 13 | 0 antes de la entrega |

Las métricas se revisan en cada *quality gate*; una meta incumplida abre un defecto según [gestion-defectos.md](./gestion-defectos.md).
