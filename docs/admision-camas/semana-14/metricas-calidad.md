# Métricas de calidad — ASII-07

| Métrica | Cómo se mide | Meta |
|---|---|---|
| Invariante «una cama por paciente» | Pruebas de cama ocupada, en mantenimiento y paciente ya admitido | 100 % |
| Atomicidad | Rollback con la auditoría caída: la cama sigue libre y no queda episodio | Probado con PDO e InMemory |
| Paridad de adaptadores | La misma batería con PDO e InMemory | 100 % |
| Rutas con permiso | 2 de 2 (`admision.crear` y `camas.ver`) | 100 % |
| Altas duplicadas en producción | Admisiones duplicadas por reintentos | 0 (el cliente no reintenta el POST) |
| Defectos Alta abiertos | Del registro de la Semana 13 | 0 antes de la entrega |

Las métricas se revisan en cada *quality gate*; una meta incumplida abre un defecto según [gestion-defectos.md](./gestion-defectos.md).
