# Evidencia de revision ASII-29 Semana 13

## Plan y roles

- Autor: Gerson Orellana prepara el flujo y los escenarios del prototipo.
- Moderador: Yamileth1235 comprueba alcance, riesgos y evidencia.
- Revisores: líder del módulo y responsable de integración verifican sesión,
  permisos, tenant y aislamiento de datos.
- Entrada: prototipo identificable, usuario/tenant ficticios y plataforma
  objetivo declarada.
- Salida: checklist completo, hallazgos con decisión y limitaciones técnicas
  explícitas.

## Checklist de 12 puntos

1. Inicio de sesión válido.
2. Sesión vencida redirige a login.
3. Cierre de sesión elimina el contexto.
4. Tenant ausente devuelve 400.
5. Tenant incorrecto devuelve 403.
6. Permiso insuficiente no ejecuta la acción.
7. Reintento conserva el `X-Request-Id`.
8. Error de red permite reintentar.
9. Estado de carga no duplica la operación.
10. Estado vacío no expone datos de otro tenant.
11. Datos clínicos no se guardan en cache local.
12. La simulación y la ejecución NativePHP real quedan separadas.

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

## Formato de hallazgo

Registrar `H-##`, severidad, escenario, pasos, esperado, observado, evidencia,
responsable y estado (`abierto`, `corregido` o `aceptado`).
