# ASII-19 Ingreso de resultados - Semana 17

## Seguridad y despliegue

La integración mantiene el aislamiento por hospital mediante `X-Tenant-ID`,
la autenticación JWT y permisos separados para consultar, capturar y corregir.

### Controles verificados

- El tenant se obtiene del contexto autenticado, no del cuerpo del resultado.
- La corrección requiere permiso específico.
- Los cambios conservan evidencia de auditoría.
- Las credenciales locales permanecen fuera del repositorio.
- El build de frontend se genera correctamente con `npm run build`.

### Evidencia ejecutada

Matriz de permisos, aislamiento por hospital, auditoría y bitácora local en
[cierre-verificacion.md](cierre-verificacion.md).

### Pendiente de cierre

Ejecutar la misma matriz contra un entorno staging/on-premise autorizado. La
prueba local con base desechable no se presenta como despliegue final.
