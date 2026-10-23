# Dashboard base UI/UX e implementación transversal - Semana 3

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-26`
- **Issue:** [#209](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/209)
- **Proceso:** resumen inicial del dashboard con indicadores agregados por rol
- **Principio SOLID aplicado:** Single Responsibility (SRP)

## Entregables

- [RF/RNF y criterios de aceptación](./rf-rnf-criterios.md)
- [Evidencia verificable](./evidencia-verificable.md)
- [Diagrama de capas](./01-capas.svg) | [fuente PlantUML](./fuentes/01-capas.puml)
- [Diagrama de contexto C4](./02-contexto-c4.svg) | [fuente PlantUML](./fuentes/02-contexto-c4.puml)
- Código ejecutable [Micro-HIS en capas](./micro-his/)

## Vista arquitectónica

El Micro-HIS del dashboard separa responsabilidades en cuatro capas. Cada capa tiene una
única razón de cambio (SRP) y la dirección de dependencias apunta hacia el Dominio.

| Capa | Responsabilidad | Artefactos |
|---|---|---|
| Presentation | Adapta la entrada CLI al caso de uso | `micro-his/bin/resumen_dashboard.php` |
| Application | Orquesta el armado del resumen | `ResumenDashboard` |
| Domain | Entidad `Indicador`, regla de valor no negativo y puerto `IndicadorRepository` | `src/Domain/` |
| Persistence | Implementaciones en memoria del puerto | `IndicadorRepositoryEnMemoria` |

## Regeneración

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-26-dashboard-base/semana-03/fuentes/*.puml -o ..
php -d zend.assertions=1 -d assert.exception=1 docs/asii-26-dashboard-base/semana-03/micro-his/tests/ejecutar_pruebas.php
```

Este entregable corresponde a la Semana 3 del módulo; el PR apunta a `develop` con `Refs #209`.
