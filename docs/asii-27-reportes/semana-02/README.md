# Reportes operativos exportables y filtros administrativos - Semana 2

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-27`
- **Issue:** [#210](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/210)
- **Proceso:** filtrado y exportación reproducible de un reporte operativo
- **Principio SOLID aplicado:** Open/Closed (OCP)

## Entregables

- [RF/RNF y criterios de aceptación](./rf-rnf-criterios.md)
- [Diseño SOLID (OCP): antes y después](./solid-ocp.md)
- [Evidencia verificable](./evidencia-verificable.md)
- [Diseño antes](./01-diseno-antes.svg) | [fuente PlantUML](./fuentes/01-diseno-antes.puml)
- [Diseño después](./02-diseno-despues.svg) | [fuente PlantUML](./fuentes/02-diseno-despues.puml)
- [Demo ejecutable OCP](./evidencia/demo-ocp.php)

## Regeneración

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-27-reportes/semana-02/fuentes/*.puml -o ..
php docs/asii-27-reportes/semana-02/evidencia/demo-ocp.php
```

Este entregable corresponde a la Semana 2 del módulo; el PR apunta a `develop` con `Refs #210`.
