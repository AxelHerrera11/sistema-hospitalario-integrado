# Reportes y analytics básicos - Semana 1

## Ficha

- **Estudiante:** María Yamilet Lindo Pablo
- **GitHub:** [`Yamilet1235`](https://github.com/Yamilet1235)
- **Identificador:** `ASII-23`
- **Issue:** [#152](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/152)
- **Odoo:** tarea `#3613`
- **Proceso:** cálculo y visualización trazable de un indicador hospitalario

## Indicador propuesto

Se modela `IND-RA-01`, **tasa de cancelación de hospitalizaciones**, versión de fórmula `TCI-1.0`. El indicador, su nombre y su fórmula son una propuesta exclusiva para esta tarea: no constituyen una definición clínica oficial ni un requisito explícito del docente. El esquema HOSPITAL contiene los campos necesarios en `care_episodes`.

## Entregables

- [Narrativa de alcance y trazabilidad](./narrativa-alcance-trazabilidad.md)
- [Casos de uso](./01-casos-de-uso.svg) | [fuente PlantUML](./fuentes/01-casos-de-uso.puml)
- [Actividad](./02-actividad.svg) | [fuente PlantUML](./fuentes/02-actividad.puml)
- [Secuencia](./03-secuencia.svg) | [fuente PlantUML](./fuentes/03-secuencia.puml)
- [Declaración de uso de IA](./DECLARACION_IA.md)

## Regeneración

Desde la raíz del repositorio:

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/reportes-analytics/semana-01/fuentes/*.puml -o ..
```

El PR futuro deberá usar `Refs #152` (no `Closes`) y apuntar a `develop`.
