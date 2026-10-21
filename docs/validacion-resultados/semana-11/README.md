# Semana 11 | ASII-20: Prototipo navegable

## Mapa de navegación

`Bandeja -> Resultado -> Revisión -> Decisión -> Firma -> Auditoría`

El bioquímico revisa y aprueba o rechaza; el técnico recibe el resultado de la
decisión. Cada vista mantiene rol, estado, resultado, unidad, rango y trazabilidad.

## Camino feliz

1. Bandeja lista resultados pendientes con prioridad y alerta.
2. Resultado muestra valor, unidad, rango e historial.
3. Revisión permite comentario y firma; Decisión confirma aprobar.
4. Auditoría muestra usuario, fecha, estado anterior y nuevo.

## Error crítico y recuperación

Si la firma no coincide con el rol autorizado, el sistema bloquea la decisión.
El mensaje explica el permiso requerido, conserva comentario y devuelve a Revisión.
El bioquímico puede corregir la sesión o cancelar; el resultado permanece
pendiente y no se registra una aprobación parcial.

## Estados prototipados

| Vista | Desktop | Móvil |
|---|---|---|
| Bandeja | Tabla con prioridad | Tarjetas con acción abrir |
| Revisión | Resultado e historial lado a lado | Secciones apiladas |
| Decisión | Botones y firma en panel | Acciones de ancho completo |
| Error | Alerta contextual | Alerta persistente y reintento |

## Accesibilidad y consistencia

- Aprobar y rechazar tienen foco, etiqueta y confirmación explícitos.
- Rechazar exige motivo; aprobar exige revisar rango y firma.
- Alertas se expresan con texto e icono además de color.
- El sistema informa si la decisión fue registrada o sigue pendiente.

## Evidencia verificable

- Requisito Odoo: `Semana 11: Mejores prácticas para diseño móvil/web`.
- Camino feliz: bandeja a aprobación firmada y auditoría.
- Error crítico: rol de firma no autorizado, estado pendiente y recuperación.
- Prototipo documentado para desktop/móvil, sin datos clínicos reales.
