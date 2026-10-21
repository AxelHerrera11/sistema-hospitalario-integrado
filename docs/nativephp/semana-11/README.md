# Semana 11 | ASII-29: Prototipo navegable NativePHP

## Mapa de navegación

`Bandeja local -> Captura -> Cola -> Sincronización -> Resultado -> Soporte`

El técnico captura en el flujo HIS acotado. La aplicación muestra conexión,
permisos, capacidades nativas y estado de sincronización sin cambiar reglas
clínicas del HIS.

## Camino feliz

1. Bandeja local muestra pendientes y última sincronización.
2. Captura valida valor, unidad y rango y guarda una solicitud idempotente.
3. Cola sincroniza al recuperar conexión.
4. Resultado confirma `Sincronizado` y referencia de trazabilidad.

## Error crítico y recuperación

Si el almacenamiento local seguro no está disponible, la aplicación no promete
persistencia: informa el riesgo de plataforma, no conserva datos sensibles y
ofrece volver al HIS web. Si ya existe una solicitud local, el usuario puede
reintentar con la misma clave sin duplicar el envío.

## Estados prototipados

| Vista | Desktop | Móvil |
|---|---|---|
| Bandeja | Tabla de cola y conexión | Tarjetas de estado |
| Captura | Formulario con contexto | Formulario vertical |
| Sincronización | Progreso y detalle técnico | Mensaje corto y reintento |
| Soporte | Riesgo y permisos | Ayuda contextual |

## Accesibilidad, seguridad y viabilidad

- Estado online/offline se expresa con texto, icono y color.
- Foco, etiquetas y orden de navegación son consistentes en desktop y móvil.
- La cola minimiza datos, aplica expiración y respeta tenant y rol.
- La evidencia distingue simulación de ejecución real; la viabilidad NativePHP
  queda pendiente hasta probar permisos y almacenamiento en cada plataforma.

## Evidencia verificable

- Requisito Odoo: `Semana 11: Mejores prácticas para diseño móvil/web`.
- Camino feliz: captura local a sincronización confirmada.
- Error crítico: almacenamiento no disponible, riesgo explícito y ruta web.
- Prototipo documentado, sin declarar ejecución real ni datos clínicos reales.
