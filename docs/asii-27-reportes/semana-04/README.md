# Reportes exportables - Semana 4

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-27` | **Issue:** [#210](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/210)
- **Proceso:** generacion y consulta de reportes operativos por periodo
- **Principio SOLID aplicado:** Open/Closed (OCP)
## Entregables

- [Diagrama MVC + Repository](./01-mvc-repository.svg) | [fuente](./fuentes/01-mvc-repository.puml)
- [Integracion con repositorio de datos compartido](./02-repositorio-compartido.svg) | [fuente](./fuentes/02-repositorio-compartido.puml)
- Codigo ejecutable [Micro-HIS MVC + Repository](./micro-his/); requisitos, criterios y evidencia abajo.

## Capas, responsabilidades y objetos reutilizables

La entrada se organiza con MVC y el acceso a datos con el patron Repository. El controlador no
tiene SQL ni reglas; la interfaz `ReporteRepository` vive en el Dominio y la Infraestructura
provee dos adaptadores intercambiables.

| Capa | Responsabilidad | Artefactos |
|---|---|---|
| Presentation (MVC) | Router y controlador: HTTP a los casos de uso | `public/index.php`, `Presentation/ReporteController.php` |
| Application | Casos de uso generar y listar reportes | `GenerarReporte` |
| Domain | Entidad `Reporte` e interfaz `ReporteRepository` | `src/Domain/` |
| Infrastructure | Adaptadores `ReporteRepositoryPDO` y `ReporteRepositoryInMemory` | `src/Infrastructure/` |

El modulo se integraria a un repositorio de datos compartido del HIS implementando la misma
interfaz `ReporteRepository` sobre el esquema comun (tenant + periodo), sin cambiar Dominio ni
controlador (ver `02-repositorio-compartido.puml`). Esto permite extender el formato de
exportacion sin modificar el nucleo (abierto a extension, cerrado a modificacion).

## Requisitos, criterios de aceptacion y evidencia

- **RF-01**: generar un reporte para un periodo por la entrada MVC (HTTP).
- **RF-02**: rechazar la generacion cuando el periodo sea vacio.
- **RF-03**: listar los reportes generados para un periodo.
- **RF-04**: persistir y recuperar reportes mediante la interfaz `ReporteRepository`.
- **RF-05**: intercambiar el adaptador (PDO o InMemory) sin cambiar controlador ni dominio.
- **RNF-01**: PHP 8.2+ sin framework; controlador sin SQL ni reglas; Repository en el Dominio.
- **AC-01**: responde `201` al generar y `200` al listar.
- **AC-02**: listar sin periodo responde `422 periodo_requerido`.
- **AC-03**: ambos adaptadores cumplen el mismo contrato sin tocar el controlador.
- **AC-04**: `src/Presentation/` y `src/Application/` no contienen SQL.

Evidencia (desde `micro-his/`): `php -d zend.assertions=1 -d assert.exception=1 tests/ejecutar_pruebas.php`
(3 pruebas, 0 fallidas); y con `php -S localhost:8080 -t public`, la entrada MVC responde
`201`/`200` en el camino feliz y `422` cuando falta el periodo.

## Regeneracion

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-27-reportes/semana-04/fuentes/*.puml -o ..
```

Este entregable corresponde a la Semana 4 del modulo; el PR apuntara a `develop` con `Refs #210`.
