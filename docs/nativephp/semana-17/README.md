# ASII-29 NativePHP - Semana 17

## Seguridad y despliegue

La extensión conserva la sesión del HIS, el tenant activo y la autorización
antes de sincronizar. La cola local no debe contener secretos ni datos clínicos
innecesarios.

### Controles verificados

- La acción local se limita al flujo HIS acotado.
- La sincronización diferencia estado pendiente, error y éxito.
- Los datos de conexión se configuran fuera del repositorio.
- El build frontend se genera correctamente.

### Pendiente de cierre

Falta comprobar la aplicación NativePHP real, inicio y cierre de sesión y
conexión contra servicios autorizados en el entorno objetivo.
