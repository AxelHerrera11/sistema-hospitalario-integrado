# Reportes exportables - Semana 13

> Tema guia: **Calidad del software y revisiones tecnicas formales**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-27` | **Issue:** [#210](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/210)
- **Proceso:** generacion y consulta de reportes operativos por periodo
- **Principio SOLID aplicado:** Open/Closed (OCP)
## Entregables

- Plan de revision tecnica formal (RTF) con roles, criterios y agenda (este documento).
- [Checklist de 14 items](./checklist.md).
- [Proceso de revision](./01-proceso-rtf.svg) | [fuente](./fuentes/01-proceso-rtf.puml)
- Formato de hallazgos y resultado de una revision piloto sobre el Micro-HIS de la Semana 4.

## Plan de revision

| Elemento | Definicion |
|---|---|
| Objeto | `semana-04/micro-his/` (MVC + Repository, PR #1185) y el borde REST de la Semana 5 |
| Objetivo | detectar defectos de correccion, seguridad (tenant, roles) y mantenibilidad antes de integrar |
| Criterios de entrada | PR abierto hacia `develop`, pruebas en verde, menos de 400 lineas, checklist adjunto |
| Duracion | preparacion individual <= 60 min; reunion <= 90 min; ritmo <= 200 LOC/hora |
| Criterios de salida | 0 hallazgos criticos y mayores abiertos; menores con issue asignado |
| Metricas | hallazgos por severidad, densidad (hallazgos/KLOC), tiempo de preparacion |

## Roles

| Rol | Responsabilidad | Asignacion |
|---|---|---|
| Moderador | planifica, conduce la reunion y decide la salida | par del grupo (rota cada revision) |
| Autora | presenta el contexto y corrige; no defiende el codigo en la reunion | Lis Rosales (`Lis671`) |
| Lector | recorre el codigo en voz alta por bloques | par del grupo |
| Revisores (2) | aplican el checklist en la preparacion individual | integrantes de modulos consumidores |
| Secretario | registra hallazgos con el formato definido | par del grupo |

La revision evalua el **producto**, no a la persona; las soluciones se discuten despues de la reunion.

## Formato de hallazgos

| Campo | Contenido |
|---|---|
| ID | `RTF-NN` correlativo |
| Ubicacion | `archivo:linea` |
| Item | ID del checklist incumplido |
| Severidad | **Critico** (seguridad o datos clinicos), **Mayor** (falla funcional), **Menor** (mantenibilidad) |
| Descripcion | que se observa y por que es un defecto |
| Accion | correccion propuesta |
| Estado | Abierto / Corregido / Verificado |

## Revision piloto (Semana 4)

| ID | Ubicacion | Item | Severidad | Descripcion | Accion | Estado |
|---|---|---|---|---|---|---|
| RTF-01 | `semana-04/micro-his/src/Presentation/ReporteController.php:21` | C-07 | Mayor | El contenido del reporte se guarda sin sanear; al exportar a CSV, un valor que empieza con `=` es inyeccion de formulas. | Escapar celdas que empiezan con `= + - @` en el exportador CSV. | Abierto |
| RTF-02 | `semana-04/micro-his/src/Presentation/ReporteController.php:23` | C-04 | Mayor | El status HTTP se deduce del texto de la excepcion. | Excepciones con `status` y `code`. | Abierto |
| RTF-03 | `semana-04/micro-his/src/Domain/Reporte.php:22` | C-13 | Mayor | No se valida el formato del periodo ni el rango maximo de 366 dias del contrato de filtros. | Value Object `Periodo` con las reglas del contrato. | Abierto |
| RTF-04 | `semana-04/micro-his/src/Presentation/ReporteController.php:31` | C-12 | Menor | La validacion de periodo vive en el controlador (listar) y en el Dominio (generar) con codigos distintos. | Una sola validacion en el Dominio. | Abierto |
| RTF-05 | `semana-04/micro-his/src/Infrastructure/ReporteRepositoryPDO.php:18` | C-06 | Mayor | La tabla de reportes no tiene tenant. | Agregar `tenant_id` y filtrar por el tenant de la sesion. | Abierto |

**Resumen:** 0 critico(s), 4 mayor(es), 1 menor(es). Con hallazgos mayores abiertos el
producto **no cumple el criterio de salida**; las correcciones se planifican en el issue #210 y se
verifican con las pruebas de la Semana 15.

## Regeneracion de diagramas

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-27-reportes/semana-13/fuentes/*.puml -o ..
```
