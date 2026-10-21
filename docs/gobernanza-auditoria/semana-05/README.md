# Gobernanza y Auditoría — Semana 5: contrato API y seguridad

## Objetivo

Definir el contrato de integración: el endpoint, los parámetros, las validaciones, el formato de respuesta, la paginación y los errores 401/403/422. También la autenticación, la autorización con el RBAC real, el aislamiento por tenant, las consultas parametrizadas, la prevención de filtraciones, los fallos de persistencia, la observabilidad, el contrato `AuditLogger` y el plan para pasar de InMemory a persistencia real.

## Entregables

| Entregable | Archivo |
|---|---|
| Endpoint, parámetros, respuesta, paginación y errores | [contrato-api.md §2](contrato-api.md#2-endpoint) |
| Autenticación, autorización y permiso `auditoria.ver` | [§3](contrato-api.md#3-autenticación-autorización-y-permisos) |
| Aislamiento y prevención de filtraciones | [§4](contrato-api.md#4-aislamiento-por-tenant-y-prevención-de-filtraciones) |
| Consultas parametrizadas y fallos de persistencia | [§5–6](contrato-api.md#5-consultas-parametrizadas) |
| Observabilidad y correlación | [§7](contrato-api.md#7-observabilidad-y-correlación) |
| Plan InMemory → persistencia real | [§8](contrato-api.md#8-plan-para-sustituir-inmemory-por-persistencia-real) |
| Contrato `AuditLogger` | [contrato-auditlogger.md](contrato-auditlogger.md) |
| Ruta protegida con el RBAC real | `app/GobernanzaAuditoria/GobernanzaAuditoriaServiceProvider.php` (`rbac:auditoria.ver`) |

## Archivos creados

- `contrato-api.md` y `contrato-auditlogger.md`.
- `app/GobernanzaAuditoria/GobernanzaAuditoriaServiceProvider.php`.
- `micro-his/gobernanza-auditoria/src/Integration/*` (`RbacAccessGate`, `RbacDenialRecorder`, `SystemClock`, `ErrorLogFailureReporter`).

## Relación con semanas anteriores

Formaliza los códigos de error que ya aparecían en los diagramas de la Semana 1 y la validación de la Semana 4. Los adaptadores de integración implementan los puertos de la Semana 3.

## Instrucciones

Prueba de integración con el RBAC de `develop`. Se exporta sin cambiar de rama.

```bash
git archive origin/develop micro-his/rbac | tar -x -C /tmp/rbac-develop
```

```bash
RBAC_DIR=/tmp/rbac-develop/micro-his/rbac php micro-his/gobernanza-auditoria/tests/rbac_integration.php
```

## Pruebas ejecutadas y resultados reales

| Suite | Resultado |
|---|---|
| `blackbox.php` (contrato HTTP) | 34 pruebas, 0 fallos. |
| `rbac_integration.php` con el RBAC de `origin/develop` @ 7c36ab4 | 6 pruebas, 0 fallos (la Semana 13 agrega TC-I-07: 7). |
| `rbac_integration.php` con el RBAC de esta rama | 3 pruebas, 0 fallos. TC-I-04…06 se omiten porque esta copia no tiene `Application/Authorize.php`. |
| `laravel_glue.php` (controlador Laravel con `Illuminate\Http\Request`) | 4 pruebas, 0 fallos (la Semana 7 agrega TC-L-05, el contenedor real). |

Evidencia: [../evidencias/s15-ejecucion-suites-rbac-develop.txt](../evidencias/s15-ejecucion-suites-rbac-develop.txt).

## Dependencias pendientes

| Dependencia | Responsable | Bloquea |
|---|---|---|
| Aprobar `auditoria.ver` en `config/rbac.php` | María (RBAC) | Toda consulta real (hasta entonces, 403). |
| Enlazar `DenialRecorder` → `RbacDenialRecorder` en `RbacServiceProvider` | María (RBAC) | Que las denegaciones lleguen a la bitácora. |
| Registrar `GobernanzaAuditoriaServiceProvider` en `bootstrap/providers.php` (archivo compartido) | Equipo | Que la ruta exista en Laravel. |
| `App\Models\Tenant`/`User` para `TenantMiddleware` y `JwtAuth` | Usuarios/Tenants | Que todo `/api/v1` funcione (hoy da HTTP 500 según el README). |
| `audit_log` fusionado y validado | Javier y Tenants | `PdoAuditLog` en producción. |

## Limitaciones conocidas

- La ruta Laravel **no se ejecutó de punta a punta**: faltan PostgreSQL, los modelos `Tenant`/`User` y `RbacServiceProvider` en esta rama. Se probaron el controlador y el `register()` del proveedor de forma aislada (TC-L-01…04; TC-L-05 se agrega en la Semana 7).
- La doble autorización (middleware y caso de uso) consulta los roles dos veces por petición (H-GOB-06).
