# Responsabilidades y objetos reutilizables

| Capa | Responsabilidad | No debe hacer |
|---|---|---|
| Presentation | Recibir decision, comentario y version seleccionada | Cambiar estados directamente |
| Application | Coordinar permiso, decision, persistencia y auditoria | Conocer SQL o detalles de UI |
| Domain | Aceptar solo transiciones validas y comentario obligatorio | Acceder a HTTP o sesiones |
| Persistence | Consultar version y guardar decision | Aprobar por si misma un resultado |

## Contratos

- `ValidationRepository`: `findForReview`, `saveDecision`, `findHistory`.
- `PermissionPort`: `canValidateResult` exige rol bioquimico y tenant activo.
- `AuditLogPort`: registra aprobar, rechazar y devolver con comentario.

## Regla principal

Una version ya aprobada no puede volver a aprobarse ni modificarse. Una decision de rechazo o devolucion debe conservar comentario y referencia a la version revisada.
