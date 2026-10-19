# Matriz de decisiones de diseño — ASII-17

| Decisión de diseño | Requerimiento que la justifica | Semanas | Evidencia verificable |
|---|---|---|---|
| Rangos en un `fieldset` «de menor a mayor» y foco al campo con error | RF-LAB-03, RNF-LAB-07 (errores comprensibles) | Semanas 8, 9 (H1, H4) y 11 | Paso «rangos desordenados» del recorrido de la Semana 11 |
| Solo versiones vigentes en la lista; el detalle no tiene «Editar» | RF-LAB-06, RNF-LAB-02 (una orden nunca cambia de definición) | Semanas 8 y 11 | Detalle «Las órdenes ya emitidas conservan esta versión» |
| «Catálogo no disponible» distinto de «sin resultados» | RNF-LAB-01 (seguridad del paciente) | Semanas 5, 9 y 11 | Paso «servidor caído» del recorrido |
| Sin «Nueva versión» en el teléfono | RNF-LAB-07 (errores comprensibles) | Semana 10 | Captura `03-lista-movil.png` |
| Controller delgado + FormRequest + permiso `laboratorio.catalogo.administrar` | RNF-LAB-01 (acceso por permiso y tenant) | Semana 7 | Diagrama de componentes |

Cada decisión responde a un requerimiento de la Semana 2 y se comprueba en el prototipo de la Semana 11 o en sus capturas.
