# Semana 2 — Proceso y modelo de diseño; principio SOLID (DIP)

**Entregable:** RF/RNF, criterios de aceptación y ejemplo SOLID aplicado al flujo «planificación de pruebas, ejecución E2E, conservación de evidencia y aplicación de quality gates».

| Documento | Contenido |
|---|---|
| [rf-rnf-criterios.md](rf-rnf-criterios.md) | 8 RF, 8 RNF y 9 criterios Dado/Cuando/Entonces trazados a pruebas |
| [dip-antes-despues.md](dip-antes-despues.md) | Diseño antes/después con DIP, responsabilidades y dependencias |
| [evidencias/architecture.txt](evidencias/architecture.txt) | Salida real de la suite de arquitectura (AR01–AR08) |

**Por qué DIP.** El riesgo principal del flujo es que la decisión «¿se promueve esta versión?» quede atada a la herramienta (GitHub Actions, PHPUnit, una base de datos). Con DIP la regla vive en `Domain/QualityGate` y la usan, sin cambios, la API HTTP, el CLI de CI, la demo y las pruebas.

**Pendiente de validar:** el adaptador RBAC real (hoy `StaticAccessGate`) depende de que María apruebe los permisos `qa.*` propuestos (ver [semana-05](../semana-05/contrato-api.md#permisos-propuesta-pendiente)).
