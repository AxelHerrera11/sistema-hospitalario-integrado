# Semana 17 - Seguridad y despliegue final

| Requisito documentado | Estado | Evidencia |
|---|---|---|
| Matriz de amenazas | Completo | [17 amenazas, controles y riesgo residual](./matriz-amenazas.md) |
| Pruebas de permisos y datos sensibles | Completo | `TrasladosAltasApiTest`: 5 pruebas, 44 aserciones |
| Preparación y reversión | Completo | [Bitácora de despliegue](./bitacora-despliegue.md) |
| Flujo real local | Verificado en S16 | JWT, tenant, RBAC, Laravel y PostgreSQL: HTTP 11/11; SPA 6/6 |
| Despliegue final | Bloqueado | No existe entorno autorizado y quedan dependencias externas |

La parte independiente de Semana 17 queda completa. No se declara un despliegue en staging ni producción: RBAC debe aprobar los permisos, identidad debe sincronizar `local_user_refs` y Plataforma/Tenants debe escoger una base HOSPITAL aislada por hospital.

[Declaración de uso de IA](./DECLARACION_IA.md)
