# Registro de avance - ASII-08

Estudiante: María Yamilet Lindo Pablo (`Yamilet1235`) · Issue `Refs #9` · Micro-HIS: [`micro-his/traslados-altas`](../../micro-his/traslados-altas/README.md)

| Semana | Requisito | Evidencia | Pruebas | Rama / PR | develop → main | Pendiente |
|---|---|---|---|---|---|---|
| 1 | Casos de uso, actividad y secuencia | `semana-01/` | Diagramas renderizados | #55 | Promovida | Revisión humana |
| 2 | LSP, RF/RNF, criterios y evidencia verificable | `semana-02/` | Demo LSP en memoria (no es un servicio productivo) | #274, #291, #347 | Promovida | Revisión humana |
| 3 | Micro-HIS PHP vanilla en 4 capas, PDO preparado, pruebas y C4/UML | `semana-03/` | `tests/run.php` 63/63; `tests/postgres.php` 12/12 (PostgreSQL desechable); recorrido de consola con traslado y alta persistidos | #790, #792, #794, #796, #798 y esta entrega | #791, #793, #795, #797, #799 | Concurrencia con conexiones independientes; R-TA-01 y `beds.status` con ASII-06 |
| 4 | MVC, contrato Repository, adaptadores PDO e InMemory y repositorio compartido | `semana-04/` | `run.php` 72/72 (contrato InMemory 8/8); `postgres.php` 20/20 (contrato PDO 8/8) | #802 y esta entrega | #803 | Acuerdos con ASII-06 y ASII-07 |
| 5 | Cliente-servidor: contrato API, propiedades, frontera de microservicio y plan académico | `semana-05/` | `run.php` 88/88; recorrido HTTP con `php -S` sobre PostgreSQL (401, 403, 409, 201, 200, 409) | #849 y esta entrega | #850 | Autenticación real, tenant, RBAC y observabilidad (S16–S17) |
| 6 | Primer parcial: 7 diapositivas, diagrama trazable, matriz de 15 decisiones y cambio práctico (simulado) | `semana-06/` | `run.php` 88/88; 503 real con `Retry-After` | Esta entrega | — | Sin defensa oral declarada |
| 7 | Componentes backend/frontend con contratos y refactor `EjecutorOperaciones` | `semana-07/` | `run.php` 95/95; defecto `motivo` como arreglo: 201 antes y 422 después | Esta entrega | — | Frontend solo en diseño |
| 8 | Flujo por roles, 6 wireframes anotados y prototipo de interfaz contra la API real | `semana-08/` | `ui-mensajes` 4/4; recorrido en Chrome 9/9 contra PostgreSQL | #859 y esta entrega | #860 | Roles propuestos sin RBAC; sin pruebas con usuarios |
| 9 | Checklist de 14 criterios (heurísticas y WCAG), 7 hallazgos reales y backlog | `semana-09/` | 10 sondas automáticas en Chrome: 3 cumplen y 7 no | Esta entrega | — | Sin pruebas con usuarios; corrección en la S10 |
| 10 | Movilidad de 320 a 430 px, breakpoints, 5 pantallas, 2 escenarios con conexión limitada y corrección de H1–H4, H6 y H7 | `semana-10/` | Recorrido 9/9 en 3 anchos; móviles 4/4; sondas 9/10; `ui-mensajes` 7/7 | #866 y esta entrega | #868 | H5 (lista de camas) en la integración; sin dispositivo físico |
| 11 | Prototipo navegable en escritorio y móvil: mapa, camino feliz, error crítico y simulaciones | `semana-11/` | Recorrido 9/9 a 1024 y 375 px; error crítico 2/2 | Esta entrega (capturas en #873) | — | Revisión humana de la S11 sin confirmar |
| 12 | Segundo parcial: 5 diapositivas, recorrido, matriz de 14 decisiones de UX y cambio práctico (Escape y foco, simulado) | `semana-12/` | `teclado.mjs` 1/4 → 4/4; recorrido 9/9; `ui-mensajes` 7/7 | Esta entrega | — | Sin defensa oral declarada; revisión humana de la S12 sin confirmar |

## Funcionalidad comprobada hasta hoy

- Traslado y alta por consola contra PostgreSQL con datos ficticios: el traslado cierra el origen y abre el destino contiguo en el mismo episodio; el alta cierra estancia y episodio con el mismo T. Los rechazos no escriben y un error real después de la primera escritura se revierte por completo.
- Prototipo HTTP (Semana 5) y prototipo de interfaz (Semana 8) probados contra PostgreSQL. Todavía no hay autenticación real, tenant, RBAC ni interfaz (semanas 16–17).

## Revisión humana

- 21/10/2026: la autora confirmó la revisión personal de las semanas 3 a 10 (PR #790–#869); ver el [registro](./revision-humana.md). No es una aprobación independiente: ningún PR la tiene.
- Semanas 1 y 2: siguen pendientes de confirmación.
