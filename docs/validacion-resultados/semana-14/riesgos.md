# Riesgos y controles SQA ASII-20

| Riesgo | Impacto | Control | Responsable |
|---|---|---|---|
| Auditoria falla despues del cambio | Alto | Transaccion y prueba de rollback | Backend |
| Rol incorrecto valida | Alto | Middleware y prueba 403 | Seguridad |
| Tenant cruzado | Alto | `X-Tenant-ID` y prueba 403 | API |
| Reproceso de resultado final | Medio | Regla de estado y 422 | Dominio |
| Identificador inexistente | Medio | 404 estable | API |
| Fixture con dato sensible | Alto | Datos ficticios revisados | QA |

Cada riesgo se cierra con una salida observable y no solo con una inspeccion de
codigo. La ejecucion contra PostgreSQL y autenticacion real pertenece a la
validacion integrada posterior.
