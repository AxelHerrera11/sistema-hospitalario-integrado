# Semana 4 - Ingreso de resultados de laboratorio

## Entregable

Definicion de arquitectura en capas y patron Repository para la captura y correccion controlada de resultados pendientes.

## Decisiones

- Presentation recibe la solicitud y devuelve una respuesta consistente.
- Application coordina el caso de uso `CapturePendingResult`.
- Domain valida estado pendiente, unidad, rango y motivo de correccion.
- Persistence implementa `LabResultRepository` con adaptadores PDO e InMemory.
- La auditoria se registra mediante una abstraccion, sin acoplar el dominio a SQL.

## Evidencia

- `fuentes/01-capas-repositorio.mmd`: diagrama de capas y dependencias.
- `responsabilidades.md`: responsabilidades, objetos reutilizables y reglas de integracion.

## Validacion

Se verifica que el controlador no contiene SQL ni reglas de negocio y que el caso de uso puede probarse con un repositorio InMemory.
