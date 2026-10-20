# Semana 6 | ASII-02

## Diapositiva 1: Objetivo

Defender la arquitectura del módulo RBAC: asignación de rol, autorización de una operación y denegación trazable.

## Diapositiva 2: Trazabilidad

Actor: médico, enfermería, recepción y administrador. Flujo: asignación de rol, solicitud de autorización, decisión y registro de la denegación.

La secuencia conserva los mismos participantes y reglas definidos en las semanas anteriores.

## Diapositiva 3: Decisiones arquitectónicas

- Presentation recibe la solicitud HTTP.
- Application coordina el caso de uso de autorización.
- Domain decide con Authorizer y aplica la falla segura.
- Repository resuelve permisos (InMemory/PDO) sin exponer SQL.

## Diapositiva 4: Caso práctico

Agregar el permiso `prescripciones.anular` al rol médico: cambia solo el catálogo y la matriz; `Authorizer` y el contrato `/api/v1/authorize` permanecen iguales.

## Diapositiva 5: Matriz decisión -> evidencia

| Decisión | Evidencia |
|---|---|
| ¿Tiene el permiso? | Matriz rol × permiso |
| ¿Puede autorizar? | Rol y tenant |
| ¿Se deniega con falla segura? | DenialRecorder y trace_id |
| ¿El contrato se mantiene? | contrato-api.md `/api/v1` |

## Diapositiva 6: Cierre y pendientes

La solución mantiene la separación de responsabilidades y el contrato `/api/v1` estable. La conexión real con Usuarios/Auth queda pendiente para la Semana 16.
