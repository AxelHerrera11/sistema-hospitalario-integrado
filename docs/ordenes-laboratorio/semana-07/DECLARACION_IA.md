# Declaración de uso de inteligencia artificial — Semana 7

**Herramienta:** OpenCode con el modelo `deepseek-v4.1-flash-free`.
**Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`).
**Módulo:** Órdenes de laboratorio desde EMR.

## Propósito

Diseñar los componentes backend/frontend y comparar un refactor concreto en el punto de mayor
acoplamiento entre órdenes, expediente, pruebas y trazabilidad.

## Aportes y límites

- La IA propuso el diagrama de componentes, los contratos y el refactor de `IssueLabOrder` en
  `LabOrderDraftAssembler` + `EmitLabOrderHandler`.
- El refactor se documenta como **diseño**; no se aplicó al código en esta semana. Las métricas de
  “después” son del diseño propuesto y así se declaran.
- Las cifras de pruebas (25, 12, 18, 6) corresponden a ejecuciones reales de semanas 3–5.
- No se agregaron endpoints, tablas ni estados; el alcance funcional no cambia.

## Validación humana

**Pendiente.** Javier Alexander Fajardo López debe revisar el diagrama, los contratos y la
justificación antes de publicar el PR.
