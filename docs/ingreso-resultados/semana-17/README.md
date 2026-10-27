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

### Pendiente de cierre

Falta ejecutar la matriz de permisos y la bitácora contra staging/on-premise
autorizado. La prueba local no se presenta como despliegue final.
