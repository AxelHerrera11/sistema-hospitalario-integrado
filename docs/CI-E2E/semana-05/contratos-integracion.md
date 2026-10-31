# Contratos de integración con otros módulos

Consumidor en todos los casos: **QA, E2E y CI — Cindy Ruano**. «Existente» = el proveedor ya lo publicó en `develop` (revisado en `origin/develop` 5e6f0044, 21-oct-2026) y aquí solo se documenta cómo se consume. «Propuesto» = pendiente de validación con el responsable.

## C-01 Autenticación y tenant (proveedor: Cindy, Tenants/Auth) — Existente

| Campo | Valor |
|---|---|
| Funcionalidad | Login, identidad, cierre de sesión para el E2E de pila completa |
| Endpoints | `POST /api/v1/auth/login`, `GET /api/v1/auth/me`, `POST /api/v1/auth/logout` |
| Parámetros | Cabecera `X-Tenant-ID` (UUID del hospital) en todas; `Authorization: Bearer` en `me`/`logout` |
| Solicitud | `{"email", "password"}` |
| Respuesta | `200 {access_token, …}` + cookie `refresh_token`; `me` → `{user, hospital, roles, roles_version}` |
| Códigos / errores | 400 `TENANT_REQUIRED`/`TENANT_INVALID`, 401 `INVALID_CREDENTIALS`/`TOKEN_MISSING`/`SESSION_REVOKED`, 403 `TENANT_MISMATCH`/`HOSPITAL_INACTIVE`, 404 `HOSPITAL_NOT_FOUND` (formato `{error, message}`) |
| Permisos | Públicas `login`/`refresh`; `me`/`logout` solo autenticación |
| Indisponibilidad | El job e2e falla al levantar `artisan serve`; la suite `e2e-auth-rbac` queda sin reporte → hallazgo del gate |
| Evidencia esperada | `qa-evidence/e2e/auth-rbac.xml` (E2E-AUTH-01..08) + `laravel-server.log` |

## C-02 RBAC (proveedor: María) — Existente; permisos `qa.*` Propuestos

| Campo | Valor |
|---|---|
| Funcionalidad | Autorizar rutas y validar que el catálogo cubra todas las rutas API |
| Contrato | Middleware `rbac:<permiso>` (`App\Rbac\RequirePermission`), catálogo `config/rbac.php` (`roles`, `pending`, `catalog`) |
| Respuesta de error | `403 {"code": "RBAC_DENIED", "trace_id"}`; 503 `RBAC_UNAVAILABLE`; 401 `AUTH_REQUIRED` |
| Uso en QA | `bin/route-guard.php` lee `route:list --json` y el catálogo; E2E-AUTH-05/06 verifican 403 reales |
| Datos obligatorios | Cada ruta API con `tenant` + `JwtAuth` + `rbac:` salvo la lista de `config/route-guard.json` |
| Indisponibilidad | Si `config/rbac.php` no carga, el contrato termina con exit 3 y el gate bloquea |
| Evidencia esperada | `qa-evidence/contract/route-guard.xml` (hoy 17/17 rutas OK) |
| Propuesta | `qa.ejecuciones.ver`, `qa.ejecuciones.registrar`, `qa.gates.evaluar` — ver [contrato-api.md](contrato-api.md#permisos-propuesta-pendiente) |

## C-03 Gobernanza y Auditoría (proveedor: Cindy) — Existente

| Campo | Valor |
|---|---|
| Funcionalidad | Registrar cada acción y denegación del módulo |
| Contrato | `MicroHis\Gobernanza\Application\RecordAuditEvent::record(actor, tenant, módulo, acción, AuditOutcome, AuditCriticality, tipo, id, correlación, razón, contexto)` |
| Datos | módulo `qa_calidad`; acciones `qa.ejecucion.planificada`, `qa.ejecucion.{start,complete,abort}`, `qa.resultado.registrado`, `qa.gate.evaluado`, `qa.acceso.denegado` |
| Validaciones | Formatos de `AuditEvent` (razón `[a-z0-9_]`, contexto sin claves de secretos ni clínicas) |
| Indisponibilidad | Security (gate, denegación) → `QaStorageFailure` → 503; Informational → se reporta y sigue |
| Evidencia | `tests/application.php` A13–A16 |

## C-04 Suites `tests/run.php` de los micro-HIS (proveedores: cada dueño) — Existente por convención

| Campo | Valor |
|---|---|
| Contrato | `php micro-his/<módulo>/tests/run.php` termina con exit 0 si todo pasa y ≠ 0 si algo falla |
| Uso | `bin/micro-his-suites.php` deja un caso JUnit por módulo; sin `run.php` → `skipped` |
| Hallazgo actual | 4 de 14 módulos sin `tests/run.php` (`signos-vitales`, `ingreso-resultados-lab`, `validacion-resultados-bioquimico`, `nativephp-extension-prototype`) → la política propuesta rechaza (ver [pendientes](../pendientes-y-bloqueos.md#d-01)) |

## C-05 Migraciones (proveedor: Javier) — Existente

Contrato: `composer migrate:central`, `migrate:framework`, `migrate:hospital` contra PostgreSQL 16. El job e2e los ejecuta en bases efímeras `his_*_e2e`; la suite `Database` corre en `his_*_test` del mismo contenedor. No se agrega ni modifica ninguna migración.

## C-06 QA funcional y regresión manual (proveedor/par: Madelin, ASII-28) — Propuesto

| Campo | Valor |
|---|---|
| Funcionalidad | Acordar qué flujos se automatizan (Cindy) y cuáles se prueban a mano (Madelin) — acuerdo pendiente del plan de integración |
| Propuesta de reparto | Automatizado: autenticación, tenant, RBAC 401/403, contrato de rutas, suites de todos los módulos, flujo QA. Manual: flujos clínicos con UI (pacientes, expediente, órdenes, resultados) hasta que sus módulos tengan API estable |
| Intercambio | Madelin puede adjuntar `qa-gate-decision/gate-decision.json` de la ejecución a su campaña de regresión (lectura; no se modifica su módulo) |
| Evidencia esperada | Matriz compartida «flujo → manual / automatizado → evidencia» |

## C-07 Formato común de errores (proveedor: Lis, contratos) — Observación

Tenants/Auth responde `{error, message}` y RBAC `{code, trace_id}`. El E2E acepta ambos tal como están. Se informa a Lis como insumo para el acuerdo de formato común; no se cambia ningún controlador.

## C-08 Datos semilla comunes (Cindy y Madelin) — Propuesto

Mientras no exista el seeder común, `tests/E2E/fixtures/seed-e2e.php` inserta 1 red, 2 hospitales y 2 usuarios **ficticios** (`admin_hospital`, `medico`) solo si `E2E_ALLOW_SEED=1`, `APP_ENV=e2e`, base `*_e2e` y host local. Contraseña generada por ejecución y enmascarada en el log.
