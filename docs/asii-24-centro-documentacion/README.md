# Contratos API y centro de documentación — ASII-24

- **Estudiante:** Lis Ivette Rosales Colindrez ([`Lis671`](https://github.com/Lis671))
- **Módulo:** ASII-24 — especificación OpenAPI/Postman y documentación técnica en el centro de documentación y manuales por rol
- **Límite de integración:** contrato **antes del código**; PR en `develop` **jue 22/10 antes de las 12:00** (plan de integración 17–23 oct)
- **Principio SOLID asignado:** ISP (interfaces segregadas)

## Contenido

| Entregable | Archivo | Estado |
|---|---|---|
| Especificación OpenAPI base (auth, seguridad, errores) | [openapi.yaml](./openapi.yaml) | Propuesto; los paths de cada módulo se agregan cuando su contrato se fusiona |
| Declaración de uso de IA | [DECLARACION_IA.md](./DECLARACION_IA.md) | — |

## Aplicación de ISP

- La base documenta **solo** lo transversal: esquemas de seguridad (`bearerAuth`, `tenantHeader`), respuestas de error y paths de `auth` (los únicos existentes en `routes/api.php`).
- Cada módulo mantiene su propio contrato (p. ej. `docs/asii-05-citas/contrato-api.md`, `docs/asii-27-reportes/contrato-filtros-export.md`); el catálogo no obliga a un consumidor a depender de paths que no usa.
- Las secciones se agregan, no se reescriben: los `$ref` publicados son estables.

## Relación con otros módulos

- **ASII-05 (citas):** `target/asii-05` pendiente de fusionar su contrato y de la tarea de spec (paths `/citas`).
- **ASII-26 (dashboard):** el contrato UI define el `403` y el envoltorio de errores que esta spec refleja como `Error`.
- **ASII-27 (reportes):** reutilizará los filtros de paginación aquí descritos.
- **RBAC (María):** los permisos por operación se anotarán por path cuando el middleware `permission:` exista.

## Estado del código

Sin implementar en este PR: la exportación a Postman/colecciones y los manuales por rol van en las tareas siguientes del módulo.
