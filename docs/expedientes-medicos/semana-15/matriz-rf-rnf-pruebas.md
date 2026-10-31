# Matriz RF/RNF → prueba

Pruebas en `micro-his/expediente-medico/tests/` (U = unitarias, C/A = caja blanca, N = caja negra, P = integración
PDO, R = arquitectura), `tests/ui-estados.test.mjs` (V) y `tests/Feature/ExpedienteMedico` (F). Todas ejecutadas el
2026-10-23: ver [`evidencias/`](evidencias/).

| Requisito | Unitarias | Caja blanca | Caja negra | Integración / otras | Estado |
|---|---|---|---|---|---|
| RF-EXP-01 Apertura única e idempotente | U-01, U-02 | A1–A7 | N-08, N-09, N-10 | P-03, P-04 | Pasa |
| RF-EXP-02 Consulta longitudinal ordenada | U-05 | C7 | N-01, N-09b | P-02, F-1 | Pasa |
| RF-EXP-03 Resumen y estado | U-05 | C7 | N-01 | V-02 | Pasa |
| RF-EXP-04 Permiso RBAC + vínculo | — | C1, C3, C4, C8, A1 | N-02, N-03, N-04, N-05 | P-01, F-2, F-3 | Pasa |
| RF-EXP-05 Auditoría | — | C1, C3, C6, C7, A5, A7 | N-10b, N-12 | P-07 | Pasa |
| RF-EXP-06 Errores con código y recuperación | — | C2, C5 | N-06, N-11, N-13 | V-04…V-08 | Pasa |
| RNF-EXP-01 Confidencialidad | — | C7 (contexto sin datos) | N-01 (sin `contact`), N-04b, N-11, N-12 | P-07b, F-1 (`no-store`), F-3, V-10 | Pasa |
| RNF-EXP-02 Integridad | U-03, U-04, U-06 | A5, A6 | N-09 | P-03, P-04, P-05 | Pasa |
| RNF-EXP-03 Aislamiento por tenant | — | C2 | N-05, N-05b | P-08, P-09, F-2 | Pasa |
| RNF-EXP-04 Entrada y SQL seguro | U-07 | — | N-07, N-07b, N-07c, N-07d | R-05, V-01, F-3 | Pasa |
| RNF-EXP-05 Resiliencia | — | C6b, A6 | N-11 | P-06, P-09, V-05…V-07 | Pasa |
| RNF-EXP-06 Mantenibilidad | — | — | — | R-01…R-07 | Pasa |
| RNF-EXP-07 Usabilidad/accesibilidad | — | — | — | V-01, V-09, V-11; checklist Semana 9 (manual) | Pasa con pendientes manuales |
| RNF-EXP-08 Trazabilidad | — | C1 (`rbac_trace`), C7 (`correlation_id`), C8 | N-03 (`trace_id`) | `semana-14/evidencias/auditoria-prototipo.txt` | Pasa |

F-1…F-4 = los cuatro métodos de `ExpedienteMedicoApiTest`, en el orden del archivo.
