# Responsabilidades y objetos reutilizables

| Capa | Responsabilidad | No debe hacer |
|---|---|---|
| Presentation | Mapear entrada HTTP, autenticacion y respuesta | Validar reglas clinicas o ejecutar SQL |
| Application | Coordinar captura, correccion y auditoria | Conocer PDO o detalles de tablas |
| Domain | Validar estado pendiente, valor, unidad, rango y motivo | Leer request o generar respuestas HTTP |
| Persistence | Cargar y guardar resultados mediante el contrato | Decidir si una correccion es permitida |

## Contratos

- `LabResultRepository`: `findPending`, `saveVersion`, `findLatest`.
- `AuditLogPort`: `record` con usuario, tenant, accion y motivo.
- `ResultPolicy`: valida reglas puras y devuelve errores de dominio.

## Flujo reutilizable

1. El controlador convierte la entrada en un comando.
2. El caso de uso carga el resultado pendiente.
3. El dominio valida y crea una nueva version sin sobrescribir la anterior.
4. El repositorio persiste la version y la auditoria.
5. El controlador traduce el resultado a HTTP 201, 409 o 422.
