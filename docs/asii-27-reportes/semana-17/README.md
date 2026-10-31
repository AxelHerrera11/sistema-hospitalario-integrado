# Reportes operativos exportables - Semana 17

> Tema guia: **Seguridad y despliegue final**

| Requisito documentado | Estado | Evidencia |
|---|---|---|
| Matriz de amenazas | Completo | [14 amenazas, controles y riesgo residual](./matriz-amenazas.md) |
| Pruebas de permisos y datos sensibles | Completo | `ReportesSeguridadTest`: 6 pruebas, 51 aserciones |
| Preparacion y reversion | Completo | [Bitacora de despliegue](./bitacora-despliegue.md) |
| Flujo real local | Verificado en S16 | JWT, tenant y RBAC reales; fuente HOSPITAL en memoria |
| Despliegue final | Bloqueado | No existe entorno autorizado ni PostgreSQL en este equipo |
| Aislamiento entre hospitales | Verificado | T02: cabecera distinta 403; sin membresia 403 |

La parte independiente de Semana 17 queda completa. No se declara un despliegue en staging ni
produccion: falta un entorno autorizado, PostgreSQL real y la aprobacion de la matriz RBAC por el
equipo. El servidor es la autoridad; ocultar reportes o el boton de exportar en la interfaz no
sustituye JWT, tenant ni RBAC.

## Alcance

- Rutas `GET /api/v1/reportes`, `GET /api/v1/reportes/{reporte}` y
  `GET /api/v1/reportes/{reporte}/export`.
- Permisos existentes `reportes.ver` y `reportes.exportar`; no se agregan permisos nuevos.
- Pruebas nuevas de matriz de permisos, exportacion, tenant real, base sin configurar y datos internos.

[Declaracion de uso de IA](./DECLARACION_IA.md)
