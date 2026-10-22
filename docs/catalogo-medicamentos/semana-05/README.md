# ASII-14 - Catálogo de medicamentos - Semana 5

- **Tema:** cliente-servidor, REST e integración del MISMO Micro-HIS (`micro-his/catalogo-medicamentos/`)
- **Flujo:** alta, búsqueda y control de vigencia de un medicamento
- **Naturaleza:** diseño y contrato; no se construye infraestructura productiva ni se divide el sistema
- **Issue:** [#165](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/165)

## Requisito → evidencia

| Requisito del enunciado | Evidencia |
|---|---|
| Contrato API | [contrato-api.md](./contrato-api.md) y [openapi.yaml](./openapi.yaml) (OpenAPI 3.1, `redocly lint` válido) |
| Propiedad de datos, comunicación, seguridad, resiliencia, observabilidad y consistencia | [cliente-servidor.md](./cliente-servidor.md) |
| Diagramas | `01-c4-cliente-servidor.svg` y `02-secuencia-api.svg`, con fuentes en `fuentes/` |
| Frontera de microservicio y migración razonada | [frontera-microservicio.md](./frontera-microservicio.md) |
| Plan de Issue, rama, worktree y PR | [plan-integracion.md](./plan-integracion.md) |
| Evidencia y uso de IA | [EVIDENCIA.md](./EVIDENCIA.md) y [DECLARACION_IA.md](./DECLARACION_IA.md) |

## Coordinación con acuerdos existentes

- **Verificado:** `/api/v1`, JWT y `X-Tenant-ID`, `trace_id` y el permiso `medicamentos.ver`.
- **Propuesto por ASII-14, no acordado:** permisos de escritura, campos de `medication`, FK desde `prescription` y lectura por Prescripciones.

La corrección del supuesto de permisos de las Semanas 2 y 3, que ahora reconoce `medicamentos.ver`, forma parte de esta semana.
