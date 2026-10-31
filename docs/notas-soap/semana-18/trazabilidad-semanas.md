# Trazabilidad de las 18 semanas — Notas SOAP y diagnósticos (ASII-11)

Estado verificado el 2026-10-22 con `gh pr list`. De los 35 PR del módulo (sin contar este), **14 están fusionados** (semanas 1 a 8 y 14) y **21 siguen abiertos**: 20 en borrador y #404 marcado como listo. Ninguno de los abiertos ha recibido revisión.

| Semana | Entregable | PR | Estado |
|---|---|---|---|
| 1 | UML (casos de uso, actividad, secuencia) y narrativa de alcance | #8 | Fusionado |
| 2 | SRP, RF/RNF y criterios de aceptación | #65 | Fusionado |
| 3 | Núcleo del micro-HIS (dominio y aplicación), persistencia PDO, entrada HTTP, vista C4 | #128, #129, #130 | Fusionados |
| 4 | Repository (PDO e InMemory), MVC con pruebas, capas y repositorio compartido | #376, #378, #379 | Fusionados |
| 5 | Contrato API v1, OpenAPI y frontera de microservicio | #374 | Fusionado |
| 5 | Servidor API v1 con permisos y `trace_id`; lectura GET y cliente HTTP | #404, #405 | Abiertos (#404 listo, #405 borrador) |
| 6 | Primera evaluación parcial | #411 | Fusionado |
| 7 | Componentes backend/frontend y refactorización | #421 | Fusionado |
| 8 | Wireframes, user flow y diagramas SVG | #427, #568 | Fusionados |
| 9 | Evaluación de usabilidad y accesibilidad | #441 | Borrador |
| 10 | Diseño móvil | #525 | Borrador |
| 11 | Prototipo navegable | #528 | Borrador |
| 12 | Segunda evaluación parcial | #532 | Borrador |
| 13 | Plan de revisión técnica formal | #533 | Borrador |
| 14 | Plan SQA | #548 | Fusionado |
| 15 | Pruebas unitarias, de caja blanca y de caja negra | #551 | Borrador |
| 16 | Correcciones D-01 y D-03, integración en Laravel, adaptadores PDO contra el esquema real, D-02 mitigado, identidad del token, documentación | #1049, #1059, #1067, #1074, #1075, #1062 | Borrador |
| 17 | Pruebas de seguridad T1 a T8, R-06 como 409, documentación | #1077, #1080, #1081 | Borrador |
| 18 | Firma, corrección, catálogo CIE-10 provisional, pantalla Vue; esta documentación | #1083, #1087, #1089, #1090 y este PR | Borrador |

## Cómo se apilan los PR de código

Cada uno apunta a `develop` e incluye por merge al anterior; las líneas propias de cada uno se aclaran en su descripción (límite de 400 sumando adiciones y eliminaciones).

#551 → #1049 → #1059 → #1067 → #1074 → #1075 → #1077 → #1080 → **#1083** (firma, 366 líneas propias) → **#1087** (corrección, 345) → **#1089** (catálogo provisional, 166) → **#1090** (pantalla, 368).

La documentación (#1062, #1081 y este PR) va aparte, también hacia `develop`.

## Requisitos del módulo y dónde se cubren

| Requisito (Semana 2) | Cubierto en |
|---|---|
| RF-SOAP-01 a 03: registrar la nota SOAP con las cuatro secciones, solo el médico tratante | `RegisterSoapNote`, `SoapSections`, `TreatingPhysicianGuard`; pruebas U-01 a U-05 y B-01 a B-05 |
| RF-SOAP-04 a 06: diagnóstico CIE-10 con tipo, un solo principal, sin repetidos | `AssociateDiagnosis`, `DiagnosisPolicy`; U-06 a U-08 |
| RF-SOAP-07: una nota firmada no acepta diagnósticos; el cambio es una corrección | `SignSoapNote`, `CorrectSoapNote`; U-09, U-16 a U-21, W-09 y W-10, y las pruebas contra PostgreSQL |
| UC-SOAP-05 y 06 (Semana 1): firmar y corregir | #1083 y #1087 |
| UC-SOAP-08: auditoría en `local_access_audits` en la misma transacción | `PdoAuditTrail`; reversión probada con la bitácora caída |
| Outbox hacia CENTRAL (Semana 1) | **No implementado** (ver [pendientes](./README.md#lo-que-no-está-resuelto)) |
