# Gobernanza y Auditoría — Semana 8: UX y wireframes

## Objetivo

Diseñar el flujo de consulta de la bitácora para los roles que realmente tienen acceso. Incluye filtros combinables, estados de carga, vacío, error y acceso denegado, la paginación y un detalle que no expone secretos.

## Entregables

| Entregable | Ubicación |
|---|---|
| Flujo principal por rol | [flujo-ux-wireframes.md §2](flujo-ux-wireframes.md#2-flujo-principal-de-consulta), `01-user-flow.svg` |
| Selección y combinación de filtros | W1 y RI-01…04 |
| Estados de carga, vacío y error | W2, W3, W4 y W6 |
| Acceso denegado | W5 |
| Resultados paginados y detalle | W2 |
| 6 wireframes anotados | [§3](flujo-ux-wireframes.md#3-wireframes-anotados) |
| Reglas de visibilidad según permisos | [§1](flujo-ux-wireframes.md#1-roles-con-acceso-y-reglas-de-visibilidad) |

## Archivos creados

`README.md`, `flujo-ux-wireframes.md`, `fuentes/01-user-flow.mmd` y `01-user-flow.svg`.

## Relación con semanas anteriores

Cada estado corresponde a una respuesta del contrato de la Semana 5. Los roles y el permiso vienen de `config/rbac.php` (develop) y de la propuesta `auditoria.ver`.

## Uso

Los wireframes son de texto (ASCII), editables en cualquier editor. El flujo se renderiza desde la fuente Mermaid.

## Pruebas ejecutadas

No aplica (diseño). Los estados se verificaron después en el prototipo de la Semana 11.

## Dependencias pendientes

- Que RBAC decida si `admin_hospital` recibe `auditoria.ver`.
- El endpoint de permisos efectivos (RBAC) para la regla RV-01.

## Limitaciones conocidas

No se presenta como funcional ninguna pantalla Vue. W1–W6 no incluyen exportación porque el contrato no la ofrece.
