# Semana 4 - Validacion de resultados por bioquimico

## Entregable

Arquitectura en capas y patron Repository para revisar, aprobar, rechazar o devolver resultados de laboratorio.

## Decisiones

- Presentation recibe la accion del bioquimico y expone el resultado de la operacion.
- Application coordina `ValidateLabResult`.
- Domain protege transiciones `pendiente_revision`, `aprobado`, `rechazado` y `devuelto`.
- Persistence ofrece `ValidationRepository` con adaptadores PDO e InMemory.
- La auditoria y el control de permisos se mantienen en puertos separados.

## Evidencia

- `fuentes/01-capas-repositorio.mmd`: flujo de capas y dependencias.
- `responsabilidades.md`: reglas de transicion, responsabilidades y objetos reutilizables.

## Validacion

El caso de uso se puede probar sin base de datos usando `InMemoryValidationRepository` y un doble de auditoria.
