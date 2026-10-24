# Tablero de indicadores (dashboard base) - Semana 14

> Tema guia: **Confiabilidad y aseguramiento de calidad (SQA)**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-26` | **Issue:** [#209](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/209)
- **Proceso:** consulta del tablero de indicadores por periodo
- **Principio SOLID aplicado:** Single Responsibility (SRP)
## Entregables

- Plan SQA con 7 metricas, matriz de riesgos, controles preventivos/detectivos y calendario (este documento).
- [Ciclo SQA](./01-ciclo-sqa.svg) | [fuente](./fuentes/01-ciclo-sqa.puml)
- Medicion real: [`evidencia/metricas.php`](./evidencia/metricas.php) sobre el Micro-HIS fusionado de la Semana 3.

## Objetivo y alcance

Asegurar que cada entrega del modulo `ASII-26` sea confiable (no rompe a otros modulos ni filtra datos)
y verificable (cada requisito tiene una prueba). Aplica al codigo de `semana-03` a `semana-05` y a
todos los PR del modulo hasta la entrega final.

## Metricas

| ID | Metrica | Formula | Umbral | Fuente | Frecuencia |
|---|---|---|---|---|---|
| M-01 | Tasa de pruebas que pasan | pasan / total | 100 % | runner de pruebas | cada PR |
| M-02 | Densidad de pruebas | pruebas por 100 LOC de `src` | >= 2.0 | `metricas.php` | cada PR |
| M-03 | Violaciones de capa | archivos de `Domain` con PDO/HTTP | 0 | `metricas.php` | cada PR |
| M-04 | Densidad de defectos RTF | hallazgos criticos+mayores / KLOC revisadas | <= 15 | revision RTF (Semana 13) | cada revision |
| M-05 | PR dentro del limite | PR <= 400 lineas / PR totales | 100 % | `git diff --numstat` | cada PR |
| M-06 | Tiempo de cierre de criticos | dias habiles desde el registro | <= 2 | issue #209 | semanal |
| M-07 | Accesibilidad automatica | chequeos sin observacion / total | 12/12 | `auditoria.php` (Semana 9) | cada PR de UI |

### Medicion actual (linea base)

```text
  LOC Application    17
  LOC Domain         32
  LOC Persistence    25
  LOC total src       74

M-01 Pruebas que pasan:        5/5 (100 %)
M-02 Pruebas por 100 LOC:      6.76
M-03 Violaciones de capa:      0
```

Con la linea base, M-01, M-02 y M-03 cumplen su umbral. La suite de la Semana 15 agrega 9 pruebas
sobre la Semana 4. M-04 parte de la revision piloto de la Semana 13, cuyos hallazgos criticos/mayores
siguen abiertos.

## Matriz de riesgos (probabilidad x impacto, escala 1-3)

| ID | Riesgo | Prob. | Impacto | Nivel |
|---|---|---|---|---|
| R-01 | Cambio de contrato rompe a un modulo consumidor | 2 | 3 | 6 Alto |
| R-05 | Indicador mal interpretado (sentido de la meta) | 2 | 3 | 6 Alto |
| R-03 | Regresion al refactorizar | 2 | 2 | 4 Medio |
| R-04 | PR demasiado grande para revisarlo bien | 2 | 2 | 4 Medio |
| R-06 | Fuente caida deja el tablero vacio | 2 | 2 | 4 Medio |
| R-02 | Fuga de datos entre tenants | 1 | 3 | 3 Medio |

## Controles

| Riesgo | Preventivo | Detectivo |
|---|---|---|
| R-01 | Contrato versionado y aprobado antes del codigo (Semana 5) | Pruebas de contrato en cada PR |
| R-02 | Item C-06 del checklist; tenant desde la cabecera, nunca como filtro | Prueba: recurso de otro tenant responde 404 |
| R-03 | Refactor con escenarios de equivalencia (`verificar.php`, Semana 7) | Suite de la Semana 15 en cada PR |
| R-04 | Limite de 400 lineas por PR y una semana por PR | Conteo `git diff --numstat` antes de abrir el PR |
| R-05 | Campo `sentido` y texto "menor/mayor es mejor" (RTF-01) | Prueba UNIT-01 de limites y revision con direccion medica |
| R-06 | Ultimos datos con hora + estado de error | Monitoreo de respuestas 5xx de Citas y Ocupacion |

## Calendario de seguimiento

| Semana | Fechas | Actividad | Responsable |
|---|---|---|---|
| 14 | 26-30 oct 2026 | Linea base de metricas; aprobar plan SQA | Autora + moderador RTF |
| 15 | 2-6 nov 2026 | Ejecutar suite S15; medir M-01, M-02, M-05; cerrar criticos (M-06) | Autora |
| 16 | 9-13 nov 2026 | Re-revision RTF de correcciones; auditoria de UI (M-07); informe final | Autora + revisores |
| Continuo | cada PR | Checklist de PR: pruebas en verde, <= 400 lineas, sin violaciones de capa | Autora |

## Confiabilidad

- **Fallar cerrado:** sin permisos o sin tenant, el sistema niega (menu, API) en vez de mostrar.
- **Errores previsibles:** todo error tiene `code` estable y no expone trazas (formato comun ASII-24).
- **Recuperacion:** las pantallas conservan los datos ante 401/409/422 y fallas de red (Semana 11).

## Regeneracion de diagramas

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-26-dashboard-base/semana-14/fuentes/*.puml -o ..
```
