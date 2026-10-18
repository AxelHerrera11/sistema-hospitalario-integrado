# Matriz de riesgos del módulo RBAC

Cubre las cinco dimensiones exigidas: **permisos, autenticación, tenant, trazabilidad y fallos de persistencia**. Los riesgos provienen de los requisitos (`docs/rbac/semana-02/requerimientos.md`), del catálogo de permisos (`docs/rbac/semana-05/permisos.md`, PR #134, pendiente de merge) y del código del motor (`micro-his/rbac/`).

## Escalas

- **Probabilidad:** Baja (<25 %), Media (25–60 %), Alta (>60 %).
- **Impacto:** Bajo (molestia), Medio (defecto funcional), Alto (decisión de seguridad errónea), Crítico (fuga de datos o escalación).
- **Exposición:** combinación probabilidad × impacto; `Crítico` cuando el impacto es Crítico.

## Matriz

| ID | Dimensión | Riesgo | Prob. | Impacto | Exposición | Control / mitigación | Responsable |
|---|---|---|---|---|---|---|---|
| R-01 | Permisos | Escalación vertical: un actor asigna un rol de nivel superior al suyo. | Media | Crítico | Crítico | `AssignerPrivilegeRule::LEVELS` niega con motivo `rol_supera_privilegio` (`AssignerPrivilegeRule.php:11,19`); caso de prueba obligatorio en cada gate. | Mar-03 |
| R-02 | Permisos | Rol como texto libre sin catálogo: valores inválidos o tipográficos. | Media | Alto | Alto | Validación de `role_code` contra el catálogo (`AssignRole.php:22`); seeder de roles pendiente. | Mar-03 |
| R-03 | Permisos | Bypasseo de autorización: el frontend oculta pero el backend no valida. | Media | Crítico | Crítico | La autoridad es `EnsurePermission` (rama Semana 7; PR #136, pendiente de merge); `permissions.js` (PR #136, pendiente de merge) solo mejora navegación. Verificar 403 server-side. | Mar-03 / revisor |
| R-04 | Autenticación | Token de usuario revocado o membresía vencida sigue autorizando. | Media | Alto | Alto | `EnsurePermission` exige `auth('api')->user()`; vigencia `valid_from`/`valid_to` aún no evaluada en el micro-HIS. | Mar-03 |
| R-05 | Tenant | Fuga entre hospitales: se decide con el hospital equivocado o se ignora `X-Tenant-ID`. | Media | Crítico | Crítico | `rolesIn()` filtra por `hospital_id`; caso multi-tenant obligatorio (Q-TEN-01). | Mar-03 |
| R-06 | Trazabilidad | Denegación sin traza o `trace_id` no recuperable en la bitácora. | Baja | Alto | Alto | `Authorizer` genera `trace_id` y `DenialRecorder` lo persiste (`Authorizer.php:36-38`); Q-TRZ-01. | Mar-03 |
| R-07 | Trazabilidad | Colisión de `trace_id` que confunde dos eventos de auditoría. | Baja | Medio | Medio | `random_bytes(6)` (48 bits) reduce la colisión; prueba de lote (Q-TRZ-02). | Mar-03 |
| R-08 | Persistencia | Estado parcial: membresía guardada sin su evento de auditoría. | Media | Alto | Alto | `assignWithAudit()` usa una sola transacción y revierte ante `PDOException` (`PdoRbacStore.php:31-42`); Q-PRS-01. | Mar-03 |
| R-09 | Persistencia | La caída de la bitácora bloquea o permite la operación. | Baja | Alto | Alto | Falla segura: la denegación persiste aunque la bitácora falle (`Authorizer.php:39-41`); Q-PRS-02. | Mar-03 |
| R-10 | Permisos | Caché de permisos (Spatie) desactualizada tras asignar/revocar rol. | Media | Medio | Medio | RNF-RBAC-01: invalidación de caché al cambiar roles; caso de regresión. | Mar-03 |
| R-11 | Persistencia | Consultas concatenadas permiten inyección SQL. | Baja | Crítico | Alto | PDO con sentencias preparadas en todas las consultas (`PdoRbacStore.php:12`). | Mar-03 |
| R-12 | Trazabilidad | Cambio de rol sin traza (asignación directa en base). | Baja | Alto | Alto | Única vía de escritura es `AssignRole`; revisión de código y Q-TRZ-03. | Mar-03 / revisor |

## Tratamiento

- **Evitar (R-01, R-03, R-05, R-11):** se convierten en casos de prueba bloqueantes; un fallo detiene el gate.
- **Mitigar (R-02, R-04, R-06, R-08, R-09, R-10, R-12):** control ya presente o planificado; se verifica por prueba automatizada.
- **Aceptar con seguimiento (R-07):** probabilidad baja; se monitorea con la prueba de lote.

## Riesgos residuales declarados

Permanecen abiertos y fuera del alcance de esta semana: la vigencia real de membresías (R-04), la invalidación efectiva de la caché de Spatie (R-10) y el catálogo de roles con *seeder* (R-02). Su verificación corresponde a los gates G2–G4 de `criterios-aceptacion-quality-gates.md`.
