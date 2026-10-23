# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code (Anthropic) con el modelo Claude Sonnet 5.5.

**Propósito:** apoyo para redactar el contrato API, el OpenAPI, la evaluación de la frontera de microservicio con sus diagramas Mermaid y la migración razonada, y para escribir el script de medición `tests/bench.php`, siguiendo el formato de la Semana 5 que ya usan otros módulos del equipo.

**Prompt relevante:** «Sigue con esto: Semana 5: Cliente-servidor, API REST, microservicios e integración», aplicado a `ASII-13`, «captura de signos vitales, evaluación de umbrales y generación de alerta».

**Contenido aceptado o modificado:** la decisión es no dividir el módulo, con una frontera de eventos hacia Alertas críticas. Las cifras de `bench.php` se midieron con PHP 8.3.33 en proceso, sin red ni PostgreSQL, y su salida se copió sin cambios. La carga del hospital (300 camas, mediciones cada 4 h) es un supuesto sin validar. La matriz de roles, el evento `signo_vital.alerta_generada`, `Idempotency-Key` y `outbox_events` son propuestas pendientes de acordar con RBAC, base de datos y Alertas críticas.

**Validación humana:** realizada el 2026-10-20. Josué Fernando Hicho García revisó el código, ejecutó las pruebas, revisó la documentación y esta declaración, y la aprueba.
