# Métricas de calidad del módulo RBAC

Las métricas son **objetivos** hasta que una ejecución las registre. La columna «Estado» distingue `Objetivo` (meta a alcanzar) de `Observado` (resultado medido, con fuente y fecha). Ninguna cifra de cobertura, campaña o despliegue se presenta como ejecutada sin evidencia.

## Convenciones

- **Permiso correcto:** coincidencia entre la decisión del motor y la matriz rol×permiso de `docs/rbac/semana-05/permisos.md` (PR #134, pendiente de merge).
- **Falso permitido:** autorizar una operación que la matriz deniega (riesgo crítico).
- **Falso denegado:** denegar una operación que la matriz permite (defecto funcional).
- Escala de medición: cada métrica se recalcula sobre la misma suite de pruebas de regresión (ver `estrategia-pruebas-regresion.md`).

## Tabla de métricas

| ID | Dimensión | Métrica | Fórmula | Objetivo | Cómo se obtiene | Estado |
|---|---|---|---|---|---|---|
| Q-PERM-01 | Permisos | Cobertura de protección de rutas | rutas con `permission:modulo.accion` ÷ rutas protegidas × 100 | 100 % | `php artisan route:list --path=api` y revisión de `routes/api.php` | Objetivo |
| Q-PERM-02 | Permisos | Acierto de la matriz rol×permiso | decisiones conformes ÷ casos de autorización × 100 | 100 % | Suite derivada de `docs/rbac/semana-05/permisos.md` (PR #134, pendiente de merge) sobre `Authorizer` | Parcial observado |
| Q-PERM-03 | Permisos | Falsos permitidos | autorizaciones indebidas ÷ casos denegados | 0 | Casos negativos de la matriz (p. ej. `enfermeria` sin `prescripciones.crear`) | Objetivo |
| Q-AUTH-01 | Autenticación | Rechazo sin token | respuestas 401 ÷ solicitudes sin token × 100 | 100 % | `EnsurePermission` cuando `auth('api')->user() === null` | Objetivo |
| Q-AUTH-02 | Autenticación | Rechazo de membresía no vigente | respuestas 403 ÷ solicitudes con membresía inactiva/vencida × 100 | 100 % | Pendiente: el micro-HIS aún no evalúa vigencia (`valid_from`/`valid_to`) | Objetivo |
| Q-TEN-01 | Tenant | Aislamiento por hospital | operaciones que solo leen `X-Tenant-ID` ÷ operaciones evaluadas × 100 | 100 % | `PdoRbacStore::rolesIn()` filtra por `hospital_id`; prueba con dos hospitales | Objetivo |
| Q-TEN-02 | Tenant | Cobertura multi-tenant | reglas con caso de dos hospitales ÷ reglas de autorización | ≥ 1 caso por regla | Suite con usuarios espejo en H1/H2 | Objetivo |
| Q-TRZ-01 | Trazabilidad | Denegación con traza recuperable | denegaciones con `trace_id` en `audit_log` ÷ denegaciones × 100 | 100 % | `micro-his/rbac/tests/run.php:39` y consulta a `audit_log` | Observado en la suite |
| Q-TRZ-02 | Trazabilidad | Unicidad de `trace_id` | colisiones ÷ trazas generadas | 0 | `random_bytes(6)` (48 bits) en `Authorizer.php:36`; prueba de lote | Objetivo |
| Q-TRZ-03 | Trazabilidad | Cambio de rol con evento | asignaciones con evento `rol_asignado` ÷ asignaciones × 100 | 100 % | `PdoRbacStore::assignWithAudit()` (transacción membresía+auditoría) | Observado en la suite |
| Q-PRS-01 | Persistencia | Rollback sin estado parcial | fallos de persistencia sin filas parciales ÷ fallos inyectados × 100 | 100 % | `run.php:48-50` (se elimina `audit_log` y se verifica que no quede membresía) | Observado en la suite |
| Q-PRS-02 | Persistencia | Falla segura de la bitácora | denegaciones que persisten con bitácora caída ÷ fallos × 100 | 100 % | `run.php:51` y `Authorizer.php:39-41` | Observado en la suite |
| Q-CVG-01 | Pruebas | Cobertura de líneas del motor | líneas ejecutadas ÷ líneas instrumentadas × 100 | ≥ 80 % (a confirmar) | `php -d xdebug.mode=coverage micro-his/rbac/tests/run.php` o PCOV | Objetivo, no medido |
| Q-REG-01 | Regresión | Suite de regresión en verde | comprobaciones `OK` ÷ comprobaciones totales × 100 | 100 % | `php micro-his/rbac/tests/run.php` | **Observado: 9/9 (2026-10-18, `255472f`)** |
| Q-DEF-01 | Defectos | Densidad de defectos abiertos | defectos abiertos ÷ dimensión | Tendencia descendente entre gates | Registro de defectos (`gestion-defectos.md`) | Objetivo |
| Q-DEF-02 | Defectos | Reincidencia | defectos críticos/altos reabiertos ÷ cerrados | 0 | Verificación de corrección y nueva ejecución de regresión | Objetivo |

## Interpretación

- `Q-PERM-02`, `Q-TRZ-01`, `Q-TRZ-03`, `Q-PRS-01`, `Q-PRS-02` y `Q-REG-01` ya tienen cobertura parcial en la suite existente; el plan exige **ampliarla** por dimensión antes del gate G2.
- Las métricas de cobertura (`Q-CVG-01`) y de integración con PostgreSQL federado quedan explícitamente **pendientes de medición**: no se declaran valores.
- Un falso permitido (`Q-PERM-03`) o una fuga de tenant (`Q-TEN-01`) detienen el *gate* correspondiente y se tratan como defecto crítico.
