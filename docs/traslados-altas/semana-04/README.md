# Traslados, altas y liberación de camas — Semana 4

## Ficha

- **Estudiante:** María Yamilet Lindo Pablo · **GitHub:** [`Yamilet1235`](https://github.com/Yamilet1235)
- **Módulo:** `ASII-08` · **Issue:** `Refs #9` · **Destino:** `develop`
- **Asignación:** continuar el mismo micro-HIS con MVC y Repository: controller sin SQL ni reglas, contrato Repository con adaptadores PDO e InMemory, diagrama de la integración con el repositorio compartido y explicación de responsabilidades y reutilización.

## Contenido

- [MVC, contrato Repository y adaptadores](./mvc-repository.md)
- [Integración con el repositorio de datos compartido HOSPITAL](./repositorio-compartido.md)
- [Declaración de uso de IA](./DECLARACION_IA.md)

## Cumplimiento

| Pedido | Evidencia | Estado |
|---|---|---|
| Mismo micro-HIS | `micro-his/traslados-altas`, evolucionado desde la Semana 3 | Cumplido |
| Controller sin SQL ni reglas | `ConsolaTraslados`; `tests/arquitectura.php` (Presentation sin PDO, Persistence ni SQL) | Cumplido y verificado |
| Contrato Repository | `Application/RegistroInternacion` | Cumplido |
| Adaptadores PDO e InMemory | `PdoRegistroInternacion` e `InMemoryRegistroInternacion`, con la misma batería `tests/contrato-registro.php` | Cumplido: 8/8 en cada uno |
| Integración con el repositorio compartido | [repositorio-compartido.md](./repositorio-compartido.md) | Diagramado; hechos comprobados y propuestas separados |
| Responsabilidades y reutilización | [mvc-repository.md](./mvc-repository.md) | Cumplido |

## Resultados (21/10/2026, entorno local de verificación)

| Comando | Resultado |
|---|---|
| `php micro-his/traslados-altas/tests/run.php` | `OK: 72 verificaciones aprobadas` |
| `TRASLADOS_PG_DSN=…his_hospital_traslados_test php micro-his/traslados-altas/tests/postgres.php` (PostgreSQL 17.10, base desechable) | `OK: 20 verificaciones aprobadas` |
| `bin/consola.php alta no-es-uuid` con HOSPITAL inaccesible | Salida 64 (antes 2): la solicitud se valida antes de conectar |
| `bin/consola.php alta <uuid>` con HOSPITAL inaccesible | Salida 2, `HOSPITAL_NO_DISPONIBLE` |

## PR

| Parte | PR | Promoción |
|---|---|---|
| 1/2 Adaptador InMemory y contrato común | #802 | #803 |
| 2/2 Controller que valida antes de conectar y esta documentación | Este PR | — |
