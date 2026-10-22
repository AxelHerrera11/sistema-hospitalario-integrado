# Registro de avance - ASII-08

Estudiante: María Yamilet Lindo Pablo (`Yamilet1235`) · Issue `Refs #9` · Micro-HIS: [`micro-his/traslados-altas`](../../micro-his/traslados-altas/README.md)

| Semana | Requisito | Evidencia | Pruebas | Rama / PR | develop → main | Pendiente |
|---|---|---|---|---|---|---|
| 1 | Casos de uso, actividad y secuencia | `semana-01/` | Diagramas renderizados | #55 | Promovida | Revisión humana |
| 2 | LSP, RF/RNF, criterios y evidencia verificable | `semana-02/` | Demo LSP en memoria (no es un servicio productivo) | #274, #291, #347 | Promovida | Revisión humana |
| 3 | Micro-HIS PHP vanilla en 4 capas, PDO preparado, pruebas y C4/UML | `semana-03/` | `tests/run.php` 63/63; `tests/postgres.php` 12/12 (PostgreSQL desechable); recorrido de consola con traslado y alta persistidos | #790, #792, #794, #796, #798 y esta entrega | #791, #793, #795, #797, #799 | Concurrencia con conexiones independientes; R-TA-01 y `beds.status` con ASII-06; revisión humana |

## Funcionalidad comprobada hasta hoy

- Traslado y alta por consola contra PostgreSQL con datos ficticios: el traslado cierra el origen y abre el destino contiguo en el mismo episodio; el alta cierra estancia y episodio con el mismo T. Los rechazos no escriben y un error real después de la primera escritura se revierte por completo.
- Todavía no hay API, tenant, RBAC ni interfaz (semanas 16–17).
