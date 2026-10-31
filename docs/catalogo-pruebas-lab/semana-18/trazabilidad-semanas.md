# Trazabilidad de las 18 semanas: Catálogo de pruebas de laboratorio

Cada semana corresponde a una entrega en `docs/catalogo-pruebas-lab/semana-NN/` y a sus PR. El estado se verificó en GitHub el 30/10/2026.

| Semana | Entregable | PR | Estado |
|---:|---|---|---|
| 1 | Modelado UML: casos de uso, actividad y secuencia, con narrativa de alcance | #43 | Integrado |
| 2 | RF/RNF, criterios de aceptación y un principio SOLID (OCP) antes/después | #197 (reentrega; #48 cerrado) | Integrado |
| 3 | Micro-HIS en PHP por capas (Presentation, Application, Domain y Persistence) | #199 (reentrega; #53 cerrado) | Integrado |
| 4 | MVC y patrón Repository (PDO e InMemory) | #1005 (reentrega; #59 cerrado) | Integrado |
| 5 | Cliente-servidor: contrato API v1 y frontera de microservicio | #1020 (reentrega; #63 cerrado) | Integrado |
| 6 | Primera evaluación parcial: presentación y matriz decisión → evidencia | #203 | Integrado |
| 7 | Componentes backend/frontend y sus contratos | #211 | Integrado |
| 8 | Flujo UX por rol | #215 | Integrado |
| 9 | Evaluación de usabilidad y accesibilidad | #219 | Integrado |
| 10 | Adaptación responsive y móvil | #223 | Integrado |
| 11 | Prototipo navegable con capturas | #228 | Integrado |
| 12 | Segunda evaluación parcial y cambio solicitado | #232 | Integrado |
| 13 | Plan de revisión técnica formal | #237 | Integrado |
| 14 | Plan de aseguramiento de calidad (SQA) | #241 | Integrado |
| 15 | Pruebas unitarias, de caja blanca y de caja negra | #1054 | Integrado |
| 16 | Integración en Laravel, pantalla Vue y validación con PostgreSQL | #1244, #1320, #1247 y #1591 | Integrado |
| 17 | Seguridad y despliegue final: amenazas, permisos y bitácora | #1593 | Integrado |
| 18 | Evaluación final y defensa: guion, trazabilidad y recorrido final | este PR | Abierto |

Las semanas 3 a 5 se reentregaron en PR de menos de 400 líneas; los PR originales se cerraron sin integrar.

## Continuidad entre entregas

- **Semanas 3 a 5 → 15:** las baterías del micro-HIS siguen pasando junto a las de caja blanca y caja negra.
- **Semanas 3 a 5 → 16:** el micro-HIS no conoce Laravel. En la Semana 16 se cableó tal cual, con un adaptador PDO sobre la base HOSPITAL.
- **Semana 15 → 17:** las correcciones de seguridad agregaron sus casos a las baterías de caja blanca o caja negra.
- **Semana 17 → 18:** el recorrido final repite los recorridos de las Semanas 16 y 17 sobre `develop` integrado.
