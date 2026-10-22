# Evidencia de revision ASII-20 Semana 13

## Flujo revisado

1. El bioquimico solicita un resultado pendiente.
2. La API valida identidad, rol y `X-Tenant-ID`.
3. El dominio acepta `validated` o `rejected` con observacion cuando aplica.
4. Persistencia escribe el estado y `AuditLog` dentro de la misma operacion.
5. La respuesta devuelve el estado final y `X-Request-Id`.

## Casos de revision

| Caso | Resultado |
|---|---|
| Validacion autorizada | Estado `validated` y auditoria |
| Rechazo autorizado | Estado `rejected` y motivo |
| Rol incorrecto | 403, sin cambio |
| Tenant ausente o incorrecto | 400/403, sin cambio |
| Resultado inexistente | 404, sin cambio ni auditoria |
| Falla de auditoria | Rollback del estado |

La captura de ejecucion debe adjuntarse al PR cuando CI o el entorno integrado
este disponible; esta rama entrega la matriz verificable y sus criterios.
