# Registro de validacion ASII-20 Semana 15

## Matriz minima de pruebas

| Tipo | Caso | Resultado esperado | Ejecucion/evidencia |
|---|---|---|---|
| Unitario | decision `validated` | Regla acepta pendiente | Pendiente de CI integrado |
| Unitario | decision `rejected` sin motivo | Regla rechaza payload | Pendiente de CI integrado |
| Unitario | estado final reprocesado | Regla bloquea cambio | Pendiente de CI integrado |
| White-box | falla de auditoria | Revierte estado y auditoria | Pendiente de entorno |
| White-box | tenant/rol en servicio | No cruza aislamiento | Pendiente de entorno |
| Black-box | validar autorizado | 200 y estado persistido | Pendiente de entorno |
| Black-box | rechazar autorizado | 200 y motivo persistido | Pendiente de entorno |
| Black-box | rol no autorizado | 403 sin cambio | Pendiente de entorno |
| Black-box | identificador inexistente | 404 sin auditoria | Pendiente de entorno |

| Evidencia | Estado antes del entorno integrado |
|---|---|
| Matriz de pruebas | Preparada |
| Casos de exito y rechazo | Preparados |
| Caso 403 y tenant | Preparado |
| Caso 404 | Preparado |
| Rollback de auditoria | Requiere ejecucion integrada |
| PostgreSQL y auth real | Requiere entorno integrado |

## Registro de ejecucion

Pegar por caso el comando exacto, fecha, commit, salida, captura y defectos
`H-##`. Mientras no exista entorno integrado, el resultado se marca como
`preparado`; no se presenta como ejecucion real.

El cierre se obtiene al pegar en este archivo los comandos exactos y su salida
del CI o del entorno HIS. La evidencia documental no sustituye esa ejecucion.
