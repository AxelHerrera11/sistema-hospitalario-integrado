# Semana 9 | ASII-20: Evaluación UX y accesibilidad

## Alcance

Se evalúa la revisión, validación o rechazo de un resultado por bioquímico.
La pantalla debe conservar contexto de resultado, unidad, rango, firma y
trazabilidad para los roles autorizados.

## Checklist de evaluación

| Criterio | Evidencia | Prioridad |
|---|---|---|
| Teclado y foco | Bandeja, detalle, comentario y decisión son navegables con Tab. | Alta |
| Contraste | Alertas fuera de rango y estados cumplen AA con texto adicional. | Alta |
| Etiquetas | Resultado, unidad, rango, comentario y firma tienen etiquetas. | Alta |
| Mensajes | Aprobar, rechazar y error de red explican la siguiente acción. | Alta |
| Prevención de errores | Rechazo exige motivo y aprobar exige revisar el rango. | Alta |
| Trazabilidad | Usuario, fecha, decisión y estado anterior/nuevo son visibles. | Alta |
| Privacidad | La bandeja limita resultados a tenant, unidad y rol permitido. | Alta |

## Hallazgos priorizados

1. **V1 Alta:** una alerta fuera de rango debe incluir texto, no solo color.
2. **V2 Alta:** la firma y el rango deben estar en el mismo contexto de decisión.
3. **V3 Alta:** rechazar sin motivo debe bloquearse con mensaje junto al campo.
4. **V4 Alta:** los botones deben bloquearse durante la petición para evitar doble decisión.
5. **V5 Media:** la tabla debe anunciar encabezado, paciente y prueba al usar lector.
6. **V6 Media:** un fallo de red debe conservar comentario y permitir reintento.

## Criterios verificables

- El bioquímico puede filtrar, abrir un resultado y revisar valor, unidad y rango.
- Aprobar registra firma y estado; rechazar exige motivo antes de cambiarlo.
- Un resultado fuera de rango es identificable en texto y no depende del color.
- Un error de conexión no duplica la decisión ni borra el comentario.
- La auditoría identifica usuario autorizado, fecha, decisión y trazabilidad.

## Evidencia y trazabilidad

- Requisito Odoo: `Semana 9: Evaluación del diseño, usabilidad y accesibilidad`.
- Flujo evaluado: revisión, validación o rechazo de un resultado por bioquímico.
- Verificación: checklist manual, navegación por teclado y revisión de mensajes.
- El alcance es documental y no declara pruebas clínicas reales.
