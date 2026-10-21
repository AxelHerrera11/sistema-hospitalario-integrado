# Dashboard base UI/UX e implementación transversal - Semana 2

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-26`
- **Issue:** [#209](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/209)
- **Proceso:** navegación del dashboard con estados de carga, vacío, error y resumen inicial
- **Principio SOLID aplicado:** Single Responsibility (SRP)

## Entregables

- [RF/RNF y criterios de aceptación](./rf-rnf-criterios.md)
- [Diseño SOLID (SRP): antes y después](./solid-srp.md)
- [Evidencia verificable](./evidencia-verificable.md)
- [Diseño antes](./01-diseno-antes.svg) | [fuente PlantUML](./fuentes/01-diseno-antes.puml)
- [Diseño después](./02-diseno-despues.svg) | [fuente PlantUML](./fuentes/02-diseno-despues.puml)
- [Demo ejecutable SRP](./evidencia/demo-srp.php)

## Regeneración

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-26-dashboard-base/semana-02/fuentes/*.puml -o ..
php docs/asii-26-dashboard-base/semana-02/evidencia/demo-srp.php
```

Este entregable corresponde a la Semana 2 del módulo; el PR apunta a `develop` con `Refs #209`.
