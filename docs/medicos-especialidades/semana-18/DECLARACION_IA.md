# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code con el modelo Claude Sonnet 5.5 (`claude-sonnet-5-5`).

**Uso:** apoyo para consolidar la entrega final: la trazabilidad de las 18 semanas, el guion de defensa con su demo reproducible y este resumen de evidencia. Durante las semanas 16 y 17 también apoyó la integración en Laravel, el adaptador PostgreSQL, la pantalla Vue y las pruebas de seguridad.

**Revisado y ajustado:** los estados de la trazabilidad salen de consultar cada PR con `gh pr view` el 22 de octubre de 2026; los resultados salen de ejecutar los comandos del guion sobre las ramas del módulo y sobre PostgreSQL 16 en contenedores efímeros. El documento declara lo que no está integrado (semanas 13, 15, 16, 17 y 18), la laguna de la Semana 5 (#372), las amenazas abiertas y que no hay despliegue fuera del equipo local. Las capturas de la pantalla se tomaron en Chrome sin interfaz con un API simulado, y así se declara: no se afirma que la pantalla se haya probado contra el backend real.

**Validación humana:** realizada el 2026-10-23. Josué Fernando Hicho García revisó esta entrega completa, incluidos `rendimiento.md`, `e2e-real.md`, las capturas con sesión real, el contrato API actualizado y la corrección de errores del cliente, y la aprueba.
