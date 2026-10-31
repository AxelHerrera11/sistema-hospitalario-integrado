# Semana 4 — MVC y repositorio compartido

## Alcance

Propuesta documental para organizar la entrada MVC del micro-HIS de
Prescripciones, definir responsabilidades del repositorio y analizar la
integración con datos compartidos. No incorpora código ni afirma que esta
arquitectura esté implementada.

## Entregables

- [Responsabilidades MVC](mvc.md)
- [Límite Repository](repository.md)
- [Propiedad e integración de datos](integracion-datos.md)
- [Diagrama de integración](integracion.mmd)

## Estado y límites

Todo diseño específico de esta semana está marcado `PROPUESTO` hasta validarse
con el código integrado y los propietarios de los módulos relacionados.
Quedan fuera de alcance el servicio, la persistencia PDO, Laravel, endpoints y
cambios de esquema.

El flujo debe preservar las decisiones SD-PRE-01: un rechazo 422 no guarda
datos, `isGrave()` no se modifica y `excepcion_alergia` sigue pendiente, sin
endpoint.
