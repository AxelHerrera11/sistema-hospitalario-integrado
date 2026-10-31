# RF, RNF y criterios de aceptación

Cada requisito indica cómo se verifica hoy. «Prueba» remite a los identificadores de `micro-his/qa-calidad-versiones/tests/*.php`, `tests/view-model.test.mjs` y `tests/E2E/*.e2e.mjs`.

## Requisitos funcionales

| ID | Requisito | Estado | Prueba |
|---|---|---|---|
| RF-01 | Planificar una ejecución para una versión SemVer y un commit SHA-1 completo, en un ambiente aislado (`local`, `ci`, `staging`), con 1–30 suites de nombre único y al menos una suite **obligatoria** unitaria y una E2E. | [Implementado] | U01–U07, BN06, BN13, BN18–BN20 |
| RF-02 | Gestionar el ciclo de vida `planned → running → completed \| aborted`; abortar exige motivo. | [Implementado] | U17, U20–U24, BN12 |
| RF-03 | Registrar el resultado de cada suite planificada una sola vez (total, fallidas, omitidas, duración). | [Implementado] | U13–U19, BN08, P04 |
| RF-04 | Conservar la evidencia de cada resultado con ruta relativa al paquete y huella SHA-256; detectar alteraciones. | [Implementado] | U08–U12, CI07–CI09, P02 |
| RF-05 | Evaluar el quality gate con una política versionada y devolver `approved`, `rejected` o `blocked` con hallazgos por regla. | [Implementado] | W01–W18, A01, A04, CI14–CI18, E2E-QA-01..03 |
| RF-06 | Consultar ejecuciones y su última decisión, solo dentro del hospital (tenant) del actor. | [Implementado] | BN10, BN11, BN21, A08, E2E-QA-05 |
| RF-07 | Ejecutar el pipeline por etapas en CI, publicar reportes JUnit, resumen del gate y artefactos con retención. | [Implementado, sin ejecución en GitHub todavía] | CI14–CI20, `pipeline-local.sh` (ver semana-15) |
| RF-08 | Validar que toda ruta API lleve tenant + JwtAuth + `rbac:` con permiso del catálogo, salvo listas explícitas. | [Implementado] | CI10–CI13, `route-guard.php` sobre las 17 rutas de develop |

## Requisitos no funcionales

| ID | Requisito | Métrica / control | Prueba |
|---|---|---|---|
| RNF-01 | Seguridad: todo caso de uso exige permiso; sin decisión del RBAC se falla cerrado (503). Errores con formato `{code, trace_id}` como RBAC. | 0 rutas del módulo sin permiso | BN01–BN05, BN22, A05–A07 |
| RNF-02 | Aislamiento: E2E solo contra ambientes aislados con datos ficticios; el sembrado se niega fuera de bases `*_e2e` locales. | `production` no existe en el dominio | U06, BN13, CI20, guardas de `seed-e2e.php` |
| RNF-03 | Integridad: resultados de solo inserción; decisiones nunca se reescriben; huella SHA-256 de cada evidencia. | 0 sobrescrituras | U19, P04, P05, A04 |
| RNF-04 | Trazabilidad: cada acción y cada denegación llega a Gobernanza y Auditoría; las de seguridad abortan si no se guardan. | 100 % de acciones auditadas | A02, A03, A06, A13–A16 |
| RNF-05 | Reproducibilidad: dependencias por lockfile (`composer install`, `npm ci`), política en JSON versionado, mismo flujo en local (`pipeline-local.sh`). | mismo veredicto local/CI con la misma evidencia | CI14–CI18 |
| RNF-06 | Mantenibilidad: capas separadas y ningún archivo > 400 líneas. | AR01–AR08 en verde | `tests/architecture.php` |
| RNF-07 | Tiempo: cada job con `timeout-minutes`; E2E > 300 s genera aviso (umbral propuesto). | 15/20/20/5 min por job | W14, W15 |
| RNF-08 | Accesibilidad del prototipo: WCAG 2.1 AA (teclado, foco visible, etiquetas, contraste ≥ 4.5:1). | checklist semana 9 | semana-09 |

## Criterios de aceptación (Dado / Cuando / Entonces)

| ID | Dado | Cuando | Entonces |
|---|---|---|---|
| CA-01 (RF-01) | un líder QA con permiso de registro en el hospital de pruebas 1 | envía un plan con `environment: production` | recibe `422 QA_VALIDATION_FAILED` con `reason: ambiente_no_permitido` y no se crea nada |
| CA-02 (RF-02) | una ejecución `completed` | alguien intenta registrar otro resultado | recibe `409 QA_RUN_STATE_CONFLICT` y el resultado previo no cambia |
| CA-03 (RF-04) | una evidencia registrada con su SHA-256 | se modifica un byte del archivo | `EvidenceRef::matches()` devuelve `false` |
| CA-04 (RF-05) | todas las suites obligatorias con 100 % y evidencia | se evalúa el gate | veredicto `approved`, decisión guardada y evento `qa.gate.evaluado` auditado |
| CA-05 (RF-05) | la etapa E2E no publicó su reporte | se evalúa el gate | veredicto `blocked` con `SUITE_MISSING` (nunca `approved` por omisión) y exit 2 en CI |
| CA-06 (RF-05) | una suite obligatoria con 1 fallo de 20 y umbral 1.0 | se evalúa el gate | veredicto `rejected` con `SUITE_BELOW_THRESHOLD` y exit 1 en CI |
| CA-07 (RF-06) | una ejecución del hospital 1 | la consulta un usuario del hospital 2 | recibe `404` (no se revela que existe) |
| CA-08 (RNF-01) | el RBAC no responde | cualquier petición | `503 RBAC_UNAVAILABLE`, sin cambios |
| CA-09 (RF-08) | una ruta API nueva sin `rbac:` y fuera de las listas | corre el contrato de rutas | el caso JUnit de esa ruta falla y el gate rechaza |
