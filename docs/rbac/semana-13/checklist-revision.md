# Checklist de revisión técnica — RBAC (Semana 13)

Cada ítem es **verificable**: indica la evidencia esperada y dónde comprobarla. La columna de
estado se llena durante la revisión formal; hoy solo constan los hallazgos de
`registro-hallazgos.md`. `N/A` solo se admite con justificación.

## 1. Código

- [ ] El PR viene de un worktree de `origin/develop` y solo contiene el módulo RBAC (`README.md:77-99`).
- [ ] Capas separadas: Presentation sin SQL ni reglas; Application orquesta; Domain decide; Persistence aísla PDO (`micro-his/rbac/src/**`).
- [ ] Domain no depende de PDO ni de HTTP; las dependencias externas entran por interfaz (`PermissionProvider`, `DenialRecorder`).
- [ ] Entradas saneadas por tipo antes de usarlas: rechazo de array/objeto/espacios (`micro-his/rbac/src/Presentation/RbacHttpHandler.php:37-41`).
- [ ] Sin valores concatenados en SQL; solo sentencias preparadas (`micro-his/rbac/src/Persistence/PdoRbacStore.php:22,27,35,51`).
- [ ] `declare(strict_types=1)` y sin comentarios que contradigan el comportamiento real.
- [ ] Sin credenciales, tokens, `.env` reales ni datos clínicos en el diff.

## 2. Permisos (autorización)

- [ ] Denegación por defecto: sin reglas, toda operación se deniega (`micro-his/rbac/src/Domain/Authorizer.php:27`).
- [ ] Las reglas se combinan con AND; la primera que niega fija el motivo (`Authorizer.php:28-32`).
- [ ] Cada ruta protegida declara su permiso `modulo.accion` explícito (contrato propuesto `docs/rbac/semana-01/README.md:21-23`).
- [ ] El token de membresía (`rbac.roles.asignar`) y el de permisos de rol (`rbac.permisos.asignar`) no se mezclan (decisión de `feature/asii-02-semana-11-rbac-mar-03`, `docs/rbac/semana-11/README.md:44-55`).
- [ ] Regla anti-escalamiento verificada: el rol asignado no supera el privilegio del asignador (`micro-his/rbac/src/Domain/AssignerPrivilegeRule.php:11,18-19`).
- [ ] Los niveles de `AssignerPrivilegeRule::LEVELS` están validados con Gestión de usuarios (supuesto SD-RBAC-09, `docs/rbac/semana-03/README.md:46`).

## 3. Aislamiento por hospital (tenant)

- [ ] El hospital activo proviene del header `X-Tenant-ID` validado por el middleware `tenant` (`app/Http/Middleware/TenantMiddleware.php:14-32`).
- [ ] El token solo opera en el hospital del header; el middleware `jwt.auth` compara usuario y tenant (`app/Http/Middleware/JwtAuth.php:23-31`).
- [ ] Los roles se leen filtrando por usuario **y** hospital (`micro-his/rbac/src/Persistence/PdoRbacStore.php:27`).
- [ ] El aislamiento no depende de `spatie` `teams=true`: hoy `config/permission.php:31` está en `false` (SD-RBAC-03, `docs/rbac/semana-02/README.md:45`).
- [ ] La membresía por hospital y su vigencia respetan `hospital_memberships` (`hospital_id`, `role_code`, `status`, `valid_from`, `valid_to`; `database/migrations/central/2026_08_11_000100_create_identity_and_memberships.php:26-41`).
- [ ] La referencia local del usuario conserva `role_code` y `valid_to` sin FK física entre bases (`database/migrations/hospital/2026_08_11_000100_create_local_identity_and_professional_tables.php:13-29`).

## 4. Errores y respuestas

- [ ] 422 para payload/headers inválidos; 401 sin token; 403 denegación; 409 conflicto; 500 fallo de persistencia (`micro-his/rbac/src/Presentation/RbacHttpHandler.php:23-34`).
- [ ] El 403 expone `trace_id` y **no** revela el permiso faltante (`docs/rbac/semana-01/README.md:57`).
- [ ] El fallo de persistencia revierte la transacción (rollback) y no deja membresía parcial (`PdoRbacStore.php:33-41`).
- [ ] Falla segura: si la bitácora cae, la operación sigue denegada (`Authorizer.php:37-42`).
- [ ] Ninguna entrada inválida escribe membresía ni auditoría (prueba `micro-his/rbac/tests/run.php:45-46`).

## 5. Auditoría

- [ ] Toda asignación de rol escribe membresía y evento de auditoría en la misma transacción (`PdoRbacStore.php:31-42`).
- [ ] Toda denegación se registra con `trace_id` y motivo (`PdoRbacStore.php:44-47`).
- [ ] El `trace_id` lo genera el autorizador y es único por denegación (`Authorizer.php:36`).
- [ ] El registro de auditoría es de solo-anexado (append-only) donde exista tabla dedicada (`database/migrations/central/2026_08_11_000300_create_consent_and_access_control.php:139-142`).
- [ ] La interfaz de consulta de bitácora queda protegida por permiso (`rbac.denegaciones.ver`), no por ocultamiento de UI.

## 6. Dependencias

- [ ] Dependencias del módulo declaradas en `composer.json` (`spatie/laravel-permission` `:12`, `stancl/tenancy` `:13`, `tymon/jwt-auth` `:14`).
- [ ] Cada dependencia externa tiene su interfaz y su punto de fallo documentado (usuarios/tenants y auditoría).
- [ ] Los supuestos abiertos (SD-RBAC-*) tienen responsable y fecha de validación.
- [ ] Los modelos referenciados existen o el fallo está declarado como bloqueo (`App\Models\User`, `App\Models\Tenant`).
- [ ] Las tablas que el código usa existen por migración o seeder (`roles`, `permissions`, `model_has_roles`).

## 7. Documentación y evidencia

- [ ] README del módulo con `file:line` verificable y distinción hecho/riesgo/bloqueo.
- [ ] Declaración de uso de IA cuando aplique.
- [ ] Comandos de verificación declarados y su salida real adjunta.
