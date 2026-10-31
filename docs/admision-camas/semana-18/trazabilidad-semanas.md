# Trazabilidad de las 18 semanas: Admisión hospitalaria y asignación de cama

Cada semana corresponde a una entrega en `docs/admision-camas/semana-NN/` y a sus PR. El estado se verificó en GitHub el 30/10/2026.

| Semana | Entregable | PR | Estado |
|---:|---|---|---|
| 1 | Modelado UML: casos de uso, actividad y secuencia, con narrativa de alcance | #41 | Integrado |
| 2 | RF/RNF, criterios de aceptación y un principio SOLID (OCP) antes/después | #46 | Integrado |
| 3 | Micro-HIS en PHP por capas (Presentation, Application, Domain y Persistence) | #201 (reentrega; #51 cerrado) | Integrado |
| 4 | MVC y patrón Repository (PDO e InMemory) | #1007 (reentrega; #57 cerrado) | Integrado |
| 5 | Cliente-servidor: contrato API v1 y frontera de microservicio | #1022 (reentrega; #61 cerrado) | Integrado |
| 6 | Primera evaluación parcial: presentación y matriz decisión → evidencia | #205 | Integrado |
| 7 | Componentes backend/frontend y sus contratos | #213 | Integrado |
| 8 | Flujo UX por rol | #217 | Integrado |
| 9 | Evaluación de usabilidad y accesibilidad | #221 | Integrado |
| 10 | Adaptación responsive y móvil | #225 | Integrado |
| 11 | Prototipo navegable con capturas | #230 | Integrado |
| 12 | Segunda evaluación parcial y cambio solicitado | #234 | Integrado |
| 13 | Plan de revisión técnica formal | #239 | Integrado |
| 14 | Plan de aseguramiento de calidad (SQA) | #243 | Integrado |
| 15 | Pruebas unitarias, de caja blanca y de caja negra | #1056 | Integrado |
| 16 | Integración en Laravel, pantalla Vue y validación con PostgreSQL | #1236, #1243 y #1324 | Integrado |
| 17 | Seguridad y despliegue final: amenazas, permisos y bitácora | #1360 | Integrado |
| 18 | Evaluación final y defensa: guion, trazabilidad y recorrido final | este PR | Abierto |

Las semanas 3 a 5 se reentregaron en PR de menos de 400 líneas; los PR originales se cerraron sin integrar.

## Continuidad entre entregas

- **Semanas 3 a 5 → 15:** las baterías del micro-HIS siguen pasando junto a las de caja blanca y caja negra.
- **Semanas 3 a 5 → 16:** el micro-HIS no conoce Laravel. En la Semana 16 se cableó tal cual, con un adaptador PDO sobre la base HOSPITAL.
- **Semana 15 → 17:** las correcciones de seguridad agregaron sus casos a las baterías de caja blanca o caja negra.
- **Semana 17 → 18:** el recorrido final repite los recorridos de las Semanas 16 y 17 sobre `develop` integrado.
