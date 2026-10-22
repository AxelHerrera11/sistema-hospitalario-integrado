# Dashboard base UI/UX e implementación transversal - Semana 1

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-26`
- **Issue:** [#209](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/209)
- **Proceso:** navegación del dashboard con estados de carga, vacío, error y resumen inicial

## Entregables

- [Narrativa de alcance y trazabilidad](./narrativa-alcance-trazabilidad.md)
- [Casos de uso](./01-casos-de-uso.svg) | [fuente PlantUML](./fuentes/01-casos-de-uso.puml)
- [Actividad](./02-actividad.svg) | [fuente PlantUML](./fuentes/02-actividad.puml)
- [Secuencia](./03-secuencia.svg) | [fuente PlantUML](./fuentes/03-secuencia.puml)
- [Declaración de uso de IA](./DECLARACION_IA.md)

## Regeneración

Desde la raíz del repositorio:

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-26-dashboard-base/semana-01/fuentes/*.puml -o ..
```

Los SVG se generaron con PlantUML 1.2026.8 a partir de las fuentes `.puml`. Este entregable corresponde a la Semana 1 del módulo; el PR apunta a `develop` con `Refs #209`.
