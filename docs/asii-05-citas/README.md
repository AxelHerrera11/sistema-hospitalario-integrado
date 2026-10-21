# Citas médicas — ASII-05

- **Estudiante:** Lis Ivette Rosales Colindrez ([`Lis671`](https://github.com/Lis671))
- **Módulo:** ASII-05 — agenda de citas con anti-solape, confirmación y cancelación
- **Límite de integración:** contrato **antes del código**; PR en `develop` **mar 20/10** (plan de integración 17–23 oct)
- **Principio SOLID asignado:** DIP (dependencia de abstracciones)

## Contenido

| Entregable | Archivo | Estado |
|---|---|---|
| Contrato API (endpoints, reglas de la tabla `appointments`, errores, permisos) | [contrato-api.md](./contrato-api.md) | Propuesto, pendiente del acuerdo de formato común (mar 20) y del catálogo de María (RBAC) |
| Declaración de uso de IA | [DECLARACION_IA.md](./DECLARACION_IA.md) | — |

## Relación con otros módulos

- **ASII-03 (pacientes)** aporta `local_patients`; **ASII-09/23 (médicos)** aporta `doctor_profiles` y `doctor_specialties`; este módulo no crea ni edita esos catálogos.
- **ASII-26 (dashboard)** define el layout y las pantallas dentro de las que se registra la agenda (`citas.ver` en el menú).
- **ASII-27 (reportes)** consume `appointments` para citas por día/estado; no duplica la lógica.
- La migración de `appointments` **ya existe** en `develop` con exclusiones GiST y triggers: este módulo solo agrega API y UI.

## Estado del código

Sin implementar en este PR: el contrato precede al código. La tarea siguiente (`feature/asii-05-02-dominio-citas`) materializa el puerto DIP y el controlador.
