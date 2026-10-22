# Matriz de decisiones de diseño — ASII-07

| Decisión de diseño | Requerimiento que la justifica | Semanas | Evidencia verificable |
|---|---|---|---|
| Tablero como `radiogroup` con el estado en texto | RF de buscar y asignar cama (UC-ADM-03 y 04), RNF de accesibilidad | Semanas 8, 9 (H2, H3) y 11 | Captura `01-tablero-desktop.png` |
| «No sabemos si se guardó» + «Verificar cama» | RNF de consistencia (sin admisiones duplicadas) | Semanas 5, 9 (H1) y 11 | Paso «sin respuesta» del recorrido |
| «Admitir» deshabilitado durante el envío | RNF de consistencia | Semanas 8 y 11 | Prototipo: `disabled` al enviar |
| Tablero refrescado tras `409 BED_NOT_AVAILABLE` | RF de rechazar la asignación (UC-ADM-05) | Semanas 3 y 11 | Paso «409 cama tomada» |
| «Admitir» fijo abajo en el teléfono | RNF de usabilidad en sala | Semana 10 | Captura `03-tablero-movil.png` |

Cada decisión responde a un requerimiento de la Semana 2 y se comprueba en el prototipo de la Semana 11 o en sus capturas.
