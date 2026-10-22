# Evidencia — Semana 3 (21/10/2026)

Solo datos ficticios y bases desechables. Ninguna base compartida ni productiva.

## Entorno

Windows 11, PHP 8.2.12 (`pdo_pgsql`) y PostgreSQL 17.10 en un clúster local propio (puerto 55433). Las bases `his_hospital_traslados_test` y `his_hospital_traslados_demo_test` se migraron con `database/migrations/hospital` de `develop`. Es un **entorno local de verificación**, no staging ni on-premise.

## Tipos de prueba

| Suite | Qué es real | Qué se sustituye |
|---|---|---|
| `tests/run.php` | Dominio, casos de uso, presentación y reglas de arquitectura | HOSPITAL por `RegistroFalso` (doble en memoria) y la autorización por `AutorizadorFijo` |
| `tests/postgres.php` | Adaptador PDO, PostgreSQL con sus restricciones y triggers, y la autorización mínima | Nada; los datos son ficticios |
| Recorrido de consola | `bin/consola.php` completo contra PostgreSQL | Nada; datos de `database/demo.sql` |

El doble **no** demuestra atomicidad ni concurrencia. La reversión ante un error real se demuestra con PostgreSQL; la concurrencia con conexiones independientes sigue pendiente.

## Resultados

| Comando | Resultado | Salida |
|---|---|---|
| `php micro-his/traslados-altas/tests/run.php` | `OK: 63 verificaciones aprobadas` | 0 |
| `TRASLADOS_PG_DSN=…his_hospital_traslados_test php micro-his/traslados-altas/tests/postgres.php` | `OK: 12 verificaciones aprobadas` | 0 |

`tests/postgres.php` comprueba:
- un error de persistencia real (FK del actor) después de cerrar el origen, y que PostgreSQL revierte también ese cierre;
- `DESTINO_OCUPADO`;
- el traslado persistido, con estancias contiguas en el mismo episodio y sin cambios en `beds.status`;
- `ESTANCIA_NO_VIGENTE`;
- el alta, que cierra estancia y episodio con el mismo T sin tocar otro episodio;
- la autorización mínima: actor activo sí; revocado, inexistente o con id inválido no.

## Recorrido de consola

Base `his_hospital_traslados_demo_test`, cargada con `database/demo.sql` y con `TRASLADOS_ACTOR_REF=00000000-0000-4000-8000-0000000000f1`.

| Paso | Salida | Resultado |
|---|---|---|
| `trasladar …e1 …b3 Aislamiento` (cama C en mantenimiento) | 1 | `DESTINO_NO_DISPONIBLE` |
| `trasladar …e1 …b2 Aislamiento` | 0 | Éxito: cama A liberada y nueva estancia en B |
| `trasladar …e1 …b2 Aislamiento` (repetido) | 1 | `ESTANCIA_NO_VIGENTE` |
| `alta <estancia en B>` | 0 | Éxito: episodio cerrado |
| `alta <estancia en B>` (repetida) | 1 | `EPISODIO_NO_ABIERTO` |
| Argumentos incompletos con HOSPITAL disponible | 64 | `campo: estancia_esperada` |
| `alta …` con un DSN inaccesible | 2 | `HOSPITAL_NO_DISPONIBLE` |

Consulta final en PostgreSQL:
- estancias A y B cerradas, cada una con `started_at < ended_at`;
- episodio `closed`, con `ended_at` igual al cierre de la última estancia;
- `beds.status` sin cambios (A y B `available`, C `maintenance`).

## Defectos y observaciones reales

- El primer intento de escribir `bootstrap.php` con un heredoc de shell colapsó `\\` en `\` (error de sintaxis). Se corrigió antes del commit.
- `bin/consola.php` abre la conexión antes de validar los argumentos. Por eso, con HOSPITAL inaccesible, unos argumentos inválidos devuelven 2 y no 64. No escribe nada; queda anotado para cuando se separe la composición en la integración.
- El punto de entrada se renombró a `bin/consola.php`: la prueba RBAC `PublishedContractsTest` leía el nombre anterior en `docs/` como un permiso.
