# Semana 8 | ASII-29: UX de extensión NativePHP

## Objetivo

Diseñar el flujo UX móvil/escritorio para capturar y sincronizar resultados sin duplicar reglas clínicas.

## Flujo por rol

1. El técnico abre la bandeja local y distingue pendientes, enviados y errores de sincronización.
2. Captura el resultado con unidad, motivo y contexto visible.
3. La aplicación valida localmente y encola el envío cuando no hay conexión.
4. El HIS confirma la recepción; la interfaz muestra sincronizado o requiere reintento.

## Estados, errores y ayudas

| Estado | Mensaje o ayuda |
|---|---|
| Disponible | Mostrar última sincronización y conexión actual. |
| Sin conexión | Explicar que el envío queda en cola y no se pierde. |
| En sincronización | Mostrar progreso y bloquear reenvío duplicado. |
| Rechazado | Mostrar motivo recibido del HIS y acción siguiente. |
| Sin permisos | Indicar rol requerido sin revelar datos innecesarios. |

## Wireframes anotados

```text
[Bandeja local]  Conexion: ONLINE  Ultima sync: 10:42
[Pendientes] [En cola] [Errores]
Paciente | Prueba | Valor | Estado
-----------------------------------
[Capturar resultado]

[Detalle]
Valor: [          ] Unidad: [     ]
Motivo: [                         ]
[Guardar local] [Sincronizar]
```

### Estado vacío

```text
[Pendientes]

          No hay resultados pendientes
       [Actualizar]       [Ver historial]
```

Anotación: el estado vacío explica la situación y ofrece acciones seguras sin mostrar datos clínicos innecesarios.

### Carga y captura

```text
[Cargando resultado...]
Paciente: ******  Prueba: ******
Valor: [          ] Unidad: [     ]
Motivo: [                         ]
[Guardar local] [Sincronizar]
```

Anotación: durante la carga se enmascaran datos sensibles y se bloquean acciones duplicadas. Los campos muestran ayuda de formato y unidad.

### Sin conexión y sincronización

```text
[SIN CONEXION]  Este envio quedara en cola
Resultado guardado localmente: 1
[Reintentar ahora] [Ver cola]
```

Anotación: almacenamiento local permite continuar; una clave idempotente evita duplicar el envío al recuperar conexión.

### Éxito y error recuperable

```text
[SINCRONIZADO]
Resultado recibido por el HIS.
[Ver detalle] [Volver a pendientes]

[ERROR RECUPERABLE]
No se pudo sincronizar. Tus datos siguen en cola.
[Reintentar] [Ver causa]
```

Anotación: éxito y error ofrecen una siguiente acción clara. El reintento conserva el formulario y no crea duplicados.

## Reglas de interacción

- El estado de conexión siempre es visible, pero no bloquea la captura local.
- Sin conexión, guardar local cambia el estado a `En cola` y muestra ayuda.
- El sistema usa una clave idempotente para evitar envíos duplicados.
- Los mensajes de error permiten reintentar o consultar soporte.
- Se minimizan datos en caché, se enmascaran identificadores y se respeta tenant/rol.
- La viabilidad NativePHP depende de almacenamiento local seguro, permisos y compatibilidad de plataforma.

## Evidencia

- Flujo UX: `fuentes/01-user-flow.mmd`.
- Cinco wireframes anotados con vacío, carga, éxito y error recuperable.
- Issue relacionado: `Refs #28`.
- La ejecución real de NativePHP queda pendiente de validar en un entorno habilitado.
- PR menor de 400 líneas.
