# Declaración de uso de IA — ASII-23 Semana 3

## Propósito

Complemento documental de la Semana 3 (arquitectura Micro-HIS, PHP 8.2+
vanilla con PDO preparado) del módulo `ASII-23`.

## Cómo me apoyé en la IA

- **Solicitud de referencia:** una sesión de Copilot se pidió generar el
  esqueleto de las cuatro capas (Presentation/Application/Domain/
  Persistence), la consulta agregada preparada y la prueba `run.php` con
  camino feliz, regla de dominio y error de persistencia.
- María Yamilet Lindo Pablo revisó y corrigió (edición humana): la consulta
  `count(*) + SUM(CASE WHEN status = 'cancelled' …)`, la validación del
  periodo con offset explícito (`desde < hasta`, extremo superior exclusivo),
  la coerción de conteos y la coherencia de `N_cancelados<=N_finalizados`.
- La evidencia se generó ejecutando realmente
  `php micro-his/reportes-analytics/tests/run.php` (11 escenarios OK en el
  entorno local de María Yamilet).

**Nota del 30/10/2026:** las afirmaciones de revisión o edición humana de este archivo no tienen confirmación registrada. Ver [`../DECLARACION_IA.md`](../DECLARACION_IA.md).
