# Evidencia completa y PR integrados

Índice final del módulo: entregables por semana, PR, estado de cada requisito y bloqueos. Estados de los PR
consultados en GitHub el 2026-10-29.

## Semanas, carpetas y PR

| Semana | Tema | Carpeta | PR integrados | PR abiertos |
|---|---|---|---|---|
| 01 | Análisis y UML | [`semana-01`](../semana-01/) | #812 | — |
| 02 | Requisitos y SOLID | [`semana-02`](../semana-02/) | #813 | — |
| 03 | Arquitectura | [`semana-03`](../semana-03/) | #814–#820 | — |
| 04 | MVC y Repository | [`semana-04`](../semana-04/) | #821 | — |
| 05 | Cliente-servidor y API | [`semana-05`](../semana-05/) | #822–#824 | — |
| 06 | 1.ª evaluación | [`semana-06`](../semana-06/) | #825–#826 | — |
| 07 | Componentes y refactorización | [`semana-07`](../semana-07/) | #827 | — |
| 08 | UX y wireframes | [`semana-08`](../semana-08/) | #828 | — |
| 09 | Usabilidad y accesibilidad | [`semana-09`](../semana-09/) | #829 | — |
| 10 | Movilidad | [`semana-10`](../semana-10/) | #830 | — |
| 11 | Prototipo navegable | [`semana-11`](../semana-11/) | #831 | — |
| 12 | 2.ª evaluación | [`semana-12`](../semana-12/) | #832 | — |
| 13 | Revisión técnica | [`semana-13`](../semana-13/) | #833 | — |
| 14 | Plan SQA | [`semana-14`](../semana-14/) | #834 | — |
| 15 | Pruebas unitarias, caja blanca y negra | [`semana-15`](../semana-15/) | #835–#838 | — |
| 16 | Integración y primera entrega | [`semana-16`](../semana-16/) | #1485 | — |
| 17 | Seguridad y despliegue final | [`semana-17`](../semana-17/) | — | #1509 |
| 18 | Evaluación final y defensa | [`semana-18`](../semana-18/) | esta entrega | — |
| | | **Total** | **28** | **1** |

En `develop`: `micro-his/qa-calidad-versiones` (dominio, aplicación, PDO/InMemory, HTTP, CI y prototipo),
`.github/workflows/qa-ci-e2e.yml`, `tests/E2E/` y la [guía de despliegue](../guia-despliegue/README.md).

## Requisitos: estado final

| Requisito | Estado | Evidencia final |
|---|---|---|
| RF-01 Planificar una ejecución por versión y commit | Implementado | Unitarias, E2E-QA-01 |
| RF-02 Ciclo de vida de la ejecución | Implementado | Caja blanca; E2E-QA-08 (completada no admite más resultados) |
| RF-03 Registrar cada suite una sola vez | Implementado | Caja negra |
| RF-04 Evidencia con huella SHA-256 | Implementado | CI15 |
| RF-05 Quality gate con política versionada | Implementado | E2E-QA-01…03; CI14…CI21 |
| RF-06 Consultar ejecuciones por hospital | Implementado | E2E-QA-05, BN21 |
| RF-07 Pipeline por etapas con reportes y gate | Implementado; en GitHub sin ejecutar (facturación) | `pipeline-local.sh` sobre la integración |
| RF-08 Contrato de rutas (tenant + JWT + RBAC) | Implementado | `route-guard.php`: 51/51 |
| RNF-01 Falla cerrada sin RBAC | Cumple | BN22, E2E-QA-07 |
| RNF-02 E2E solo en ambientes aislados | Cumple | Guarda `E2E_ALLOW_SEED`; guía §3 |
| RNF-03 Integridad de resultados y decisiones | Cumple | PDO, CI15 |
| RNF-04 Trazabilidad hacia Gobernanza | Cumple | Pruebas de aplicación |
| RNF-05 Reproducibilidad | Cumple en local; en GitHub pendiente | Lockfiles; pipeline local |
| RNF-06 Mantenibilidad (capas, ≤ 400 líneas) | Cumple | Suite de arquitectura |
| RNF-07 Tiempos por job | Definido en el workflow; sin medir en GitHub | `timeout-minutes` |
| RNF-08 Accesibilidad del prototipo | Cumple en lo evaluado (Semana 9) | Capturas de las Semanas 10–11 |

## Hallazgos y bloqueos

| Id | Estado |
|---|---|
| D-01 (micro-HIS sin `tests/run.php`) | Cerrado en la Semana 16 |
| E-QA-16-01/02 (documentación desactualizada; clasificar fallos) | Cerrados en la Semana 16 |
| H-QA-17-01 (evidencias sin revisión de secretos) | Cerrado: `escanear-secretos.php`, CI22–CI23 |
| H-QA-17-02 (`/auth/me` responde 500 con CENTRAL caída) | Avisado a Tenants; la guía lo documenta |
| B-03 (primera ejecución en GitHub) | Bloqueado por la facturación de la organización |
| B-04 (umbrales y suites obligatorias) | Decisión pendiente del grupo |
| Q-03 (acciones de terceros sin SHA) | Riesgo medio documentado |
