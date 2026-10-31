# Gestion de citas medicas y agenda - Semana 17

> Tema guia: **Seguridad y despliegue final**

| Requisito documentado | Estado | Evidencia |
|---|---|---|
| Matriz de amenazas | Completo | [13 amenazas, controles y riesgo residual](./matriz-amenazas.md) |
| Pruebas de permisos y datos sensibles | Completo | `CitasSeguridadTest`: 3 pruebas, 29 aserciones |
| Preparacion y reversion | Completo | [Bitacora de despliegue](./bitacora-despliegue.md) |
| Flujo real local | Verificado en S16 | JWT, tenant y RBAC reales; HOSPITAL en memoria |
| Despliegue final | Bloqueado | No existe entorno autorizado ni PostgreSQL en este equipo |
| Aislamiento entre hospitales | Verificado | T02: cabecera distinta 403; sin membresia 403 |

La parte independiente de Semana 17 queda completa. No se declara un despliegue en staging ni
produccion: falta un entorno autorizado, PostgreSQL real y la aprobacion de la matriz RBAC por el
equipo. El servidor es la autoridad; ocultar acciones en la interfaz no sustituye JWT, tenant ni RBAC.

## Alcance

- Rutas `GET/POST /api/v1/citas`, transiciones `confirmar`, `completar`, `cancelar` y `DELETE`.
- Permisos existentes en `config/rbac.php` (`citas.ver`, `citas.crear`, `citas.confirmar`,
  `citas.cancelar`); no se agregan permisos nuevos.
- Pruebas nuevas de matriz de permisos, aislamiento de tenant y no exposicion de datos internos.

[Declaracion de uso de IA](./DECLARACION_IA.md)
