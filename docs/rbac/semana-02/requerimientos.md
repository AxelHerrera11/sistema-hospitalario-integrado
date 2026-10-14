# Semana 02 — Módulo RBAC: Requisitos funcionales y no funcionales

> Entrega documental + ejemplo ejecutable sobre el Principio Abierto/Cerrado (OCP).
> **Alcance:** Semanas 2. Lo que no se implementa aún se marca como *Propuesto — se implementa desde Semana 3*. No se modifica `app/`, `database/` ni `routes/`.

## 1. Contexto (qué hay en el repositorio hoy)

| Hecho | Evidencia |
|---|---|
| No existe verificación de roles/permisos en `app/`: no hay `can()`, `Gate` ni middleware `permission:` en rutas. Solo gestión de token y tenant. | `app/Http/Middleware/JwtAuth.php:19-30`, `app/Http/Middleware/TenantMiddleware.php:18-28`, `routes/api.php:6-17` |
| Los únicos middlewares registrados son `tenant`, `jwt.auth`, `jwt.refresh`; `withExceptions` está vacío. | `bootstrap/app.php:17-24` |
| El rol se guarda como texto libre (`role_code text NOT NULL`, sin catálogo ni CHECK de valores). | `database/migrations/central/2026_08_11_000100_create_identity_and_memberships.php:30-31` |
| Asignación/revocación de rol = membresías por hospital: única activa sin vencer por `(user_id, hospital_id, role_code)`. | `database/migrations/central/2026_08_11_000100_create_identity_and_memberships.php:41` |
| La base HOSPITAL replica el rol del usuario local. | `database/migrations/hospital/2026_08_11_000100_create_local_identity_and_professional_tables.php:18` |
| Único rol concreto en código: `'Recepcionista'` al registrar; login carga `roles, tenant`. | `app/Http/Controllers/Api/V1/AuthController.php:32,40` |
| Las tablas de roles/permisos de Spatie están configuradas (sin `teams`). | `config/permission.php:11-15,31` |
| No hay seeder de roles ni permisos. | `database/seeders/DatabaseSeeder.php:1-13` |

**Semana 1 (fuente de requisitos RBAC):** UCs `UC-RBAC-01..08`; catálogo de roles de ejemplo (`SuperAdmin`, `AdminHospital`, `Medico`, `Enfermeria`, `TecnicoLab`, `Bioquimico`, y `Recepcionista` en `AuthController`); convención de permisos `modulo.accion`; denegación `403` con `{ "code": "RBAC_DENIED", "message": "...", "trace_id": "..." }` (propuesto en docs, no en código).
- `docs/rbac/semana-01/README.md:17,23,57`
- `docs/rbac/semana-01/narrativa-alcance-trazabilidad.md:11,14,16,36-43,48-54`

## 2. Requisitos funcionales (RF) — propuestos (Semana 2 → Semana 3)

| ID | Requisito | Fuente / justificación |
|---|---|---|
| RF-RBAC-01 | El sistema mantiene un **catálogo de roles y permisos**, sin valores fijos en el código de verificación. | Semana 1: `narrativa…:11`; OCP (rol = dato) |
| RF-RBAC-02 | Un **permiso se asigna a un rol** (relación `role_has_permissions`), registrando quién y cuándo. | `config/permission.php:15` |
| RF-RBAC-03 | Toda ruta protegida **declara el permiso** que exige. | Semana 1: `README.md:23` (`permission:modulo.accion`) |
| RF-RBAC-04 | Ante una operación protegida el sistema **niega por defecto**: 401 sin token válido, 403 sin membresía activa/vigente o sin el permiso (`UC-RBAC-04,05`). Falla segura. | `JwtAuth.php:19-30`, `TenantMiddleware.php:18-28`, Semana 1: `README.md:57` |
| RF-RBAC-05 | Cada denegación se **registra con `trace_id` determinista** y los campos usuario, permiso, operación, hospital y motivo (p. ej. `sin_permiso_de_rol`, `fuera_de_hospital`), y se responde `403 {code: RBAC_DENIED, message, trace_id}`; si la bitácora falla, la operación **sigue denegada** (`UC-RBAC-05,06`). | Semana 1: `narrativa…:16,54`; `README.md:57`; ejemplo `ocp-demo.php` |
| RF-RBAC-06 | La autorización se evalúa **respecto del hospital de la solicitud** (`X-Tenant-ID`) y la membresía vigente de ese tenant (`UC-RBAC-04`). | `database/migrations/central/…:41`; `TenantMiddleware.php:18-28` |
| RF-RBAC-07 | Incorporar una regla de autorización nueva (p. ej. alcance por hospital, horario) **no modifica el motor** de autorización: se agrega una clase que implementa la regla (OCP). | Ver diseño 02-despues.svg y ejemplo `ocp-demo.php` |
| RF-RBAC-08 | El frontend puede **consultar los permisos efectivos** del usuario en su tenant (`UC-RBAC-08`). | Semana 1: `narrativa…:43` |

## 3. Requisitos no funcionales (RNF) — propuestos

| ID | Requisito | Verificación |
|---|---|---|
| RNF-RBAC-01 | La decisión de autorización usa **caché de permisos** (config Spatie), invalidado al asignar/revocar un rol. | `config/permission.php:31` (teams), Semana 1: `README.md:57` |
| RNF-RBAC-02 | La auditoría **no condiciona la decisión** (falla segura; la denegación persiste aunque la bitácora falle). | `ocp-demo.php` escenario 4 (`FailingDenialRecorder`); Semana 1: `README.md:57` |
| RNF-RBAC-03 | **Extensibilidad:** 1 rol nuevo = 1 fila de datos; 1 regla nueva = 1 clase nueva registrada (`RoleGrantsPermission`, `HospitalScopeRule`). `Authorizer` no cambia. | `ejemplo/ocp-demo.php` (escenario 3) |
| RNF-RBAC-04 | Stack aceptable: framework y librerías ya presentes (Laravel + `spatie/laravel-permission`); el ejemplo usa PHP 8.2 vanilla. | `composer.json`, `config/permission.php:11-15` |

## 4. Criterios de aceptación (Dado / Cuando / Entonces)

| ID | Dado | Cuando | Entonces | Verificación |
|---|---|---|---|---|
| RF-RBAC-01 | No hay `/api/v1/roles` en rutas | se lista el catálogo de roles | se obtiene del catálogo declarado | Prueba **planificada, no implementada** |
| RF-RBAC-02 | Existe rol y permiso | se asigna permiso al rol | queda en `role_has_permissions` | Idem |
| RF-RBAC-03 | Ruta con guard `permission:modulo.accion` | un rol sin ese permiso accede | 403 RBAC_DENIED + `trace_id` | Idem |
| RF-RBAC-04 | Sin reglas registradas (o token/tenant/permiso ausente o inválido) | se llama una ruta protegida | 401/403 sin ejecutar el módulo (denegación por defecto) | `ocp-demo.php` escenario 1 (sin reglas) |
| RF-RBAC-05 | Denegación con motivo (p. ej. `sin_reglas`, `fuera_de_hospital`) | se registra en bitácora | respuesta 403 con `trace_id`; el registro incluye usuario, permiso, operación, hospital, motivo y `trace_id` | `ocp-demo.php` escenarios 1 y 3; bitácora que falla: escenario 4 |
| RF-RBAC-07 | `Authorizer` con regla base | se agrega la regla nueva `HospitalScopeRule` | el resultado de `muestras.recibir` en H1 pasa de PERMITIDO a DENEGADO **sin modificar `Authorizer`** (combinación AND) | `ocp-demo.php` escenario 2 → 3 |
| RF-RBAC-08 | Usuario autenticado con membresía vigente | consulta `GET /auth/me/permissions` | lista permisos efectivos del tenant | Prueba planificada |

## 5. Supuestos de diseño (SD)

| ID | Supuesto | Necesita validación |
|---|---|---|
| SD-RBAC-01 | Los permisos no se almacenan hardcodeados por ruta sino en la tabla `permissions`. | Alta de permisos (columna `permissions.name`) |
| SD-RBAC-02 | Los nombres de permiso NO siguen aún la convención `modulo.accion`: hoy solo existe `Recepcionista` en código. | `AuthController.php:32` vs `narrativa…:11` |
| SD-RBAC-03 | Spatie `teams=false` no aísla permisos por hospital: el alcance por tenant se implementa con la regla de hospital (`HospitalScopeRule`). | `config/permission.php:31` |
| SD-RBAC-04 | `role_code` seguirá siendo identificador de rol (ya usado por membresías). No se agrega CHECK de catálogo en esta semana. | Migraciones `…:30,41` |
| SD-RBAC-05 | El catálogo de permisos llega al motor vía la interfaz `PermissionProvider` (implementada por `InMemoryPermissions` en el ejemplo); toda regla implementa `AuthorizationRule` y recibe un contexto (`role`, `required`, hospital/membresía). | APIs futuras del módulo |
| SD-RBAC-06 | El `trace_id` lo genera `Authorizer` al denegar (ejemplo: `trace-0001`, secuencial); `DenialRecorder` solo lo registra. | Formato con Gobernanza |
| SD-RBAC-07 | Un usuario puede tener varias membresías/roles por hospital; la autorización siempre usa el hospital del `X-Tenant-ID`. | Semana 1 `README.md:17` |
| SD-RBAC-08 | Las reglas de autorización se combinan con **AND** (todas deben permitir); sin reglas, denegación por defecto; la primera regla que niega define el motivo. No hay fuente en el repositorio que defina esta combinación. | Diseño DESPUÉS + ejemplo `ocp-demo.php` |

## 6. Dependencias externas

| Dependencia | Nota |
|---|---|
| Autenticación JWT y contexto de tenant | Ya construidos: `JwtAuth`, `TenantMiddleware`; no se tocan |
| Auditoría/bitácora (AuditLogger) | Módulo **Gobernanza**, externo; esta semana solo la interfaz `DenialRecorder` |
| `spatie/laravel-permission` | Ya instalado y configurado |

## 7. Fuera del alcance de Semana 2

- Implementación en `app/` (endpoints RBAC, middleware `permission:`, guard de Vue, seeder de roles/permisos) → **Semana 3**.
- Auditoría central real (Gobernanza) → **Semana 4**.
- Módulos clínicos protegidos (prescripciones, muestras, resultados) → **Semanas siguientes**.

## 8. Trazabilidad

| RF | UC-RBAC (Semana 1) |
|---|---|
| RF-RBAC-01, 02 | UC-RBAC-02 (revocar), soporte |
| RF-RBAC-03, 04 | UC-RBAC-03, 04 |
| RF-RBAC-05 | UC-RBAC-05, 06 |
| RF-RBAC-06 | UC-RBAC-04 (tenant/membresía) |
| RF-RBAC-07 | Impacto OCP sobre UC-RBAC-04/05 |
| RF-RBAC-08 | UC-RBAC-08 |

| RNF | UC-RBAC |
|---|---|
| RNF-RBAC-01, 02 | UC-RBAC-04, 05, 06 |
| RNF-RBAC-03 | UC-RBAC-04 (extensión) |