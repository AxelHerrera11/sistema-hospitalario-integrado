# Plan de revisión técnica

## Objetivo

Determinar qué garantías de Pacientes existen en código y cuáles son solo
contrato/diseño, identificar inconsistencias y recomendar el siguiente paso
seguro de integración sin modificar el módulo.

## Alcance

- Casos de uso de alta, consulta por código, edición, reglas y errores.
- Separación por capas y Repository.
- OpenAPI frente a presenter, serialización e implementación.
- IDs locales/globales, restricciones y persistencia PDO/PostgreSQL.
- Permisos declarados y su aplicación efectiva en rutas; tenant, JWT y frontend.
- Pruebas disponibles, UI y documentación presente.

Fuera de alcance: cambio de código, auditoría de despliegue/configuración
productiva, prueba de penetración, revisión de conformidad WCAG y validación
de enlace MPI en un servicio activo.

## Criterios principales

1. Registro y validación de requeridos.
2. Consulta exacta y resultado no encontrado.
3. Edición e inmutabilidad de identidad.
4. Duplicados y unicidad bajo concurrencia.
5. Validación de fecha futura y tipos de dato.
6. Correspondencia `local_patient_id` / `id` / `patient_id`.
7. Semántica y validación de `global_id`/MPI.
8. Código local, constraints y consulta parametrizada.
9. Exposición de información sensible.
10. Separación Presentation/Application/Domain/Persistence.
11. Contrato de errores y estados HTTP.
12. Coherencia y cobertura del OpenAPI.
13. Permisos en rutas y fuente de permisos frontend.
14. Tenant/JWT/RBAC, repositorio PostgreSQL, pruebas y documentación.

Los 14 criterios se verifican individualmente en [checklist.md](./checklist.md).

## Entradas

Código presente bajo `micro-his/pacientes/`; `openapi.yaml`; pruebas
`micro-his/pacientes/tests/` y pruebas Laravel relacionadas con integridad
PostgreSQL; migraciones hospitalarias/centrales relacionadas; documentos
`docs/asii-03-pacientes/`; RBAC, rutas, middlewares, autenticación y router
frontend actuales.

Después de `git fetch origin` y `git pull --ff-only origin develop`,
`origin/develop` quedó en `1b2ca46`. La rama de trabajo también quedó en ese
SHA; no se integraron cambios mediante merge/rebase.

## Método y roles

1. Confirmar árbol y rama; actualizar refs y fast-forward solo `develop`.
2. Enumerar semanas y superficies existentes.
3. Seguir cada operación desde controller hasta dominio/repositorio.
4. Contrastar esquema, OpenAPI, RBAC, rutas y payload frontend.
5. Distinguir pruebas en memoria, pruebas de esquema PostgreSQL y pruebas
   directas al PDO del módulo.
6. Registrar evidencia, estado, impacto y una recomendación accionable.

Revisión ejecutada por asistente técnico. Seguimiento recomendado por áreas:
responsable Pacientes (Domain/Application/Presentation/PDO adapter), equipo
Laravel/API, equipo identidad/Tenant/RBAC y equipo de base de datos. Sus
responsabilidades son propuestas; no indican asignación confirmada.

## Criterios de salida

- 12–15 criterios con evidencia y estado defendible.
- Solo hallazgos demostrables; prioridad y responsabilidad/área indicada.
- Delimitar si la API/UI están cableadas o solo documentadas.
- Describir primer paso y dependencias para la secuencia HTTP completa.
- Suite PHP ejecutada, prueba disponible de permisos ejecutada y diff revisado.
- No tocar código/migraciones ni superar 400 líneas documentales.
