# Semana 10 | ASII-20: Diseño responsive móvil

## Propuesta 320–430 px

La revisión bioquímica conserva como prioridad resultado, unidad, rango,
alertas, decisión, firma y trazabilidad. La bandeja se convierte en tarjetas
compactas y el detalle apila la información sin quitar contexto de decisión.

| Pantalla | Contenido prioritario | Regla responsive |
|---|---|---|
| Bandeja | Paciente, prueba, prioridad y estado | Filtros en panel; una decisión por tarjeta. |
| Detalle | Resultado, unidad, rango e historial | Datos clínicos apilados y alerta textual. |
| Decisión | Firma, comentario, aprobar o rechazar | Botones de ancho completo; rechazo separado. |
| Confirmación | Estado, usuario y auditoría | Mensaje persistente y siguiente resultado. |

## Reglas de interacción

- Aprobar y rechazar permanecen visibles con foco y etiquetas claras.
- Rechazar exige motivo; aprobar exige revisar rango, comentario y firma.
- Una petición en curso bloquea ambas acciones para evitar decisiones duplicadas.
- Si falla la conexión, el comentario y la decisión quedan en el formulario.

## Escenarios móviles

### Camino feliz

1. El bioquímico filtra la bandeja por prioridad.
2. Abre detalle y verifica resultado, unidad, rango e historial.
3. Firma, aprueba y confirma el estado trazable.
4. La interfaz ofrece siguiente resultado o auditoría.

### Error recuperable

1. La conexión falla al registrar rechazo.
2. Se conserva motivo y contexto y se muestra la causa.
3. `Reintentar` registra una sola decisión; `Cancelar` mantiene pendiente.

## Criterios verificables

- Las cuatro pantallas son legibles y utilizables en 320 px y 430 px.
- El rango y la alerta se entienden sin depender del color.
- La confirmación identifica estado, firma, usuario y trazabilidad.
- Tenant, unidad y rol autorizado se conservan en bandeja y detalle.

## Evidencia

- Requisito Odoo: `Semana 10: Diseño para movilidad`.
- Entregable: propuesta responsive de cuatro pantallas y dos escenarios.
- Validación: revisión de foco, contraste, etiquetas y recuperación sin datos reales.
