# Gestión de citas - Semana 2

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-05`
- **Issue:** [#207](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/207)
- **Proceso:** solicitud, validación de disponibilidad, confirmación o cancelación de cita
- **Principio SOLID aplicado:** Dependency Inversion (DIP)

## Entregables

- [RF/RNF y criterios de aceptación](./rf-rnf-criterios.md)
- [Diseño SOLID (DIP): antes y después](./solid-dip.md)
- [Evidencia verificable](./evidencia-verificable.md)
- [Diseño antes](./01-diseno-antes.svg) | [fuente PlantUML](./fuentes/01-diseno-antes.puml)
- [Diseño después](./02-diseno-despues.svg) | [fuente PlantUML](./fuentes/02-diseno-despues.puml)
- [Demo ejecutable DIP](./evidencia/demo-dip.php)

## Regeneración

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-05-citas/semana-02/fuentes/*.puml -o ..
php docs/asii-05-citas/semana-02/evidencia/demo-dip.php
```

Este entregable corresponde a la Semana 2 del módulo; el PR apunta a `develop` con `Refs #207`.
