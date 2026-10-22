# Traslados, altas y liberación de camas — Semana 3

## Ficha

- **Estudiante:** María Yamilet Lindo Pablo · **GitHub:** [`Yamilet1235`](https://github.com/Yamilet1235)
- **Módulo:** `ASII-08` — Traslados, altas y liberación de camas · **Issue:** `Refs #9` · **Destino:** `develop`
- **Asignación:** iniciar el micro-HIS en PHP 8.2+ vanilla, sin framework; separar Presentation, Application, Domain y Persistence; usar PDO con consultas preparadas; probar camino feliz, regla de dominio y error de persistencia; y documentar una vista C4/UML con las dependencias.
- **Base:** [Semana 2](../semana-02/README.md) (contrato LSP `OperacionConLiberacionDeCama`, RF-TA-01 a RF-TA-10, RNF-TA-01 a RNF-TA-08).

## Contenido

- [Arquitectura: C4, UML, dependencias y decisiones](./arquitectura.md)
- [Evidencia: comandos, entorno y resultados](./evidencia.md)
- [Declaración de uso de IA](./DECLARACION_IA.md)
- Código: [`micro-his/traslados-altas`](../../../micro-his/traslados-altas/README.md)

## Cumplimiento de la asignación

| Pedido | Dónde | Estado |
|---|---|---|
| PHP 8.2+ vanilla, sin framework | `micro-his/traslados-altas` (autocarga propia, sin Composer) | Cumplido |
| Cuatro capas separadas | `src/Domain`, `src/Application`, `src/Persistence`, `src/Presentation` | Cumplido y verificado por `tests/arquitectura.php` |
| PDO con consultas preparadas | `PdoRegistroInternacion`, `AutorizadorReferenciaActiva` | Cumplido |
| Prueba de camino feliz | `tests/traslado.php`, `tests/alta.php` (doble) y `tests/postgres.php` (PostgreSQL) | Cumplido |
| Prueba de regla de dominio | `tests/reglas.php`, `tests/destino.php` | Cumplido |
| Prueba de error de persistencia | `tests/protocolo.php` (fallas inyectadas en el doble) y `tests/postgres.php` (FK real con reversión) | Cumplido |
| Vista C4/UML y dependencias | [arquitectura.md](./arquitectura.md) | Cumplido |

## Recorrido funcional

Traslado y alta por consola contra una base HOSPITAL desechable con datos ficticios. Los pasos y salidas están en [evidencia.md](./evidencia.md#recorrido-de-consola).

## PR de la semana

| Parte | PR | Promoción a `main` |
|---|---|---|
| 1/6 Dominio: solicitudes y reglas | #790 | #791 |
| 2/6 Planes, resultados y contratos de aplicación | #792 | #793 |
| 3/6 Protocolo común y traslado | #794 | #795 |
| 4/6 Alta, batería LSP y adaptador PDO | #796 | #797 |
| 5/6 Presentación de consola y autorización mínima | #798 | #799 |
| 6/6 Esta documentación | Este PR | — |

## No incluido todavía

- API HTTP, tenant, permisos RBAC por rol e interfaz: se integran con Laravel en las semanas 16–17.
- Concurrencia probada con conexiones independientes.
- Acuerdos con ASII-06 sobre R-TA-01 y `beds.status`.
