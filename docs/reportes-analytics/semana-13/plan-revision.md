# ASII-23 Semana 13 — Plan de revisión técnica formal

**Issue:** `Refs #152`. **Estado:** preparada. La reunión **no** se ha realizado; el cuadro pide prepararla.

## Objetivo y alcance

Verificar que el indicador cumple su regla, su contrato y sus controles de acceso. Entran `micro-his/reportes-analytics/`, la ruta y el controlador de Laravel, `resources/js/modules/reportes/` y las pruebas del módulo. Quedan fuera el esquema HOSPITAL, RBAC y Tenants, que tienen otros dueños.

## Roles

Solo la autora está definida; las demás personas están por confirmar.

| Rol | Responsabilidad | Propuesta |
|---|---|---|
| Autora | Presenta y corrige | María Yamilet Lindo Pablo |
| Moderador | Conduce y decide si hay nueva revisión | Por confirmar |
| Revisor 1 | Regla, datos y consulta | Por confirmar |
| Revisor 2 | Permisos y aislamiento | Por confirmar |
| Secretaría | Registra los hallazgos | Por confirmar |

## Criterios de entrada

- Commit fijado y desplegable en local.
- `run.php`, `tests/Feature/Reportes` y las pruebas de Node en verde.
- Manual del módulo actualizado.
- Material entregado a los revisores con al menos 48 horas de anticipación.

## Criterios de salida

- Cada ítem de la [lista](./checklist-revision-tecnica.md) tiene resultado.
- Sin hallazgos críticos ni altos abiertos.
- Cada hallazgo tiene responsable y fecha.
- Si hubo un hallazgo crítico, se agenda otra revisión.

## Agenda (45 minutos)

| Tiempo | Actividad |
|---|---|
| 5 min | Alcance y criterios de entrada |
| 25 min | Recorrido por la lista; solo se señalan defectos, no se diseñan soluciones |
| 10 min | Clasificación de hallazgos |
| 5 min | Decisión y responsables |

## Formato de hallazgos

| ID | Ítem | Archivo y línea | Descripción | Severidad (crítica, alta, media, baja) | Responsable | Fecha | Estado |
|---|---|---|---|---|---|---|---|
| — | — | — | Aún no hay hallazgos: la revisión no se ha realizado | — | — | — | — |
