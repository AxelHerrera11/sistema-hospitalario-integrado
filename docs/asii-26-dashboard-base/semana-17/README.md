# Dashboard base - Semana 17

> Tema guia: **Seguridad y despliegue final**

| Requisito documentado | Estado | Evidencia |
|---|---|---|
| Matriz de amenazas | Completo | [12 amenazas, controles y riesgo residual](./matriz-amenazas.md) |
| Pruebas de permisos y datos sensibles | Completo | `DashboardSeguridadTest`: 4 pruebas, 40 aserciones |
| Preparacion y reversion | Completo | [Bitacora de despliegue](./bitacora-despliegue.md) |
| Flujo real local | Verificado en S16 | JWT, tenant y RBAC reales; citas en memoria |
| Despliegue final | Bloqueado | No existe entorno autorizado ni PostgreSQL en este equipo |
| Aislamiento entre hospitales | Verificado | T02: hospital ajeno 403; sin configurar 503 |

La parte independiente de Semana 17 queda completa. No se declara un despliegue en staging ni
produccion: falta un entorno autorizado, PostgreSQL real y la aprobacion de la matriz RBAC por el
equipo. El dashboard falla cerrado: sin permiso no se consulta la fuente ni se arma la tarjeta.

## Alcance

- Ruta `GET /api/v1/dashboard/resumen`.
- Tarjetas calculadas en el servidor con los permisos de la sesion (`citas.ver`); no hay
  `RequirePermission` en la ruta, pero el controlador no confia en el cliente.
- Pruebas nuevas de sesion, tenant real (incluido el caso sin configurar) y datos internos.

[Declaracion de uso de IA](./DECLARACION_IA.md)
