# Registro de hallazgos — RBAC (Semana 13)

Revisión estática del repositorio en la rama `feature/asii-02-semana-13-rbac-mar-03` (base
`develop`), más una ejecución real del prototipo. Toda evidencia es `archivo:línea` y fue
comprobada al redactar (existencia de archivo o lectura de línea).

**Severidad.** `Bloqueo` = impide aprobar; `Riesgo` = permite aprobar con plan; `Hecho` = verificado.
**Estado.** `Abierto`, `Verificado` o `Pendiente de validación con terceros`.

**Snapshot de referencia.** Los hechos se verificaron sobre la base `255472f` (17-10-2026). Estado de `origin/develop` al auditar esta corrección: `1d4bbe0` (19-10-2026). Los merges posteriores de S8–S11 son documentación/estilos (salvo `styles.css` de S11, #148); H-01…H-12 siguen vigentes.

| ID | Severidad | Hallazgo | Evidencia | Estado |
|---|---|---|---|---|
| H-01 | Bloqueo | Los modelos `App\Models\User` y `App\Models\Tenant` no existen (`app/Models/` ausente) pero se referencian en el código. | `app/Http/Controllers/Api/V1/AuthController.php:6,25,56,80`; `app/Http/Middleware/TenantMiddleware.php:5,23`; `config/auth.php:72` | Abierto |
| H-02 | Bloqueo | No hay migración de las tablas de Spatie (`roles`, `permissions`, `model_has_roles`), que el código usa al asignar rol. | `config/permission.php:10-16`; sin migración correspondiente en `database/migrations/**` | Abierto |
| H-03 | Bloqueo | En `origin/develop` no existe el middleware `permission:` ni su alias, y `routes/api.php` no protege endpoints RBAC. Está implementado en el PR #136 de Semana 7 (`EnsurePermission`, `RbacServiceProvider` y el alias `permission`), **pendiente de integración**. | `origin/develop:bootstrap/app.php:16-20`; `origin/develop:routes/api.php:6-18`; PR #136: `app/Http/Middleware/EnsurePermission.php`, `app/Providers/RbacServiceProvider.php`, `bootstrap/app.php:20` | Abierto |
| H-04 | Bloqueo | Los controladores y endpoints HTTP Laravel de RBAC siguen pendientes incluso considerando el PR #136: `routes/api.php` solo tiene `auth/*` y no hay controladores RBAC. El handler PHP vanilla de Semana 5 (PR #179) no constituye una ruta Laravel funcional y no lo resuelve. | contrato: `docs/rbac/semana-01/README.md:21`; `origin/develop:routes/api.php` solo tiene `auth/*`; PR #136: `routes/api.php` sin endpoints RBAC; PR #179: `micro-his/rbac/src/Presentation/RbacHttpHandler.php` (handler vanilla, no una ruta Laravel) | Abierto |
| H-05 | Riesgo | `spatie` con `teams=false` no aísla por hospital; el aislamiento debe implementarse aparte. | `config/permission.php:31`; supuesto SD-RBAC-03 en `docs/rbac/semana-02/README.md:45` | Pendiente de validación con terceros |
| H-06 | Riesgo | No hay seeder de roles/permisos ni niveles; el catálogo no existe. | `database/seeders/DatabaseSeeder.php:11` | Abierto |
| H-07 | Riesgo | Los niveles de privilegio son un supuesto sin validar. | `micro-his/rbac/src/Domain/AssignerPrivilegeRule.php:11`; SD-RBAC-09 en `docs/rbac/semana-03/README.md:46` | Pendiente de validación con terceros |
| H-08 | Riesgo | Resolución de tenant sin configuración: `stancl/tenancy` está instalado pero no hay `config/tenancy.php` ni participación del paquete. | `composer.json:13`; `config/tenancy.php` inexistente (`Test-Path` = False) | Abierto |
| H-09 | Riesgo | La comprobación de tenant depende de `User` inexistente y de `tenant_id`, no de `hospital_memberships`. | `app/Http/Middleware/JwtAuth.php:24-31` | Abierto |
| H-10 | Riesgo | La auditoría general de RBAC no tiene tabla en producción; solo existe auditoría de acceso entre hospitales. | `database/migrations/central/2026_08_11_000300_create_consent_and_access_control.php:60,139-142` | Abierto |
| H-11 | Riesgo | La consulta de bitácora protegida por permiso no está implementada. | sin ruta/controlador en `routes/api.php`; propuesta en `docs/rbac/semana-11` (#149–#151, pendientes) | Abierto |
| H-12 | Riesgo | Documentación referenciada ausente en la rama. | `README.md:101,169,229-230` referencian `docs/weekly-plan.md` y `docs/worktree-guide.md`; ambos inexistentes | Abierto |
| H-13 | Hecho | El prototipo RBAC pasa sus 9 pruebas y la suite termina en verde. | `micro-his/rbac/tests/run.php`; salida `informe-tecnico.md` §1 | Verificado |
| H-14 | Hecho | Denegación por defecto, combinación AND, `trace_id` y falla segura están implementados. | `micro-his/rbac/src/Domain/Authorizer.php:27,28-32,36,37-42` | Verificado |
| H-15 | Hecho | Membresía y auditoría se guardan en una sola transacción con rollback. | `micro-his/rbac/src/Persistence/PdoRbacStore.php:31-42` | Verificado |
| H-16 | Hecho | El aislamiento por hospital se filtra en consulta por usuario y hospital. | `micro-his/rbac/src/Persistence/PdoRbacStore.php:27` | Verificado |
| H-17 | Hecho | Las entradas se validan por tipo y evita la coerción `"Array"` / `TypeError`. | `micro-his/rbac/src/Presentation/RbacHttpHandler.php:37-41` | Verificado |
| H-18 | Hecho | El esquema de membresías por hospital con vigencia existe y es válido. | `database/migrations/central/2026_08_11_000100_create_identity_and_memberships.php:26-41` | Verificado |

## Detalle de los hallazgos de mayor impacto

- **H-01/H-02/H-09 (bloquean la integración Laravel).** El flujo `auth` y el middleware `tenant`
  fallan en tiempo de ejecución porque referencian un modelo ausente, y `assignRole`
  (`AuthController.php:32`) depende de tablas que no se migran. Consecuencia: las rutas `auth/*`
  no pueden proteger nada; el RBAC productivo no arranca sobre esta base.
- **H-03 (implementado en el PR #136, pendiente de integración).** El middleware `permission:`
  aliasado, `EnsurePermission` y `RbacServiceProvider` no están en `origin/develop`, pero sí en
  el PR #136 de Semana 7. Hasta integrarlo, `routes/api.php` de `develop` no protege endpoints
  RBAC.
- **H-04 (pendiente incluso con el PR #136).** Los controladores y endpoints HTTP Laravel de
  RBAC siguen sin implementarse: el PR #136 no añade endpoints RBAC (solo `auth/*`), y el handler
  PHP vanilla de Semana 5 (PR #179, `micro-his/rbac/src/Presentation/RbacHttpHandler.php`) no es
  una ruta Laravel funcional. No es un defecto del prototipo, sino alcance no entregado.
- **Cierre de H-03/H-04.** Integrar #136 cierra H-03 (middleware y alias); H-04 requiere endpoints/controladores Laravel y no se cierra con #136 ni con el handler vanilla de #179.
- **H-13/H-14/H-15/H-16/H-17 (núcleo verificado).** El prototipo demuestra las reglas que el
  módulo debe cumplir: denegación por defecto, AND de reglas, trazabilidad, falla segura,
  transacción con rollback, aislamiento por hospital y saneamiento de entradas.

## Método y límites

- Se leyó cada archivo citado y se comprobó la existencia de rutas y configuraciones referidas.
- Se ejecutó **solo** el prototipo `micro-his/rbac`. No se ejecutó la suite Laravel ni se
  desplegó nada; no se modificó código de producción.
- Los hallazgos marcados «Pendiente de validación con terceros» requieren firma de
  usuarios/tenants o gobernanza y no se cierran solo con lectura de código.
