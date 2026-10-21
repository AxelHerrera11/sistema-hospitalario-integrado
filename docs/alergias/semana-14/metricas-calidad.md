# Métricas de calidad — ASII-12

| Métrica | Cómo se mide | Meta |
|---|---|---|
| Falla segura probada | Servidor y cliente devuelven `unavailable` sin base o sin red | 100 % (2 de 2 caminos) |
| Reglas de dominio con prueba | Alérgeno, tipo, severidad y los 4 niveles del banner | 100 % |
| Paridad de adaptadores | La misma batería con PDO e InMemory | 100 % |
| Rutas con permiso | 2 de 2 (`alergias.ver` y `alergias.registrar`) | 100 % |
| Deriva del contrato | Rutas de `openapi.yaml` que no existen en el servidor | 0 |
| Defectos Alta abiertos | Del registro de la Semana 13 | 0 antes de la entrega |

Las métricas se revisan en cada *quality gate*; una meta incumplida abre un defecto según [gestion-defectos.md](./gestion-defectos.md).
