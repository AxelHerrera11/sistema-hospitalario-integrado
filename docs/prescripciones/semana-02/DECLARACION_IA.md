# Declaración de uso de inteligencia artificial

## Herramienta y propósito

Se utilizó GitHub Copilot en VS Code como apoyo de redacción y organización de
los requisitos, criterios de aceptación y límites DIP de Prescripciones. La
herramienta no sustituye la decisión del equipo sobre reglas clínicas ni la
revisión del código.

## Instrucciones relevantes

- Documentar los requisitos funcionales/no funcionales y criterios de
  aceptación para ASII-15.
- Respetar SD-PRE-01: `mild` produce advertencia; `moderate` y `unknown`
  rechazan con 422 y `exception_allowed: true`; `severe` y
  `life_threatening` rechazan con 422 sin excepción.
- No guardar ante ningún 422, no modificar `isGrave()` y mantener
  `excepcion_alergia` pendiente, sin endpoint.
- Explicar DIP con los puertos del módulo, sin presentar trabajo de otra rama
  como integrado.

## Uso y cambios humanos

Se aceptó el apoyo para estructurar la documentación y redactar tablas y
criterios verificables. Se modificó el texto para conservar las decisiones de
producto confirmadas por la persona usuaria, retirar cualquier endpoint de
excepción y marcar como `PROPUESTO` lo que aún no está integrado. Los contratos
de puertos relacionados están **en revisión en #900**. `PrescriptionRepository`
y `PrescriptionService` se describen como `PROPUESTO`, no como componentes
integrados.

## Validación humana

La persona usuaria proporcionó y ratificó las reglas clínicas y el alcance
documental descritos arriba. Esta declaración y el contenido del PR quedan
sujetos a su revisión final; no se atribuyen pruebas clínicas ni validaciones
de implementación que no se hayan realizado.
