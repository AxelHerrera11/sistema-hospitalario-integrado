# Semana 7 — Componentes y propuesta de refactor

Estado: `PROPUESTO`. Este inventario separa componentes existentes en
`origin/develop` de los límites y clases que todavía no tienen implementación
integrada para Prescripciones.

## Alcance

- Inventario documental de componentes backend y frontend.
- Diagrama de responsabilidades y dependencias.
- Refactors candidatos, con estado y evidencia explícitos.

## Entregables

- [Componentes backend](components/backend.csv)
- [Componentes frontend](components/frontend.csv)
- [Diagrama](diagrams/components.mmd)
- [Propuesta de refactor](refactor/propuesta.md)

## Evidencia y límites

El shell y los permisos compartidos, el módulo de Medicamentos y la navegación
se verificaron en el árbol de `origin/develop`. La entrada de Prescripciones
aparece como próximamente y no tiene ruta. Por ello, las clases y enlaces
específicos de Prescripciones se marcan `PROPUESTO`; este documento no usa
código de otra rama como si estuviera integrado.

Se conservan SD-PRE-01, el rechazo sin persistencia, la no modificación de
`isGrave()` y el estado pendiente de `excepcion_alergia` sin endpoint.
