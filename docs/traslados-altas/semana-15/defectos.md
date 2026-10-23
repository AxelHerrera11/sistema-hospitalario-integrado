# Resultados y defectos reales

## Resultados de la ejecución (21/10/2026, develop `159de67`)

| Suite | Resultado |
|---|---|
| `tests/run.php` (sin base) | 101/101 |
| `tests/postgres.php` (PostgreSQL 17.10 desechable) | 20/20 |
| `tests/concurrencia.php 10` (dos procesos y conexiones independientes) | 20/20 después de la corrección; 8/10 antes |
| `tests/ui-mensajes.test.mjs` | 7/7 |

## Defectos encontrados durante el proyecto

Cada uno se observó en una ejecución real y se corrigió con una prueba que lo cubre.

| ID | Semana | Defecto | Cómo se detectó | Corrección | Prueba |
|---|---|---|---|---|---|
| D1 | 4 | Con HOSPITAL caído, unos argumentos inválidos devolvían 2 y no 64 | Recorrido de consola | Validar antes de conectar (#846) | `presentacion.php` |
| D2 | 7 | Un `motivo` enviado como arreglo JSON se guardaba como «Array» (201) | Revisión del refactor y prueba contra el código anterior | Los campos no escalares cuentan como ausentes (#856) | `ejecutor.php` |
| D3 | 9 | Consultar sin actor mostraba «La estancia no existe» | Sonda S2 | Validar actor y estancia en la interfaz (#866) | `ui-mensajes.test.mjs` y sonda S2 |
| D4 | 10 | «Procesando…» no se anunciaba en la región `aria-live` | Recorrido a 320 px | Anunciar al enviar (#866) | `recorrido-ui.mjs` |
| D5 | 15 | Concurrencia: el perdedor de dos traslados a la misma cama terminaba en `FalloConfirmado` y no en `DESTINO_OCUPADO` | `concurrencia.php` C2 (3/5) | Comprobar si la cama está libre después del bloqueo (#895) | `concurrencia.php` C2 (10/10) |
| D6 | 12 | Tras «Cancelar» o «Volver», el foco quedaba en un botón oculto | `teclado.mjs` (1/4) | Devolver el foco y retroceder con Escape (#884) | `teclado.mjs` (4/4) |
| D7 | 6 y 8 | Dos nombres de archivo citados en `docs/` (el punto de entrada original de la consola y el primer recorrido de la interfaz) contenían el nombre del módulo seguido de un punto y la extensión. `PublishedContractsTest` los habría leído como permisos | Búsqueda del patrón de la prueba | Renombrar a `bin/consola.php` y `recorrido-ui.mjs` | Búsqueda en cada PR de documentación |

## Pendientes de prueba

- Corte real de la conexión **durante** el COMMIT: el resultado incierto solo se probó con el doble y con red emulada.
- Permisos por rol y tenant: no existen todavía (semanas 16–17).
