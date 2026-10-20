# Criterios de aceptación, quality gates y responsables

## 1. Criterios de aceptación (Dado / Cuando / Entonces)

Cada criterio cubre una dimensión exigida y se verifica con la suite de `estrategia-pruebas-regresion.md`.

### Permisos

| ID | Dado | Cuando | Entonces |
|---|---|---|---|
| CA-PERM-01 | `admin1` (`AdminHospital`) en H1 con `rbac.asignar` (token del motor; el catálogo formalizado lo llama `rbac.roles.asignar`) | asigna `Enfermeria` a `enf1` | 201; membresía y evento `rol_asignado` persistidos |
| CA-PERM-02 | `med1` sin `rbac.asignar` (token del motor) | intenta asignar un rol | 403 `RBAC_DENIED` con `trace_id` |
| CA-PERM-03 | `admin1` de nivel 80 | asigna `SuperAdmin` de nivel 100 | 403 con motivo `rol_supera_privilegio`; sin escalación |
| CA-PERM-04 | Rol ya vigente para el usuario | se reasigna el mismo rol | 409 `rol_ya_asignado`; sin efecto en base |

### Autenticación

| ID | Dado | Cuando | Entonces |
|---|---|---|---|
| CA-AUTH-01 | Solicitud sin token válido | se invoca una operación protegida | 401 `UNAUTHENTICATED` sin ejecutar el caso de uso |
| CA-AUTH-02 | Token válido pero membresía vencida/inactiva | se invoca una operación protegida | 403; no se autoriza |

### Tenant

| ID | Dado | Cuando | Entonces |
|---|---|---|---|
| CA-TEN-01 | `med1` con rol solo en H1 | solicita la operación con `X-Tenant-ID: H2` | 403; el motor no lee roles de H1 |
| CA-TEN-02 | Petición sin `X-Tenant-ID` o con valor vacío | se invoca la operación | 422/403; no se ejecuta el módulo |

### Trazabilidad

| ID | Dado | Cuando | Entonces |
|---|---|---|---|
| CA-TRZ-01 | Una denegación por falta de permiso | el motor decide denegar | responde 403 con `trace_id` presente en `audit_log` |
| CA-TRZ-02 | Varias denegaciones consecutivas | se consultan sus `trace_id` | todos son únicos y recuperables |
| CA-TRZ-03 | Cualquier cambio de rol | se consulta la bitácora | existe el evento de auditoría asociado; no hay cambio sin traza |

### Fallos de persistencia

| ID | Dado | Cuando | Entonces |
|---|---|---|---|
| CA-PRS-01 | Tabla de auditoría no disponible | se asigna un rol | 500; rollback total, sin membresía residual |
| CA-PRS-02 | Bitácora no disponible | el motor decide denegar | la operación sigue denegada (falla segura) |

## 2. Quality gates

| Gate | Momento | Criterios bloqueantes | Evidencia | Responsable |
|---|---|---|---|---|
| G0 | Local, antes del commit | Suite base `run.php` en `OK` | Salida de consola | Mar-03 |
| G1 | Al abrir el PR hacia `develop` | G0 + revisión de `routes/api.php` (hoy solo `auth/*`; sin rutas RBAC ni alias `permission`) + plantilla de PR completa | `.github/pull_request_template.md` | Mar-03 |
| G2 | Antes del merge | Suite base + adiciones de regresión (`Q-TEN-01`, `Q-TRZ-02`, `Q-AUTH-02`, `Q-PRS-01`, `Q-PERM-02/03`) | Informe de regresión con 100 % `OK` | Mar-03 / revisor |
| G3 | Integración (staging/on-premise) | G2 + sin fugas de tenant + 0 falsos permitidos | Ejecución sobre PostgreSQL federado | Mar-03 / revisor |
| G4 | Antes de la entrega final | G3 + revisión de seguridad (permisos, tenant, datos sensibles) + defectos críticos cerrados y verificados | Checklist de seguridad y bitácora de defectos | Revisor `@rortizs` |

Un gate no pasa si: falla cualquier criterio bloqueante, existe un defecto crítico/alto abierto en la dimensión evaluada, o falta la evidencia. No se aprueban gates «por avance» sin la salida registrada. G1 no puede ejecutar `route:list --path=api` con rutas RBAC porque aún no existen; se limita a comprobar que no se declaren rutas falsas ni un alias `permission` inexistente.

## 3. Responsables (RACI)

| Actividad | Mar-03 (módulo) | Revisor `@rortizs`/`@Yamilet1235` | ASII-01 (usuarios/auth) | Gobernanza |
|---|---|---|---|---|
| Definir y ejecutar el plan SQA | R/A | C | I | I |
| Mantener la suite de regresión | R/A | C | I | I |
| Aprobar los gates G2–G4 | C | R/A | I | C |
| Validar vigencia de membresías | C | C | R/A | I |
| Bitácora central de auditoría | C | I | I | R/A |

`R` = responsable de ejecutar, `A` = aprueba, `C` = consultado, `I` = informado.
