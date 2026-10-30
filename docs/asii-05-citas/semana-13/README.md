# Gestion de citas medicas y agenda - Semana 13

> Tema guia: **Calidad del software y revisiones tecnicas formales**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-05` | **Issue:** [#207](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/207)
- **Proceso:** registro y consulta de citas medicas validando disponibilidad
- **Principio SOLID aplicado:** Dependency Inversion (DIP)
## Entregables

- Plan de revision tecnica formal (RTF) con roles, criterios y agenda (este documento).
- [Checklist de 14 items](./checklist.md).
- [Proceso de revision](./01-proceso-rtf.svg) | [fuente](./fuentes/01-proceso-rtf.puml)
- Formato de hallazgos y resultado de una revision piloto sobre el Micro-HIS de la Semana 4.

## Plan de revision

| Elemento | Definicion |
|---|---|
| Objeto | `semana-04/micro-his/` (MVC + Repository, PR #1182) y el borde REST de la Semana 5 |
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
| RTF-01 | `semana-04/micro-his/src/Presentation/CitaController.php:35` | C-04 | Mayor | El status HTTP se obtiene cortando el texto de la excepcion; cambiar el mensaje rompe la respuesta. | Excepciones con propiedades `status` y `code`. | Abierto |
| RTF-02 | `semana-04/micro-his/src/Presentation/CitaController.php:29` | C-03 | Mayor | Campos faltantes llegan vacios al dominio; `'' >= ''` produce 409 `cita_rango_invalido` en lugar de 422. | Validar obligatorios en el borde (ya mitigado por el Router de la Semana 5). | Abierto |
| RTF-03 | `semana-04/micro-his/src/Domain/Cita.php:30` | C-09 | Mayor | Compara fechas como cadenas sin validar ISO 8601 ni zona horaria (`2026-11-2T9:00` ordena mal). | Value Object `Horario` con `DateTimeImmutable` y zona del tenant. | Abierto |
| RTF-04 | `semana-04/micro-his/src/Application/AgendarCita.php:22` | C-12 | Menor | Carga todas las citas para detectar solape (O(n)) sin filtrar por doctor/paciente/fecha. | Consulta `solapadas(paciente, doctor, rango)` en el Repository; en produccion respalda la exclusion GiST. | Abierto |
| RTF-05 | `semana-04/micro-his/src/Infrastructure/CitaRepositoryPDO.php:20` | C-06 | Mayor | La tabla de citas no tiene columna de tenant; dos hospitales compartirian agenda. | Agregar `tenant_id` y filtrar en cada consulta (en el HIS lo impone la base HOSPITAL). | Abierto |

**Resumen:** 0 critico(s), 4 mayor(es), 1 menor(es). Con hallazgos mayores abiertos el
producto **no cumple el criterio de salida**; las correcciones se planifican en el issue #207 y se
verifican con las pruebas de la Semana 15.

## Regeneracion de diagramas

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-05-citas/semana-13/fuentes/*.puml -o ..
```
