# Matriz de decisiones de diseño — ASII-12

| Decisión de diseño | Requerimiento que la justifica | Semanas | Evidencia verificable |
|---|---|---|---|
| Banner crítico fijo, sin cerrar, con `role="alert"` | RF de alerta visual (UC-ALG-05), RNF de seguridad del paciente | Semanas 8 y 11 | Captura `02-banner-critico-desktop.png` |
| «No disponible» en gris oscuro con texto propio | RNF de falla segura | Semanas 5, 9 (H1) y 11 | Paso «servidor caído» del recorrido (rechaza «Sin alergias») |
| Severidad en lenguaje clínico («Amenaza la vida») | RNF de usabilidad | Semanas 8 y 9 | Formulario del prototipo |
| Duplicado (409) con enlace a la alergia existente | RF de no duplicar alergias activas (UC-ALG-03) | Semanas 3 y 11 | Paso «la misma alergia otra vez» |
| En el teléfono solo la alergia más grave y «+n» | RNF de usabilidad en sala | Semana 10 | Escenario «enfermería junto a la cama» |

Cada decisión responde a un requerimiento de la Semana 2 y se comprueba en el prototipo de la Semana 11 o en sus capturas.
