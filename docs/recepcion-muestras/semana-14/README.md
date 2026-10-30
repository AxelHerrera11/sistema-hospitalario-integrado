# Semana 14 — SQA ejecutable

CI ejecuta dominio, HTTP/JWT/RBAC, integridad PostgreSQL, compilación Vue y navegador con axe. Las pruebas clínicas usan esquemas aleatorios en bases *_test. Se comprueban atomicidad muestra/outbox, exclusión concurrente y aislamiento hospitalario. Los dobles HTTP se limitan a pruebas del componente Vue.


## Plan de aseguramiento de calidad: métricas, riesgos y estrategia (plan, semana 14)

### Métricas y umbrales

| Métrica | Meta | Valor medido el 2026-10-23 |
|---|---|---|
| Pruebas PHPUnit del módulo (dominio, API, integridad, rollback) | 100 % en verde | 46 pruebas, 264 aserciones, sin fallos |
| Pruebas de navegador (4 tamaños, axe, recuperación, foco, rol) | 100 % en verde | 12 comprobaciones aprobadas |
| Violaciones automáticas axe en el formulario | 0 | 0 (no certifica WCAG) |
| Build de producción Vue | Sin errores | Aprobado |
| Reglas de rechazo con prueba | Todas | tipo, contenedor, volumen, estabilidad, temperatura, hemólisis, coagulación, derrame, identificación |

### Riesgos

| Riesgo | Prob. | Impacto | Mitigación |
|---|---|---|---|
| Rangos clínicos incorrectos (SD-REC-01) | Media | Alto | Validación con laboratorio antes de uso real |
| Duplicar una muestra por reintento | Media | Alto | `request_key` + hash; pruebas de concurrencia en PostgreSQL |
| Mezcla de hospitales | Baja | Alto | Tenant debe coincidir con `RECEPTION_HOSPITAL_ID`; prueba de aislamiento |
| Cambio de contrato de órdenes (ASII-16) | Alta | Medio | Puerto `OrderGateway`; adaptador sustituible |
| CI no ejecutable por facturación de la organización | Alta (ocurre) | Medio | Evidencia local documentada; reejecutar al restablecerse la facturación |
| Orden de merge de la pila de PR | Media | Medio | Revisión en el orden documentado en `semana-15/ramas-prs.md`; no mergear automáticamente |

### Estrategia

Pirámide: unitarias de dominio sin base de datos; HTTP con JWT/RBAC reales; integridad y concurrencia en PostgreSQL con esquemas aleatorios en bases `*_test`; navegador con dobles HTTP solo para el componente Vue. El workflow `recepcion-muestras.yml` ejecuta estas capas; los dobles no sustituyen la prueba de API. Fuera de alcance: validación en staging y carga.
