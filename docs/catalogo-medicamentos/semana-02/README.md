# ASII-14 - Catálogo de medicamentos - Semana 2

- **Estudiante:** María Yamilet Lindo Pablo ([`Yamilet1235`](https://github.com/Yamilet1235))
- **Tema:** proceso y modelo de diseño; principios SOLID (ISP)
- **Flujo:** alta, búsqueda y control de vigencia de un medicamento
- **Issue:** [#165](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/165) (`Refs #165`)

## Entregables

La semana se entrega en dos PR para respetar el máximo de 400 líneas por PR.

| Parte | Entregable | Documento | Estado |
|---|---|---|---|
| 1 | RF/RNF, criterios de aceptación y supuestos | [rf-rnf-criterios.md](./rf-rnf-criterios.md) | Incluido |
| 1 | Declaración de uso de IA | [DECLARACION_IA.md](./DECLARACION_IA.md) | Incluido |
| 2 | Ejemplo SOLID: diseño antes/después con ISP, responsabilidades, dependencias y evidencia | `diseno-isp.md`, `EVIDENCIA.md` | Pendiente del siguiente PR |

## Trazabilidad con Semana 1

| Caso de uso (Semana 1) | RF | Criterios |
|---|---|---|
| UC-MED-01 Dar de alta | RF-01, RF-08 | CA-01, CA-10 |
| UC-MED-06 Validar datos e identidad | RF-02, RF-03 | CA-02, CA-03 |
| UC-MED-02 Buscar | RF-04 | CA-04, CA-05, CA-06 |
| UC-MED-03 Consultar vigencia | RF-05 | CA-07 |
| UC-MED-04 Cambiar vigencia | RF-06, RF-08 | CA-07, CA-08, CA-10 |
| UC-MED-05 Validar acceso | RF-07 | CA-09 |
| Diseño (ISP) | RNF-04 | CA-11 |

## Estado real

- **Existente en el repositorio:** casos de uso de Semana 1, base HOSPITAL por hospital y RBAC sin permisos de medicamentos. Slice H5 de `medication` pendiente.
- **Propuesto en esta entrega:** requisitos, criterios y supuestos.
- **No implementado:** ninguna funcionalidad productiva, API, UI, migración ni permiso. El Micro-HIS ejecutable empieza en Semana 3.
