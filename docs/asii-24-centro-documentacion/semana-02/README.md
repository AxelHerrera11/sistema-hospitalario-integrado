# Centro de documentación y manuales por rol - Semana 2

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-24`
- **Issue:** [#208](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/208)
- **Proceso:** publicación y consulta de documentación y manuales diferenciados por rol
- **Principio SOLID aplicado:** Interface Segregation (ISP)

## Entregables

- [RF/RNF y criterios de aceptación](./rf-rnf-criterios.md)
- [Diseño SOLID (ISP): antes y después](./solid-isp.md)
- [Evidencia verificable](./evidencia-verificable.md)
- [Diseño antes](./01-diseno-antes.svg) | [fuente PlantUML](./fuentes/01-diseno-antes.puml)
- [Diseño después](./02-diseno-despues.svg) | [fuente PlantUML](./fuentes/02-diseno-despues.puml)
- [Demo ejecutable ISP](./evidencia/demo-isp.php)

## Regeneración

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-24-centro-documentacion/semana-02/fuentes/*.puml -o ..
php docs/asii-24-centro-documentacion/semana-02/evidencia/demo-isp.php
```

Este entregable corresponde a la Semana 2 del módulo; el PR apunta a `develop` con `Refs #208`.
