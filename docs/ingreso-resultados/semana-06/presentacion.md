# Semana 6 | ASII-19

## Diapositiva 1: Objetivo

Defender la arquitectura de captura y corrección controlada de resultados pendientes.

## Diapositiva 2: Trazabilidad

Actor: personal de laboratorio. Flujo: captura, validación, corrección autorizada y auditoría.

La secuencia conserva los mismos participantes y reglas definidos en las semanas anteriores.

## Diapositiva 3: Decisiones arquitectónicas

- Presentation recibe la solicitud.
- Application coordina el caso de uso.
- Domain valida estado, valores y permisos.
- Repository persiste el resultado y la evidencia.

## Diapositiva 4: Caso práctico

Un resultado pendiente de 95 mg/dL se corrige a 105 mg/dL con motivo obligatorio y autorización del rol correspondiente.

## Diapositiva 5: Matriz decisión -> evidencia

| Decisión | Evidencia |
|---|---|
| ¿Está pendiente? | Estado del resultado |
| ¿Puede corregir? | Rol y tenant |
| ¿Es válido el valor? | Regla de dominio |
| ¿Se conserva la trazabilidad? | Registro de auditoría |

## Diapositiva 6: Cierre y pendientes

La solución mantiene separación de responsabilidades y permite evolucionar hacia API y persistencia compartida. La auditoría productiva queda pendiente de integración.
