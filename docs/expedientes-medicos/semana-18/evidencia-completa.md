# Evidencia completa y PR integrados

Índice final del módulo: dónde está cada entregable, qué PR lo integró y cómo quedó cada requisito. Estados de los
PR consultados en GitHub el 2026-10-29.

## Semanas, carpetas y PR

| Semana | Tema | Carpeta | PR integrados | PR abiertos |
|---|---|---|---|---|
| 01 | Análisis y UML | [`semana-01`](../semana-01/) | #1156–#1157 | — |
| 02 | Requisitos y SOLID | [`semana-02`](../semana-02/) | #1158 | — |
| 03 | Arquitectura | [`semana-03`](../semana-03/) | #1159–#1163 | — |
| 04 | MVC y Repository | [`semana-04`](../semana-04/) | #1164–#1166 | — |
| 05 | Cliente-servidor y API | [`semana-05`](../semana-05/) | #1167–#1169 | — |
| 06 | 1.ª evaluación | [`semana-06`](../semana-06/) | #1170 | — |
| 07 | Componentes y refactorización | [`semana-07`](../semana-07/) | #1171 | — |
| 08 | UX y wireframes | [`semana-08`](../semana-08/) | #1172–#1174 | — |
| 09 | Usabilidad y accesibilidad | [`semana-09`](../semana-09/) | #1175 | — |
| 10 | Movilidad | [`semana-10`](../semana-10/) | #1176 | — |
| 11 | Prototipo navegable | [`semana-11`](../semana-11/) | #1177 | — |
| 12 | 2.ª evaluación | [`semana-12`](../semana-12/) | #1178 | — |
| 13 | Revisión técnica | [`semana-13`](../semana-13/) | #1179 | — |
| 14 | Plan SQA | [`semana-14`](../semana-14/) | #1180 | — |
| 15 | Pruebas unitarias, caja blanca y negra | [`semana-15`](../semana-15/) | #1181 | — |
| 16 | Integración y primera entrega | [`semana-16`](../semana-16/) | #1480 | — |
| 17 | Seguridad y despliegue final | [`semana-17`](../semana-17/) | #1496 | — |
| 18 | Evaluación final y defensa | [`semana-18`](../semana-18/) | esta entrega | — |
| | | **Total** | **28** | **0** |

Todo el módulo está en `develop`: micro-HIS `micro-his/expediente-medico`, adaptador `app/ExpedienteMedico`, prueba
`tests/Feature/ExpedienteMedico` y el provider en `bootstrap/providers.php`.

## Requisitos: estado final

| Requisito | Estado | Evidencia final |
|---|---|---|
| RF-EXP-01 Apertura única e idempotente | Implementado en el micro-HIS; en Laravel espera el permiso de RBAC | N-09, A3, A6, S-02; demo |
| RF-EXP-02 Consulta longitudinal | Implementado (micro-HIS y Laravel) | N-01, Feature; demo |
| RF-EXP-03 Resumen y estado | Implementado | N-01 (`summary`) |
| RF-EXP-04 Permiso y vínculo asistencial | Implementado | S-01, N-02…N-04 |
| RF-EXP-05 Bitácora de apertura, consulta y denegación | Implementado; apertura repetida desde la Semana 17 | S-07, S-08, N-10b; bitácora de la demo |
| RF-EXP-06 Códigos de error estables | Implementado | Caja negra N-*, `ui-estados.test.mjs` |
| RNF-EXP-01 Confidencialidad | Cumple | S-06, S-07 |
| RNF-EXP-02 Integridad | Cumple en SQLite; PostgreSQL sin validar | Integración PDO; A3/A6 |
| RNF-EXP-03 Aislamiento por tenant | Cumple | S-05; Feature «Another hospital…» |
| RNF-EXP-04 Seguridad de entrada | Cumple | S-04, R-05 |
| RNF-EXP-05 Resiliencia | Cumple | N-11, N-12, S-08 |
| RNF-EXP-06 Mantenibilidad | Cumple | R-01…R-07 (capas, ≤ 400 líneas) |
| RNF-EXP-07 Usabilidad y accesibilidad | Cumple en lo medido; lector de pantalla y teléfono físico sin probar | Semanas 9–11 |
| RNF-EXP-08 Trazabilidad | Cumple | `trace_id` en denegaciones; bitácora |

## Defectos y hallazgos

| Id | Semana | Estado |
|---|---|---|
| DEF-EXP-01…09 | 15 | Cerrados (ver `../semana-15/defectos.md`) |
| DEF-EXP-10 (caché de módulos JS) | 15 → 16 | Cerrado: `StaticAsset` + N-14 |
| H-EXP-17-01 (apertura repetida sin auditar) | 17 | Cerrado: evento `apertura_repetida` + S-07, S-08 |
| A-11 (sin límite por usuario) | 17 | Abierto: decisión del grupo |

## Pendientes con otros módulos

Permiso de apertura y criterio para enfermería (RBAC), confirmación de tablas y `record_number` (Javier), formato
común de errores y ruta en la SPA (Lis): ver [`../pendientes-integracion.md`](../pendientes-integracion.md).
