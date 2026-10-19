# Estrategia de pruebas y regresión del módulo RBAC

Estrategia por niveles y dimensiones, con criterios de entrada/salida. No describe campañas ejecutadas: define qué se prueba, con qué datos y cómo se evidencia.

## 1. Niveles de prueba

| Nivel | Objeto | Herramienta / base | Evidencia |
|---|---|---|---|
| Unitario de dominio | `Authorizer`, `RoleGrantsPermission`, `AssignerPrivilegeRule`, `Membership` | `micro-his/rbac/tests/run.php` con SQLite en memoria | Salida `OK`/`FALLO` por comprobación |
| Integración de persistencia | `PdoRbacStore` (permisos, membresías, auditoría, transacción) | SQLite en memoria; PostgreSQL desechable (`phpunit.xml`, puertos 55431/55433) | Filas resultantes y rollback verificado |
| HTTP / middleware | `RbacHttpHandler` y `app/Http/Middleware/EnsurePermission` | Suite del micro-HIS; pruebas HTTP de Laravel (no existe suite HTTP RBAC todavía) | Código de estado y cuerpo JSON |
| Frontend | `resources/js/router/permissions.js` (`can`, `canAny`; PR #136, pendiente de merge) | `resources/js/router/permissions.test.mjs` (Node) | `OK: pruebas de permisos del router pasaron.` |
| Prototipo (E2E manual/headless) | Flujos A–E del prototipo | Chromium headless sobre `docs/rbac/semana-11/` | PASS/FAIL por escenario |

## 2. Cobertura por dimensión

### Permisos
- Camino feliz: actor con el permiso ejecuta la operación (201).
- Denegación por falta de permiso: 403 `RBAC_DENIED` con motivo.
- Matriz rol×permiso (`semana-05/permisos.md`, PR #180, pendiente de merge): un caso por rol y por permiso representativo.
- Anti-escalamiento: actor de nivel inferior no asigna rol superior (`rol_supera_privilegio`).
- Rol duplicado (409), rol inexistente (422).

### Autenticación
- Sin token → 401 `UNAUTHENTICATED`.
- Token válido pero membresía ausente en el hospital → 403.
- Membresía no vigente (`valid_from`/`valid_to`) → 403 (pendiente de implementación; caso marcado como fallo esperado hasta que exista).

### Tenant
- Usuario con rol solo en H1 no autoriza en H2 (mismo usuario espejo).
- `X-Tenant-ID` ausente o inválido → 422/403 sin ejecutar el módulo.
- Verificación de que `rolesIn()` nunca mezcla hospitales.

### Trazabilidad
- Toda denegación registra `trace_id` recuperable en `audit_log`.
- Unicidad de `trace_id` en un lote de denegaciones.
- Todo cambio de rol genera el evento `rol_asignado` en la misma transacción.

### Fallos de persistencia
- Caída de la tabla de auditoría → 500 y rollback, sin membresía residual.
- Caída de la bitácora durante la denegación → la operación sigue denegada (falla segura).
- Reintento tras recuperación no duplica membresías (idempotencia deseada; objetivo).

## 3. Datos y entornos

- **Datos ficticios:** hospitales `H1`/`H2`; usuarios `admin1`, `med1`, `enf1`, `recep1`; roles del catálogo de Semana 5 (PR #180, pendiente de merge). Sin datos clínicos reales.
- **Entorno base:** SQLite en memoria para el micro-HIS (sin infraestructura).
- **Entorno de integración:** dos bases PostgreSQL desechables de `phpunit.xml` (`btree_gist` en HOSPITAL), según `README.md`.
- **Entorno de interfaz:** navegador Chromium (prototipo) y Node para los helpers.

## 4. Suite de regresión

La suite base es `micro-his/rbac/tests/run.php`. Las comprobaciones existentes (observadas 9/9) cubren: camino feliz, dos reglas de dominio, rol duplicado, rol inexistente, entradas inválidas sin efectos, error de persistencia y falla segura.

Adiciones exigidas antes del gate G2, trazadas a las métricas:

| Comprobación nueva | Dimensión | Métrica |
|---|---|---|
| Denegación por usuario solo en otro hospital | Tenant | Q-TEN-01, Q-TEN-02 |
| Lote de denegaciones con `trace_id` únicos | Trazabilidad | Q-TRZ-02 |
| Membresía no vigente → 403 | Autenticación | Q-AUTH-02 |
| Reintento post-recuperación sin duplicar | Persistencia | Q-PRS-01 |
| Matriz rol×permiso completa | Permisos | Q-PERM-02, Q-PERM-03 |

**Selección para regresión completa:** toda la suite base más las cinco adiciones se ejecuta en cada gate G1–G3 y ante cambios en `micro-his/rbac/`, `EnsurePermission`, `permissions.js` (PR #136, pendiente de merge) o `config/permission.php`.

## 5. Automatización

El comando base `php micro-his/rbac/tests/run.php` es reproducible sin dependencias. No existe workflow de pruebas en `.github/workflows/` (solo `branch-protection.yml` e `issue-opened.yml`); la ejecución en CI queda como **objetivo por configurar** y la evidencia es la salida local registrada en el PR.

## 6. Criterios de entrada y salida

- **Entrada:** requisitos `RF-RBAC-01..08` y catálogo de permisos versionados; entorno base disponible; datos ficticios cargados.
- **Salida (por gate):** 100 % de la suite base y adiciones en `OK`; 0 falsos permitidos; 0 fugas de tenant; toda denegación con traza; cualquier fallo se registra como defecto (`gestion-defectos.md`) antes de continuar.
