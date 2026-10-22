# Métricas de calidad — ASII-21

| Métrica | Cómo se mide | Meta |
|---|---|---|
| Idempotencia | El evento repetido devuelve `duplicate` y no crea otra alerta | 100 % |
| Acuse único | El segundo acuse devuelve 409 | 100 % |
| Lectura de verificación | Un valor distinto devuelve 422 | 100 % |
| Tiempo hasta que la alerta es visible | Desde la validación del resultado (RNF-ALR-01) | ≤ 60 s p95 |
| Alertas perdidas | Eventos del inbox sin alerta tras restaurar el consumidor (RNF-ALR-02) | 0 |
| Defectos Alta abiertos | Del registro de la Semana 13 | 0 antes de la entrega |

Las métricas se revisan en cada *quality gate*; una meta incumplida abre un defecto según [gestion-defectos.md](./gestion-defectos.md).
