# Hallazgos y backlog priorizado — Semana 9

Son 7 hallazgos reales, todos confirmados por una sonda que no cumple ([salida](./evidencia/salida-sondas.txt)). No se agregaron hallazgos sin evidencia.

| ID | Hallazgo | Criterio | Evidencia | Severidad |
|---|---|---|---|---|
| H1 | Consultar sin actor ni estancia muestra «La estancia no existe»: la ruta vacía devuelve 404 y la interfaz la interpreta como estancia inexistente | Nielsen 9, WCAG 3.3.1 | S2 | Alta: el mensaje engaña |
| H2 | Tras una consulta exitosa, el foco queda en «Consultar» y no se anuncia la situación; un lector de pantalla no se entera del nuevo contenido | WCAG 2.4.3, 4.1.3 | S3 | Alta |
| H3 | Los errores de validación no marcan `aria-invalid` ni se anuncian con `role=alert` | WCAG 3.3.1, 4.1.2 | S4 | Media |
| H4 | Cama y motivo no se declaran obligatorios (`required` o `aria-required`) | WCAG 3.3.2 | S5 | Media |
| H5 | La cama destino se teclea como uuid: alta probabilidad de error y carga de memoria | Nielsen 6 | S7 | Alta (requiere que la API ofrezca camas libres) |
| H6 | El éxito no muestra la nueva estancia ni la hora T, así que para dar de alta después hay que conseguir el id por otra vía (también se observó en el recorrido de la Semana 8) | Nielsen 1 | S8 | Alta |
| H7 | Tras una operación confirmada, el motivo anterior queda cargado y puede reutilizarse sin querer | Nielsen 5 | S9 | Media |

## Backlog

| Prioridad | Ítem | Hallazgos | Semana prevista |
|---|---|---|---|
| 1 | Validar actor y estancia localmente antes de consultar, con el foco en el campo que falta y un mensaje correcto | H1 | 10 |
| 2 | Mover el foco al título de la situación y anunciarla | H2 | 10 |
| 3 | Mostrar en el éxito la nueva estancia y la hora, con un enlace para consultarla | H6 | 10 |
| 4 | Limpiar el formulario tras una operación confirmada | H7 | 10 |
| 5 | `aria-invalid`, `role=alert` y `required` en los campos del traslado | H3, H4 | 10 |
| 6 | Elegir la cama de una lista de camas libres | H5 | 16, si la integración ofrece la lista; dependencia con Ocupación (ASII-09) o Salas y camas (ASII-06) |
