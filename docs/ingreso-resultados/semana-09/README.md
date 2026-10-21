# Semana 9 | ASII-19: Evaluación UX y accesibilidad

## Alcance

Se evalúa el flujo de captura y corrección controlada de resultados pendientes.
La revisión cubre al técnico que captura, al bioquímico que autoriza y los
estados `Pendiente`, `En revisión`, `Corregido` y `Rechazado`.

## Checklist de evaluación

| Criterio | Evidencia | Prioridad |
|---|---|---|
| Teclado y orden de foco | La tabla, el formulario y la confirmación son navegables con Tab. | Alta |
| Foco visible | El control activo conserva un indicador de foco contrastado. | Alta |
| Etiquetas | Valor, unidad, rango, motivo y estado tienen etiquetas persistentes. | Alta |
| Contraste | Texto, estados y alertas cumplen contraste AA sin depender solo del color. | Alta |
| Mensajes | Los errores indican campo, causa y acción recuperable. | Media |
| Prevención de errores | La corrección muestra valor anterior y exige motivo antes de enviar. | Alta |
| Datos clínicos | La vista limita resultados al tenant y rol autorizados. | Alta |

## Hallazgos y backlog

1. **H1 Alta:** el rango no debe aparecer solo en un tooltip; debe quedar junto al
   valor y su unidad. Criterio: un usuario puede validar el rango sin abrir ayuda.
2. **H2 Alta:** el botón de corrección requiere foco visible y confirmación.
   Criterio: teclado y lector identifican acción, estado y consecuencia.
3. **H3 Alta:** un error de formato debe conservar el valor capturado.
   Criterio: corregir un campo no borra los demás datos.
4. **H4 Alta:** el estado pendiente debe usar texto e icono, no solo color.
   Criterio: la diferencia es comprensible en escala de grises.
5. **H5 Media:** la tabla debe ofrecer encabezados asociados a valor, unidad y rango.
   Criterio: lector de pantalla anuncia cada celda con su columna.
6. **H6 Media:** la sesión expirada debe indicar que el envío no se realizó.
   Criterio: se puede reintentar sin duplicar la corrección.

## Criterios verificables

- El camino feliz permite filtrar un pendiente, capturar valor y enviar a revisión.
- Un valor fuera de rango muestra mensaje recuperable y no cambia el estado.
- Una corrección conserva valor anterior, nuevo valor, motivo y usuario en auditoría.
- La interfaz no muestra resultados de otro tenant ni permite acciones sin rol.

## Evidencia y trazabilidad

- Requisito Odoo: `Semana 9: Evaluación del diseño, usabilidad y accesibilidad`.
- Flujo evaluado: captura y corrección controlada de resultados pendientes.
- Verificación: revisión manual del checklist y prueba de teclado en los estados.
- Alcance limitado a evidencia documental; no se declara ejecución de NativePHP.
