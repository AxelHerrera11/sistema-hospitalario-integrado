# Tablero de indicadores (dashboard base) - Semana 4

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-26` | **Issue:** [#209](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/209)
- **Proceso:** consulta del tablero de indicadores por periodo
- **Principio SOLID aplicado:** Single Responsibility (SRP)
## Entregables

- [Diagrama MVC + Repository](./01-mvc-repository.svg) | [fuente](./fuentes/01-mvc-repository.puml)
- [Integracion con repositorio de datos compartido](./02-repositorio-compartido.svg) | [fuente](./fuentes/02-repositorio-compartido.puml)
- Codigo ejecutable [Micro-HIS MVC + Repository](./micro-his/); requisitos, criterios y evidencia abajo.

## Capas, responsabilidades y objetos reutilizables

La entrada se organiza con MVC y el acceso a datos con el patron Repository. El controlador no
tiene SQL ni reglas; la interfaz `IndicadorRepository` vive en el Dominio y la Infraestructura
provee dos adaptadores intercambiables.

| Capa | Responsabilidad | Artefactos |
|---|---|---|
| Presentation (MVC) | Router y controlador: HTTP al caso de uso | `public/index.php`, `Presentation/TableroController.php` |
| Application | Caso de uso armar el tablero (read model) | `ObtenerTablero` |
| Domain | Entidad `Indicador` e interfaz `IndicadorRepository` | `src/Domain/` |
| Infrastructure | Adaptadores `IndicadorRepositoryPDO` y `IndicadorRepositoryInMemory` | `src/Infrastructure/` |

El modulo se integraria a un repositorio de datos compartido del HIS implementando la misma
interfaz `IndicadorRepository` sobre el esquema comun (tenant + periodo), sin cambiar Dominio ni
controlador (ver `02-repositorio-compartido.puml`).

## Requisitos, criterios de aceptacion y evidencia

- **RF-01**: consultar el tablero de un periodo por la entrada MVC (HTTP).
- **RF-02**: rechazar la consulta cuando no se indique periodo.
- **RF-03**: devolver cada indicador con su valor, meta y si cumple la meta.
- **RF-04**: persistir y recuperar indicadores mediante la interfaz `IndicadorRepository`.
- **RF-05**: intercambiar el adaptador (PDO o InMemory) sin cambiar controlador ni dominio.
- **RNF-01**: PHP 8.2+ sin framework; controlador sin SQL ni reglas; Repository en el Dominio.
- **AC-01**: responde `200` con el tablero y `422 periodo_requerido` sin periodo.
- **AC-02**: el total y el conteo de cumplimiento de meta se calculan en el caso de uso.
- **AC-03**: ambos adaptadores cumplen el mismo contrato sin tocar el controlador.
- **AC-04**: `src/Presentation/` y `src/Application/` no contienen SQL.

Evidencia (desde `micro-his/`): `php -d zend.assertions=1 -d assert.exception=1 tests/ejecutar_pruebas.php`
(3 pruebas, 0 fallidas); y con `php -S localhost:8080 -t public`, la entrada MVC responde
`200` con el tablero y `422` cuando falta el periodo.

## Regeneracion

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-26-dashboard-base/semana-04/fuentes/*.puml -o ..
```

Este entregable corresponde a la Semana 4 del modulo; el PR apuntara a `develop` con `Refs #209`.
