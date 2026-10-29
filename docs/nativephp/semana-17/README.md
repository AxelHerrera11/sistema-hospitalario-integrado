# ASII-29 NativePHP - Semana 17

## Seguridad y despliegue

La extensión conserva la sesión del HIS, el tenant activo y la autorización
antes de sincronizar. La cola local no debe contener secretos ni datos clínicos
innecesarios.

### Controles verificados

- La acción local se limita al flujo HIS acotado.
- La cola distingue `pendiente_sincronizar`; la sincronización de esta entrega responde `simulado` y no marca acciones como sincronizadas.
- Los datos de conexión se configuran fuera del repositorio.
- El build frontend se genera correctamente.

### Evidencia ejecutada

Matriz de permisos, aislamiento por hospital, sincronización simulada y
bitácora local en [cierre-verificacion.md](cierre-verificacion.md).

### Pendiente de cierre

Falta comprobar la aplicación NativePHP real, inicio y cierre de sesión y
conexión contra servicios autorizados en el entorno objetivo.
