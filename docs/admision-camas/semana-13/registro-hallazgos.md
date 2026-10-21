# Registro de hallazgos — ASII-07

Severidad: **Alta** (puede dañar datos clínicos o la seguridad del paciente), **Media** (defecto o incoherencia que afecta la integración), **Baja** (cosmético o de estilo).

| # | Severidad | Área | Hallazgo | Estado |
|---|---|---|---|---|
| RT-01 | Media | Trazabilidad | Los comentarios y el README de la Semana 3 citaban «UC-ADM-01 a 03» y «UC-ADM-02 Buscar camas»; en la Semana 1, buscar es UC-ADM-03 y asignar es UC-ADM-04 | Cerrado (commits «alinear la numeración de casos de uso») |
| RT-02 | Media | Prueba | Una aserción comparaba el campo del error con la clave cambiada y no con el campo real (`bed_type`) | Cerrado antes de publicar la Semana 3 |
| RT-03 | Baja | Prueba | La prueba de PDO armaba un SQL con el id interpolado | Cerrado (sentencia preparada) |
| RT-04 | Alta | Datos | `beds.status` lo escriben Admisión, Traslados y Salas y camas; falta un solo escritor | Abierto: acordar con Madelin y Yamilet |
| RT-05 | Media | Prueba | La carrera por la misma cama entre dos procesos no tiene prueba; solo el camino de dominio | Abierto (Semana 15) |

Los hallazgos cerrados vienen de la autorrevisión de la reentrega y citan el commit que los corrigió. Los abiertos son el punto de partida de la revisión formal.
