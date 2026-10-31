# ASII-29 NativePHP - Semana 16

## Integración funcional

Se integró el flujo acotado de preparación local y sincronización en línea en
la ruta `/nativephp`, reutilizando `micro-his/nativephp-extension-prototype`.

La cola local se persiste en PostgreSQL. La sincronización de esta entrega es
explícitamente `simulado`: no afirma que exista un servicio remoto ni marca
acciones como sincronizadas hasta contar con ese gateway autorizado.

### Evidencia comprobada

- Preparación de una acción local.
- Estado `pendiente_sincronizar` cuando no hay conexión.
- Respuesta `simulado` sin afirmar sincronización remota.
- Tenant, sesión y permisos heredados del HIS.

### Validación local

```text
php micro-his/nativephp-extension-prototype/tests/run.php
OK: preparacion local, regla offline y error de persistencia verificados.
```

Esta evidencia distingue el prototipo ejecutado localmente de una aplicación
NativePHP distribuida; todavía no se declara una entrega de escritorio real.
