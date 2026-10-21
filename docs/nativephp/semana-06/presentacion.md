# Semana 6 | ASII-29

## Diapositiva 1: Objetivo

Defender la viabilidad arquitectónica preliminar de una extensión NativePHP para el flujo del HIS.

## Diapositiva 2: Trazabilidad

Actor: usuario de laboratorio. Flujo: capturar, validar, sincronizar y reportar errores sin duplicar la lógica clínica.

La propuesta conserva las reglas del módulo web y separa la experiencia de la integración.

## Diapositiva 3: Decisiones arquitectónicas

- La interfaz móvil/escritorio consume un gateway.
- El HIS conserva la propiedad de los datos clínicos.
- Idempotencia y reintentos evitan duplicados.
- Telemetría registra fallos de comunicación.

## Diapositiva 4: Caso práctico

Una captura offline se encola, se reintenta al recuperar conexión y se rechaza con detalle si cambió el estado del resultado.

## Diapositiva 5: Matriz decisión -> evidencia

| Decisión | Evidencia |
|---|---|
| ¿Puede sincronizar? | Tenant y permisos |
| ¿Ya fue enviada? | Idempotency key |
| ¿Se reintenta? | Política de resiliencia |
| ¿Es viable NativePHP? | Prueba real del entorno |

## Diapositiva 6: Cierre y pendientes

La evidencia actual es simulación documental. La viabilidad técnica sigue condicionada a ejecutar y validar NativePHP en un entorno habilitado.
