# Evidencia de revision ASII-29 Semana 13

## Flujo revisado

1. La aplicacion inicia sesion y obtiene el contexto del hospital.
2. La pantalla carga solo datos autorizados.
3. La accion envia `X-Tenant-ID`, `X-User-Ref` y `X-Request-Id`.
4. La interfaz muestra exito, error recuperable o estado sin conexion.
5. El cierre de sesion elimina el contexto local de autenticacion.

## Casos

| Caso | Resultado esperado |
|---|---|
| Sesion valida | Flujo disponible |
| Sesion vencida | Redireccion a login |
| Tenant ausente | Error 400, sin datos |
| Permiso insuficiente | Error 403, sin accion |
| API no disponible | Estado recuperable y reintento |
| Dato duplicado | Idempotencia por request id |

Las capturas de ejecucion real deben adjuntarse cuando el entorno NativePHP y
los servicios autorizados esten disponibles; el prototipo no se presenta como
aplicacion productiva.
