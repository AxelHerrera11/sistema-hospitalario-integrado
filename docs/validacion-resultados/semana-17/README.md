# ASII-20 Validación bioquímica - Semana 17

## Seguridad y despliegue

La decisión de validar o rechazar está separada de la captura y exige el rol
bioquímico. La respuesta conserva el resultado y evita repetir una decisión
ya registrada.

### Controles verificados

- El rol se resuelve desde la membresía activa del hospital.
- La solicitud usa tenant autenticado y permiso de validación.
- La decisión se registra junto con la auditoría del flujo.
- Se corrigió la comparación de vigencia usando `CURRENT_TIMESTAMP` de la BD.
- Build frontend ejecutado correctamente.

### Evidencia ejecutada

Matriz de permisos con denegación de roles no autorizados, aislamiento por
hospital, decisión única y bitácora local en
[cierre-verificacion.md](cierre-verificacion.md).

### Pendiente de cierre

Repetir la matriz en un entorno staging/on-premise autorizado.
