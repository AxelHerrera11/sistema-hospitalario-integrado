# Tablero de indicadores (dashboard base) - Semana 13

> Tema guia: **Calidad del software y revisiones tecnicas formales**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-26` | **Issue:** [#209](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/209)
- **Proceso:** consulta del tablero de indicadores por periodo
- **Principio SOLID aplicado:** Single Responsibility (SRP)
## Entregables

- Plan de revision tecnica formal (RTF) con roles, criterios y agenda (este documento).
- [Checklist de 14 items](./checklist.md).
- [Proceso de revision](./01-proceso-rtf.svg) | [fuente](./fuentes/01-proceso-rtf.puml)
- Formato de hallazgos y resultado de una revision piloto sobre el Micro-HIS de la Semana 4.

## Plan de revision

| Elemento | Definicion |
|---|---|
| Objeto | `semana-04/micro-his/` (MVC + Repository, PR #1184) y el borde REST de la Semana 5 |
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
| RTF-01 | `semana-04/micro-his/src/Domain/Indicador.php:31` | C-11 | Mayor | Supone que "mayor es mejor": el tiempo de espera (15 min vs meta 20) se marca como no cumplido. | Agregar `sentido` (`mayor_mejor` / `menor_mejor`) al indicador. | Abierto |
| RTF-02 | `semana-04/micro-his/src/Domain/Indicador.php:23` | C-13 | Mayor | Regresion: la regla de la Semana 3 "rechazar valor negativo" (RF-02) ya no se valida. | Restituir la validacion y su prueba. | Abierto |
| RTF-03 | `semana-04/micro-his/src/Presentation/TableroController.php:19` | C-09 | Menor | Solo valida que el periodo no este vacio; acepta `2026-13` o texto libre. | Validar `AAAA-MM` con mes 01-12. | Abierto |
| RTF-04 | `semana-04/micro-his/src/Application/ObtenerTablero.php:22` | C-12 | Menor | `cumpleMeta()` se calcula dos veces por indicador (contador y fila). | Calcular una vez en una variable local. | Abierto |
| RTF-05 | `semana-04/micro-his/src/Infrastructure/IndicadorRepositoryPDO.php:18` | C-06 | Mayor | La tabla de indicadores no tiene tenant ni clave primaria (duplicados por periodo). | Clave `(tenant_id, clave, periodo)`. | Abierto |

**Resumen:** 0 critico(s), 3 mayor(es), 2 menor(es). Con hallazgos mayores abiertos el
producto **no cumple el criterio de salida**; las correcciones se planifican en el issue #209 y se
verifican con las pruebas de la Semana 15.

## Regeneracion de diagramas

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-26-dashboard-base/semana-13/fuentes/*.puml -o ..
```
