# Semana 10 | ASII-29: Diseño responsive NativePHP

## Propuesta 320–430 px

El prototipo NativePHP prioriza viabilidad, flujo HIS acotado, capacidades
nativas, sincronización y riesgos de plataforma. La aplicación muestra conexión,
última sincronización y cola sin ocultar el estado del resultado.

| Pantalla | Contenido prioritario | Regla responsive |
|---|---|---|
| Bandeja | Conexión, pendientes, cola y errores | Una tarjeta por resultado; filtros desplegables. |
| Captura | Valor, unidad y rango | Campos apilados y ayuda contextual. |
| Cola | Estado, intento y reintento | No se permite reenviar una solicitud activa. |
| Sincronización | Resultado del HIS y causa de error | Mensaje persistente y acción clara. |

## Reglas de interacción

- Offline no bloquea captura local; cambia el estado a `En cola`.
- La sincronización reutiliza una clave idempotente y conserva errores recuperables.
- La interfaz informa permisos, compatibilidad y última sincronización.
- La caché minimiza datos clínicos y aplica tenant, rol y expiración.

## Escenarios móviles

### Camino feliz

1. El técnico abre la bandeja y verifica conexión.
2. Captura valor, unidad y contexto del resultado.
3. La aplicación valida localmente y sincroniza con el HIS.
4. Se muestra `Sincronizado` y la referencia de trazabilidad.

### Conexión limitada

1. La aplicación pierde red después de guardar localmente.
2. Muestra `En cola`, número de intento y fecha, sin borrar el formulario.
3. Al volver la conexión, reintenta con la misma clave.
4. Si la plataforma no permite el almacenamiento requerido, muestra soporte y no
   declara viabilidad confirmada.

## Criterios verificables

- Las pantallas conservan acciones y datos esenciales en 320 px y 430 px.
- La cola distingue local, enviando, sincronizado y error.
- El flujo no duplica envíos ni expone datos de otro tenant.
- La evidencia separa simulación de ejecución real de NativePHP y documenta la
  validación pendiente de permisos y plataforma.

## Evidencia

- Requisito Odoo: `Semana 10: Diseño para movilidad`.
- Entregable: propuesta responsive de cuatro pantallas y dos escenarios.
- Validación: revisión de breakpoints, cola, offline y riesgos; sin infraestructura real.
