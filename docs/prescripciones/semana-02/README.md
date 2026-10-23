# Semana 2 — Requisitos y DIP

## Alcance

Documentar los requisitos funcionales y no funcionales de Prescripciones, sus
criterios de aceptación y la inversión de dependencias alrededor de puertos
explícitos. Este documento es de diseño; no afirma que la implementación esté
integrada en `develop`.

## Entregables

- [Requisitos](requisitos.md): comportamiento y restricciones.
- [Criterios de aceptación](aceptacion.md): resultados observables, incluidos
  los conflictos de alergia.
- [Puertos y DIP](dip.md): contratos usados por el límite de aplicación.

## Decisiones de producto vigentes

- SD-PRE-01: `mild` produce advertencia; `moderate` y `unknown` rechazan con
  HTTP 422 y `exception_allowed: true`; `severe` y `life_threatening` rechazan
  con HTTP 422 y sin excepción.
- Ninguna respuesta 422 guarda una prescripción.
- No se modifica `isGrave()`.
- `excepcion_alergia` permanece pendiente y no tiene endpoint.

El alcance no incluye persistencia PDO, MVC, Laravel ni cambios en otros
módulos.
