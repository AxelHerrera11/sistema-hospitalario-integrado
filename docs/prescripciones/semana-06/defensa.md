# Guion de defensa técnica

Estado: `PROPUESTO`; ajustar a la versión realmente integrada antes de usar.

## Problema y límites

Presentar el flujo de prescripción como una operación clínica con reglas
explícitas de alergias, catálogo y firma. Explicar qué pertenece al dominio,
qué se consulta por puertos y qué queda en los adaptadores externos.

## Decisiones que deben explicarse

- SD-PRE-01 distingue advertencia leve y rechazos por severidad.
- Un rechazo 422 no crea ni altera una prescripción.
- `exception_allowed` comunica la política; no implementa una excepción.
- `isGrave()` no se modifica.
- La persistencia, la API y el wiring todavía deben demostrarse contra la
  implementación integrada; no presentarlos como completados a partir de este
  documento.

## Evidencia a mostrar

- Diagrama de límites y dependencias vigente.
- Casos de aceptación enlazados con pruebas ejecutadas, si existen.
- Contrato API y permisos vigentes, si están aprobados.
- Registro de lo pendiente y de las limitaciones conocidas.

No incluir capturas, conteos o porcentajes que no se puedan reproducir.
