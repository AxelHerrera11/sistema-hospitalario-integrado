# Registro de avance - ASII-08

Estudiante: María Yamilet Lindo Pablo (`Yamilet1235`) · Issue `Refs #9` · Micro-HIS: [`micro-his/traslados-altas`](../../micro-his/traslados-altas/README.md)

| Semana | Requisito | Evidencia | Pruebas | Rama / PR | develop → main | Pendiente |
|---|---|---|---|---|---|---|
| 1 | Casos de uso, actividad y secuencia | `semana-01/` | Diagramas renderizados | #55 | Promovida | Revisión humana |
| 2 | LSP, RF/RNF, criterios y evidencia verificable | `semana-02/` | Demo LSP en memoria (no es un servicio productivo) | #274, #291, #347 | Promovida | Revisión humana |
| 3 | Micro-HIS PHP vanilla en 4 capas, PDO preparado, pruebas y C4/UML | `semana-03/` | `tests/run.php` 63/63; `tests/postgres.php` 12/12 (PostgreSQL desechable); recorrido de consola con traslado y alta persistidos | #790, #792, #794, #796, #798 y esta entrega | #791, #793, #795, #797, #799 | Concurrencia con conexiones independientes; R-TA-01 y `beds.status` con ASII-06; revisión humana |
| 4 | MVC, contrato Repository, adaptadores PDO e InMemory y repositorio compartido | `semana-04/` | `run.php` 72/72 (contrato InMemory 8/8); `postgres.php` 20/20 (contrato PDO 8/8) | #802 y esta entrega | #803 | Acuerdos con ASII-06 y ASII-07; revisión humana |
| 5 | Cliente-servidor: contrato API, propiedades, frontera de microservicio y plan académico | `semana-05/` | `run.php` 88/88; recorrido HTTP con `php -S` sobre PostgreSQL (401, 403, 409, 201, 200, 409) | #849 y esta entrega | #850 | Autenticación real, tenant, RBAC y observabilidad (S16–S17); revisión humana |
| 6 | Primer parcial: 7 diapositivas, diagrama trazable, matriz de 15 decisiones y cambio práctico (simulado) | `semana-06/` | `run.php` 88/88; 503 real con `Retry-After` | Esta entrega | — | Sin defensa oral declarada; revisión humana |

## Funcionalidad comprobada hasta hoy

- Traslado y alta por consola contra PostgreSQL con datos ficticios: el traslado cierra el origen y abre el destino contiguo en el mismo episodio; el alta cierra estancia y episodio con el mismo T. Los rechazos no escriben y un error real después de la primera escritura se revierte por completo.
- Prototipo HTTP del micro-HIS (Semana 5) probado contra PostgreSQL. Todavía no hay autenticación real, tenant, RBAC ni interfaz (semanas 16–17).
