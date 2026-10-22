# Reportes operativos exportables y filtros administrativos — ASII-27

- **Estudiante:** Lis Ivette Rosales Colindrez ([`Lis671`](https://github.com/Lis671))
- **Módulo:** ASII-27 — reportes operativos en CSV/PDF con filtros reproducibles
- **Límite de integración:** contrato **antes del código**; PR en `develop` **jue 22/10 antes de las 12:00** (plan de integración 17–23 oct)
- **Principio SOLID asignado:** OCP (abiertos para extensión, cerrados para modificación)

## Contenido

| Entregable | Archivo | Estado |
|---|---|---|
| Contrato de filtros y exportación (endpoints, CSV, OCP, reportes fuente) | [contrato-filtros-export.md](./contrato-filtros-export.md) | Propuesto, pendiente de los acuerdos del grupo del mar 20 (formato común y PDF) |
| Declaración de uso de IA | [DECLARACION_IA.md](./DECLARACION_IA.md) | — |

## Relación con otros módulos

- **ASII-23 (Yamilet):** indicadores con fórmulas versionadas; este módulo exporta reportes operativos, no duplica analytics.
- **ASII-05 (citas):** el reporte de citas consume `AppointmentRepositoryContract` en lugar de consultar la tabla directamente.
- **ASII-26 (dashboard):** comparte la capa de filtros y los estados de carga/error una vez el grupo acuerde el formato común.
- **ASII-06/09 (camas/ocupación):** fuentes de `wards`, `beds` y `bed_stays` para el reporte de ocupación.

## Estado del código

Sin implementar en este PR: el contrato precede al código. Primera implementación: catálogo + `CsvReportExporter` con el reporte de citas; PDF queda fuera hasta aprobación de dependencia.
