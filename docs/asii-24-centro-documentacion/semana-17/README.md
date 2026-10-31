# Centro de documentacion y manuales por rol - Semana 17

> Tema guia: **Seguridad y despliegue final**

| Requisito documentado | Estado | Evidencia |
|---|---|---|
| Matriz de amenazas | Completo | [12 amenazas, controles y riesgo residual](./matriz-amenazas.md) |
| Pruebas de permisos y datos sensibles | Completo | `DocumentacionSeguridadTest`: 4 pruebas, 28 aserciones |
| Preparacion y reversion | Completo | [Bitacora de despliegue](./bitacora-despliegue.md) |
| Flujo real local | Verificado en S16 | JWT, tenant y RBAC reales; catalogo en configuracion |
| Despliegue final | Bloqueado | No existe entorno autorizado en este equipo |
| Aislamiento entre hospitales | Verificado | T02: cabecera distinta 403; sin membresia 403 |

La parte independiente de Semana 17 queda completa. No se declara un despliegue en staging ni
produccion: falta un entorno autorizado y la aprobacion de la matriz RBAC por el equipo. El servidor
es la autoridad; ocultar documentos en la interfaz no sustituye JWT, tenant ni RBAC.

## Alcance

- Rutas `GET /api/v1/documentacion` y `GET /api/v1/documentacion/{id}`.
- Permiso existente `documentacion.ver`; no se agregan permisos nuevos.
- Pruebas nuevas de matriz de permisos, aislamiento de tenant y no exposicion de rutas internas.

[Declaracion de uso de IA](./DECLARACION_IA.md)
