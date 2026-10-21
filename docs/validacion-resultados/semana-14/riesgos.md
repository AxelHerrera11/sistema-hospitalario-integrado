# Plan SQA y controles ASII-20

## Metricas de seguimiento

| Metrica | Umbral | Fuente | Responsable | Accion |
|---|---|---|---|---|
| Cobertura de casos | 100% de casos criticos | Matriz S13 | QA | Completar faltantes |
| Respuestas 4xx correctas | 100% | Pruebas API | API | Abrir hallazgo |
| Auditorias completas | 100% | AuditLog | Backend | Bloquear cierre |
| Rollback ante falla | 100% | Prueba de transaccion | Backend | Corregir atomicidad |
| Tiempo de respuesta | < 500 ms en fixture | Log de prueba | Integracion | Perfilar consulta |
| Hallazgos criticos abiertos | 0 | Registro H-## | Moderador | No promover |

## Calendario de seguimiento

| Momento | Actividad | Responsable |
|---|---|---|
| Inicio | Revisar entrada y datos ficticios | Autor + QA |
| Durante | Ejecutar controles preventivos y registrar H-## | Autor |
| Cierre | Ejecutar controles detectivos y revisar metricas | Yamileth1235 |
| Antes de merge | Confirmar umbrales y pendientes | Lider del modulo |

| Riesgo | Impacto | Control | Responsable |
|---|---|---|---|
| Auditoria falla despues del cambio | Alto | Transaccion y prueba de rollback | Backend |
| Rol incorrecto valida | Alto | Middleware y prueba 403 | Seguridad |
| Tenant cruzado | Alto | `X-Tenant-ID` y prueba 403 | API |
| Reproceso de resultado final | Medio | Regla de estado y 422 | Dominio |
| Identificador inexistente | Medio | 404 estable | API |
| Fixture con dato sensible | Alto | Datos ficticios revisados | QA |

## Tipo de control

- Preventivos: validacion de rol, tenant, payload y datos ficticios antes de
  persistir.
- Detectivos: pruebas 403/404, rollback, auditoria y medicion de respuesta
  despues de ejecutar el flujo.

Cada riesgo se cierra con una salida observable y no solo con una inspeccion de
codigo. La ejecucion contra PostgreSQL y autenticacion real pertenece a la
validacion integrada posterior.
