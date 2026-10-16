# Reglas de breakpoint — RBAC móvil

## Rangos

| Rango | Dispositivo | Layout | Acción primaria |
|---|---|---|---|
| 320–430 px | Móvil | 1 columna, campos apilados, tablas como tarjetas. | Botón fijo abajo (sticky), ancho completo. |
| 431–768 px | Móvil grande / tablet vertical | 1 columna ancha, tarjetas con más detalle. | Botón fijo abajo. |
| 769–1024 px | Tablet horizontal | 2 columnas (formulario + resultado). | Botón en la barra de acciones. |
| > 1024 px | Escritorio | Tabla completa + panel lateral de detalle. | Botón en la cabecera del formulario. |

## Decisiones por rango

- A 320 px: se oculta la columna de fecha en la bitácora; el `trace_id` se acorta visualmente pero se copia completo.
- Los filtros pasan a un acordeón colapsado por defecto en ≤ 430 px.
- El permiso requerido (`rbac.roles.asignar`) se muestra como texto de ayuda bajo el campo, no en una columna aparte.
- El estado de la decisión (Permitido/Denegado) nunca se corta: tiene prioridad sobre cualquier otro dato.

## Base técnica (propuesta)

- Vue 3 + CSS con `min-width` media queries en esos cuatro puntos.
- Contenedores fluidos (`%`/`fr`), nada de anchos fijos en px para datos.
- Probar con las herramientas de dispositivo del navegador a 320, 375 y 430 px.
