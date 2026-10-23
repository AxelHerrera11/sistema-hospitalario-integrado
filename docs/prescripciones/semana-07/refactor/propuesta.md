# Propuesta de refactor

Estado de todas las acciones: `PROPUESTO`; no se reportan refactors ejecutados.

| Orden | Cambio propuesto | Beneficio | Dependencia/criterio de salida |
|---|---|---|---|
| 1 | Mantener dominio y caso de uso independientes de Laravel, HTTP y PDO | Reglas comprobables sin infraestructura | Reglas y excepciones aprobadas |
| 2 | Definir puertos para catálogo, alergias, auditoría y persistencia | Dependencias sustituibles y fallas explícitas | Contratos revisados con los módulos propietarios |
| 3 | Dejar la unidad de trabajo y no-persistencia en rechazo dentro del caso de uso | Evitar guardar respuestas 422 | Pruebas de reglas aprobadas |
| 4 | Añadir API/controller delgado y FormRequest solo cuando se integre Laravel | Separar validación HTTP del dominio | Contrato, RBAC y códigos de respuesta aprobados |
| 5 | Añadir una página Vue protegida por el permiso aprobado | Completar navegación desde el shell existente | API integrada; rutas y permisos acordados |

## Restricciones de diseño

- No cambiar `isGrave()`.
- No guardar una prescripción cuando el resultado sea 422.
- No crear flujo ni endpoint para `excepcion_alergia`; sigue pendiente.
- No crear un fallback que convierta una falla de alergias o persistencia en
  un resultado exitoso.
- No declarar tipos, tablas o métodos como definitivos antes de su revisión.

## Verificación pendiente

Revisar el diagrama junto con el contrato API, la decisión de persistencia y
los permisos. Esta propuesta no acredita que haya compilación, pruebas,
despliegue o evaluación de usuarios.
