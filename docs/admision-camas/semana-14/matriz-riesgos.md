# Matriz de riesgos — ASII-07

| # | Riesgo | Probabilidad | Impacto | Mitigación | Seguimiento |
|---|---|---|---|---|---|
| R1 | Dos escritores de `beds.status` | Alta | Alto | Un solo escritor acordado con ASII-06 y ASII-08 | RT-04 de la Semana 13 |
| R2 | Carrera por la misma cama | Media | Alto | Índices parciales + `UPDATE … WHERE status = 'available'` | Prueba concurrente en la Semana 15 |
| R3 | Alta repetida tras un timeout | Media | Alto | El cliente no reintenta y pide verificar la cama | Paso «sin respuesta» del prototipo |
| R4 | Sin camas en horas pico | Alta | Medio | Lista de espera (UC-ADM-09, propuesta) | Escenario de la Semana 12 |
