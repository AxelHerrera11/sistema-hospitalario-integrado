# Semana 11 | ASII-19: Prototipo navegable

## Mapa de navegación

`Pendientes -> Detalle -> Captura -> Validar -> Confirmación -> Auditoría`

Roles: el técnico captura y envía; el bioquímico consulta y autoriza la
corrección. Todos los estados conservan tenant, resultado, unidad, rango y
trazabilidad.

## Camino feliz

1. Pendientes muestra paciente, prueba, valor vacío, unidad, rango y estado.
2. Detalle abre Captura con etiquetas y foco en el valor.
3. Validar confirma formato, unidad y rango antes de enviar.
4. Confirmación muestra `En revisión`, usuario y enlace a Auditoría.

## Error crítico y recuperación

Si el valor llega con una unidad incompatible, el sistema no envía el resultado.
El error aparece junto al campo, explica la unidad esperada y conserva el resto
del formulario. `Corregir unidad` devuelve al campo; `Cancelar` conserva el
estado pendiente. No se crea una auditoría hasta que la validación es exitosa.

## Estados prototipados

| Vista | Desktop | Móvil |
|---|---|---|
| Pendientes | Tabla con filtros | Tarjetas apiladas |
| Captura | Formulario de dos columnas | Formulario vertical |
| Confirmación | Panel lateral | Pantalla completa |
| Error | Alerta junto al campo | Alerta persistente bajo el campo |

## Accesibilidad y consistencia

- Foco visible, orden de teclado y etiquetas persistentes en ambas variantes.
- La acción de corrección muestra valor anterior como solo lectura.
- El estado usa texto, icono y color; nunca color como única señal.
- Los mensajes indican causa, acción recuperable y si el envío ocurrió.

## Evidencia verificable

- Requisito Odoo: `Semana 11: Mejores prácticas para diseño móvil/web`.
- Archivo: este README contiene mapa, estados y guion reproducible del prototipo.
- Camino feliz: pendiente a revisión con auditoría.
- Error crítico: unidad incompatible, preservación del formulario y recuperación.
- Alcance: prototipo navegable documentado, sin datos clínicos reales.
