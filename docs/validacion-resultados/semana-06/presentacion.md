# Semana 6 | ASII-20

## Diapositiva 1: Objetivo

Defender la arquitectura de validación de resultados por bioquímico y el rechazo controlado.

## Diapositiva 2: Trazabilidad

Actor: bioquímico. Flujo: recibir pendiente, validar reglas, aprobar o rechazar y registrar motivo.

La secuencia mantiene el vínculo entre autorización, decisión y respuesta.

## Diapositiva 3: Decisiones arquitectónicas

- Presentation expone la solicitud.
- Application coordina la validación.
- Domain aplica estado, unidad, rango y permisos.
- Persistence registra decisión y auditoría.

## Diapositiva 4: Caso práctico

Un resultado fuera de rango se rechaza con motivo obligatorio; solo el rol autorizado puede corregirlo y reenviarlo a validación.

## Diapositiva 5: Matriz decisión -> evidencia

| Decisión | Evidencia |
|---|---|
| ¿Puede validar? | Rol, tenant y unidad |
| ¿Es consistente? | Estado y reglas |
| ¿Aprueba o rechaza? | Decisión y motivo |
| ¿Se puede reintentar? | Control de concurrencia |

## Diapositiva 6: Cierre y pendientes

La solución concentra las reglas en el dominio y deja preparada la integración con identidad y auditoría institucional.
