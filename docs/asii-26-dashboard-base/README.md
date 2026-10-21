# Dashboard base UI/UX e implementación transversal — ASII-26

- **Estudiante:** Lis Ivette Rosales Colindrez ([`Lis671`](https://github.com/Lis671))
- **Módulo:** ASII-26 — layout general, navegación por roles, componentes reutilizables y estados de carga/error
- **Límite de integración:** contrato **dom 18/10**, PR en `develop` **lun 19/10** (plan de integración 17–23 oct)

## Contenido

| Entregable | Archivo | Estado |
|---|---|---|
| Contrato UI base (layout, menú por rol, componentes, estados, errores) | [contrato-ui-base.md](./contrato-ui-base.md) | Propuesto, pendiente de validar con Cindy (auth) y María (permisos) |
| Declaración de uso de IA | [DECLARACION_IA.md](./DECLARACION_IA.md) | — |

## Relación con otros módulos

- **ASII-23 (analytics)** y **ASII-27 (reportes exportables)** consumen esta base pero no la sustituyen: este módulo solo entrega layout, navegación, tarjetas iniciales y estados.
- **ASII-05 (citas)**, **ASII-27 (reportes)** y **ASII-24 (documentación)** registran sus pantallas dentro de este layout y siguen su patrón de estados.
- Permisos de navegación dependen del catálogo de **María (RBAC)** y de la sesión de **Cindy (auth)**; hasta entonces el menú falla cerrado (oculta lo que no puede verificar).

## Estado del código

Aún sin implementar en este PR: el contrato precede al código según el plan de integración ("el contrato se publica antes que el código").
