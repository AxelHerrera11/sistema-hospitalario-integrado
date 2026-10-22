# ASII-14 - Catálogo de medicamentos - Semana 7

- **Tema:** componentes backend y frontend y propuesta de refactor
- **Issue:** [#165](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/165)

| Requisito | Evidencia |
|---|---|
| Diagrama de componentes | [01-componentes.svg](./01-componentes.svg) |
| Contratos de entrada y salida | [componentes.md](./componentes.md#contratos-de-entrada-y-salida) |
| Punto de mayor acoplamiento | [componentes.md](./componentes.md#punto-de-mayor-acoplamiento-la-taxonomía-de-errores), con fan-in medido |
| Refactor antes/después y comparación | [02-refactor-errores.svg](./02-refactor-errores.svg) y su tabla |

**Estado:** el núcleo (contratos, casos de uso, Repository) existe y está probado. Los componentes Vue y el controlador Laravel son propuestos, y siguen las convenciones verificadas en `develop`: `resources/js/modules/<modulo>/`, `plugins/axios.js` y `router/permissions.js`.

`can()` hoy deniega siempre, porque el payload de autenticación no incluye `permissions`. La UI no podrá ocultar acciones por permiso hasta que eso se resuelva; el backend sigue siendo la autoridad. El refactor es conceptual y no se aplicó.

[Declaración de uso de IA](./DECLARACION_IA.md)
