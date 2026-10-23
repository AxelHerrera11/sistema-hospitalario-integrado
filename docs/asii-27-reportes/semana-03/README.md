# Reportes operativos exportables y filtros administrativos - Semana 3

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-27`
- **Issue:** [#210](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/210)
- **Proceso:** filtrado y exportación reproducible de un reporte operativo
- **Principio SOLID aplicado:** Open/Closed (OCP)

## Entregables

- [RF/RNF y criterios de aceptación](./rf-rnf-criterios.md)
- [Evidencia verificable](./evidencia-verificable.md)
- [Diagrama de capas](./01-capas.svg) | [fuente PlantUML](./fuentes/01-capas.puml)
- [Diagrama de contexto C4](./02-contexto-c4.svg) | [fuente PlantUML](./fuentes/02-contexto-c4.puml)
- Código ejecutable [Micro-HIS en capas](./micro-his/)

## Vista arquitectónica

El Micro-HIS de reportes separa responsabilidades en cuatro capas. La exportación se extiende
por OCP: un formato nuevo es una clase nueva que implementa `Exportador`, sin modificar el
caso de uso ni los formatos existentes.

| Capa | Responsabilidad | Artefactos |
|---|---|---|
| Presentation | Adapta la entrada CLI al caso de uso | `micro-his/bin/generar_reporte.php` |
| Application | Filtra y exporta el reporte | `GenerarReporte` |
| Domain | Entidad `Reporte`, regla de fechas y contratos `ReporteRepository` / `Exportador` | `src/Domain/` |
| Persistence | Repositorio en memoria y exportadores CSV/JSON | `ExportadorCsv`, `ExportadorJson` |

## Regeneración

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-27-reportes/semana-03/fuentes/*.puml -o ..
php -d zend.assertions=1 -d assert.exception=1 docs/asii-27-reportes/semana-03/micro-his/tests/ejecutar_pruebas.php
```

Este entregable corresponde a la Semana 3 del módulo; el PR apunta a `develop` con `Refs #210`.
