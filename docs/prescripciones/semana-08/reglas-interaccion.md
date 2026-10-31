# Reglas de interacción — `PROPUESTO`

| Estado | Presentación | Acción disponible |
|---|---|---|
| Inicial | Paciente y búsqueda claramente identificados | Iniciar búsqueda |
| Validando | Indicador con texto, controles duplicados deshabilitados | Esperar o cancelar si el servicio lo permite |
| Sin conflicto | Resumen antes de guardar | Volver o guardar |
| Advertencia `mild` | Mensaje persistente, no solo color | Revisar y continuar según contrato |
| Rechazo 422 | Motivo, resultado no guardado y retorno seguro | Volver a revisar; no hay excepción |
| Error técnico | Mensaje explícito, sin resultado de éxito | Reintentar cuando sea seguro |
| Guardado | Confirmación con identificador/estado devuelto por el servicio | Abrir detalle |
| Detalle | Estado y datos devueltos por API | Firmar solo si el rol y estado lo permiten |

Los mensajes deben distinguir advertencia clínica, rechazo de regla y error
técnico. No se deben borrar datos de entrada por un error recuperable ni
presentar un rechazo como operación exitosa.

## Campos

Validación de nombres, tipos, unidades, longitudes y valores permitidos queda
`PENDIENTE` del contrato API. No se infieren unidades de ejemplos.
