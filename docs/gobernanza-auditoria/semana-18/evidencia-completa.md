# Evidencia completa y PR integrados

Índice final del módulo: entregables por semana, PR, estado de cada requisito y hallazgos. Estados de los PR
consultados en GitHub el 2026-10-29. Los PR #640, #649, #650 y #651 se cerraron sin integrar porque su contenido ya
había llegado con los demás.

## Semanas, carpetas y PR

| Semana | Tema | Carpeta | PR integrados | PR abiertos |
|---|---|---|---|---|
| 01 | Análisis y UML | [`semana-01`](../semana-01/) | #564, #566 | — |
| 02 | Requisitos y SOLID | [`semana-02`](../semana-02/) | #573 | — |
| 03 | Arquitectura | [`semana-03`](../semana-03/) | #576, #580, #620, #623–#624 | — |
| 04 | MVC y Repository | [`semana-04`](../semana-04/) | #628, #634–#635 | — |
| 05 | Cliente-servidor y API | [`semana-05`](../semana-05/) | #636–#637 | — |
| 06 | 1.ª evaluación | [`semana-06`](../semana-06/) | #638 | — |
| 07 | Componentes y refactorización | [`semana-07`](../semana-07/) | #639 | — |
| 08 | UX y wireframes | [`semana-08`](../semana-08/) | #641 | — |
| 09 | Usabilidad y accesibilidad | [`semana-09`](../semana-09/) | #642 | — |
| 10 | Movilidad | [`semana-10`](../semana-10/) | #643 | — |
| 11 | Prototipo navegable | [`semana-11`](../semana-11/) | #644–#645 | — |
| 12 | 2.ª evaluación | [`semana-12`](../semana-12/) | #646 | — |
| 13 | Revisión técnica | [`semana-13`](../semana-13/) | #648 | — |
| 14 | Plan SQA | [`semana-14`](../semana-14/) | #652 | — |
| 15 | Pruebas unitarias, caja blanca y negra | [`semana-15`](../semana-15/) | #653–#654 | — |
| 16 | Integración y primera entrega | [`semana-16`](../semana-16/) | #1481 | — |
| 17 | Seguridad y despliegue final | [`semana-17`](../semana-17/) | — | #1507 |
| 18 | Evaluación final y defensa | [`semana-18`](../semana-18/) | esta entrega | — |
| | | **Total** | **26** | **1** |

En `develop`: `micro-his/gobernanza-auditoria`, `app/GobernanzaAuditoria`, el provider registrado y la ruta
`GET /api/v1/audit-events` con `JwtAuth` y `RequirePermission:auditoria.ver`.

## Requisitos: estado final

| Requisito | Estado | Evidencia final |
|---|---|---|
| RF-GOB-01 Registrar eventos con actor, tenant, acción, resultado y criticidad | Implementado | Contrato C-*, unitarias |
| RF-GOB-02 Distinguir lo ocurrido de los intentos | Implementado | TC-N-04; demo (`occurred:false`) |
| RF-GOB-03 Política de fallo por criticidad | Implementado | Contrato; expediente S-08 |
| RF-GOB-04 Consultar con filtros en el tenant actual | Implementado | TC-N-01, TC-N-02; demo |
| RF-GOB-05 Paginación | Implementado | TC-N-13 |
| RF-GOB-06 Errores por parámetro | Implementado | TC-N-12, TC-S-06 |
| RF-GOB-07 Autorizar con el RBAC real (`auditoria.ver`) | Implementado; permiso catalogado (hoy solo `superadmin`) | `GovernanceHttpTest`, TC-N-10 |
| RF-GOB-08 Auditar cada consulta autorizada | Implementado | TC-N-14 |
| RF-GOB-09 Denegaciones de RBAC con `DenialRecorder` | Integrado (`AuditDenials`) | «Medico is denied before filters and denial is persisted once» |
| RF-GOB-10 Detalle sin datos internos | Implementado | TC-N-03 |
| RNF-GOB-01 Aislamiento por tenant | Cumple | TC-N-02, TC-N-07, TC-N-08 |
| RNF-GOB-02 Identidad del servidor | Cumple | TC-N-05; JWT en Laravel |
| RNF-GOB-03 Sin secretos, datos clínicos ni personales | Cumple desde la Semana 17 | TC-C-10, TC-W-14, TC-S-01…04 |
| RNF-GOB-04 Consultas parametrizadas | Cumple | TC-W-10, TC-W-11 |
| RNF-GOB-05 Inmutabilidad y evidencia de manipulación | Cumple en SQLite; trigger PostgreSQL sin validar | Cadena de hashes, `hash_compat` |
| RNF-GOB-06 Errores seguros | Cumple | 503 con código y `trace_id` |
| RNF-GOB-07 Correlación | Cumple | `X-Request-ID`, TC-N-15 |
| RNF-GOB-08 Sustitución de adaptadores | Cumple | Contrato InMemory = PDO |
| RNF-GOB-09 Rendimiento | Sin medir con datos reales | — |
| RNF-GOB-10 Accesibilidad del prototipo | Cumple en lo evaluado (Semana 9) | Capturas de la Semana 11 |

## Hallazgos

| Id | Semana | Estado |
|---|---|---|
| H-GOB-07 (modelos `Tenant`/`User`) | 13 → 16 | Resuelto por la integración de Tenants |
| E-GOB-16-01 (`hash_compat` se omitía) | 16 | Corregido |
| E-GOB-16-02 (dependencias desactualizadas en el README) | 16 | Corregido |
| H-GOB-17-01 (datos personales en el contexto) | 17 | Corregido: `PERSONAL_KEY`, `EMAIL_VALUE`, TC-S-01…04 |
| G-12 (sin límite por usuario) | 17 | Abierto: decisión del grupo |
