# Informe técnico de la revisión — RBAC (Semana 13)

Rama `feature/asii-02-semana-13-rbac-mar-03` (base `develop`). Distingue **hechos
comprobados**, **riesgos** y **bloqueos**. No se modificó código de producción.

## 1. Hechos comprobados

1. **El prototipo RBAC funciona.** Se ejecutó `php micro-his/rbac/tests/run.php` (PHP 8.2.12) y
   la salida real fue:

   ```text
   OK: camino feliz: 201, membresía y auditoría guardadas
   OK: regla de dominio: rol que supera el privilegio -> 403 y denegación con trace_id
   OK: regla de dominio: sin permiso rbac.asignar -> 403 RBAC_DENIED
   OK: regla de dominio: rol ya asignado -> 409
   OK: validación: rol inexistente -> 422
   OK: validación: entradas inválidas (array/objeto/espacios en user_id, headers no string) -> 422 sin TypeError
   OK: validación: ninguna entrada inválida escribió membresía ni auditoría
   OK: error de persistencia: auditoría caída -> 500 y rollback
   OK: falla segura: bitácora caída -> sigue denegado (403)
   Todas las pruebas pasaron.
   ```

2. **Reglas de autorización verificadas por lectura:** denegación por defecto, combinación AND y
   primera regla que niega fija el motivo (`micro-his/rbac/src/Domain/Authorizer.php:27-32`);
   `trace_id` generado por el autorizador (`:36`); falla segura ante bitácora caída (`:37-42`).
3. **Persistencia atómica:** membresía + auditoría en una transacción con rollback
   (`micro-his/rbac/src/Persistence/PdoRbacStore.php:31-42`) y sentencias preparadas.
4. **Aislamiento por hospital en consulta:** roles filtrados por usuario y hospital
   (`PdoRbacStore.php:27`).
5. **Saneamiento de entradas** por tipo, sin coerción `"Array"` (`RbacHttpHandler.php:37-41`).
6. **Esquema de membresías con vigencia** en CENTRAL (`hospital_memberships`:
   `hospital_id`, `role_code`, `status`, `valid_from`, `valid_to`;
   `database/migrations/central/2026_08_11_000100_create_identity_and_memberships.php:26-41`).
7. **Auditoría append-only disponible en CENTRAL** para acceso entre hospitales
   (`2026_08_11_000300_create_consent_and_access_control.php:139-142`).

## 2. Riesgos

- **Aislamiento no nativo (H-05).** `config/permission.php:31` usa `teams=false`; el aislamiento
  por hospital debe vivir en la regla/middleware, no en Spatie. Requiere validación de
  usuarios/tenants.
- **Catálogo inexistente (H-06).** `DatabaseSeeder.php:11` está vacío: sin roles, permisos ni
  niveles, la autorización productiva no tiene datos.
- **Niveles sin validar (H-07).** `AssignerPrivilegeRule.php:11` es un supuesto (SD-RBAC-09).
- **Tenant sin configuración (H-08/H-09).** `stancl/tenancy` instalado (`composer.json:13`) pero
  sin `config/tenancy.php`; la comprobación de tenant depende de `User` y `tenant_id`, no de
  `hospital_memberships`.
- **Auditoría general pendiente (H-10/H-11).** No hay tabla de auditoría RBAC en producción ni
  la consulta protegida de bitácora.
- **Documentación referenciada ausente (H-12).** `README.md:101,169` enlaza guías inexistentes en
  la rama.

## 3. Bloqueos

- **Modelos ausentes (H-01).** `App\Models\User` y `App\Models\Tenant` no existen
  (`app/Models/` ausente) y se referencian en `AuthController.php:6` y `TenantMiddleware.php:5`.
  Las rutas `auth/*` y el middleware `tenant` no pueden operar.
- **Tablas Spatie no migradas (H-02).** `AuthController.php:32` (`assignRole`) fallaría: no hay
  migración de `roles`/`permissions`/`model_has_roles`.
- **Superficie RBAC inexistente (H-03/H-04).** No hay middleware `permission:` aliasado
  (`bootstrap/app.php:16-20`) ni rutas RBAC (`routes/api.php:6-18`). No es un defecto del
  prototipo, sino alcance aún no implementado.

## 4. Conclusión

- **Qué se puede aprobar hoy:** el diseño y el prototipo del núcleo RBAC (autorización,
  trazabilidad, transacción, aislamiento en consulta y saneamiento de entradas), con H-13…H-18
  como evidencia.
- **Qué no se puede revisar todavía:** el código Laravel del módulo RBAC, porque no existe
  (H-01…H-04). La revisión formal de esa capa queda **programada** y no puede declararse
  ejecutada.
- **Condición para aprobar el PR del módulo:** resolver o compensar los bloqueos H-01…H-04,
  validar los riesgos H-05/H-07 con usuarios/tenants, y completar seeder y auditoría (H-06/H-10/H-11).
- **Alcance de esta entrega:** plan, checklist, responsables, hallazgos e informe. Sin `push`,
  sin PR, sin merge y sin modificar código de producción.
