# Registro de validacion ASII-29 Semana 15

## Matriz minima de pruebas

| Tipo | Caso | Resultado esperado | Ejecucion/evidencia |
|---|---|---|---|
| Unitario | sesion valida | Permite flujo autorizado | Pendiente de plataforma |
| Unitario | sesion vencida | Redirige a login | Pendiente de plataforma |
| Unitario | tenant ausente | Devuelve 400 sin datos | Pendiente de plataforma |
| White-box | permiso insuficiente | No invoca la accion | Pendiente de plataforma |
| White-box | error de red | Conserva estado y permite reintento | Pendiente de plataforma |
| Black-box | inicio/cierre de sesion | Contexto creado y eliminado | Pendiente de entorno |
| Black-box | flujo autorizado | Operacion completa | Pendiente de entorno |
| Black-box | tenant incorrecto | 403 sin datos | Pendiente de entorno |
| Black-box | reintento | No duplica por request id | Pendiente de entorno |

| Evidencia | Estado antes del entorno objetivo |
|---|---|
| Matriz de pruebas | Preparada |
| Estados UX y errores | Preparados |
| Permisos y tenant | Preparados |
| Reintento idempotente | Preparado |
| Sesion real | Requiere ejecucion integrada |
| Aplicacion NativePHP real | Requiere plataforma objetivo |

## Registro de ejecucion

Pegar por caso comando, fecha, commit, salida, captura y defectos `H-##`.
La simulacion documentada no confirma viabilidad NativePHP; esta permanece
restringida hasta ejecutar la aplicacion real contra servicios autorizados.

El cierre se obtiene al adjuntar comandos, fecha, commit y capturas de una
ejecucion real. La evidencia de simulacion queda separada de la viabilidad
tecnica y no la sustituye.
