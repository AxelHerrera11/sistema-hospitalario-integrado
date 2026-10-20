# Integración de semanas 16 y 17 — 20 de octubre de 2026

## Orden de las ramas

| Entrega | Rama | Base del PR |
|---|---|---|
| S16a: integración y adaptador | `feature/asii-02-semana-16-rbac-mar-03` | `develop` |
| S16b: matriz administrativa | `feature/asii-02-semana-16-rbac-matriz-mar-03` | S16a |
| S16c: contrato y documentación | `feature/asii-02-semana-16-rbac-docs-mar-03` | S16b |
| S17: seguridad | `feature/asii-02-semana-17-rbac-mar-03` | S16c |
| S17: pruebas de integración | `feature/asii-02-semana-17-rbac-pruebas-mar-03` | S17 |

Revisar y fusionar en ese orden. No comparar simultáneamente todas las ramas con
develop: el diff acumulado supera 400 líneas. Antes de fusionar cada entrega,
confirmar que su base anterior ya quedó integrada. No usar force push.

## Separación de módulos y dependencias

Las ramas solo contienen RBAC. `AuditDenials` es su adaptador; no incluye el contrato
ni la implementación interna de Cindy. El provider local de Cindy registra
`DenialRecorder -> AuditDenials` y aporta `AuditLogRepositoryInterface::logRaw()`.
Hasta integrar ese provider, RBAC conserva `LogDenials`; no se afirma persistencia
de auditoría usando solo estas ramas. No se necesita un servicio `AuditLogger`.

El catálogo contiene 26 permisos: los 22 previos y `usuarios.ver`,
`usuarios.desactivar`, `tenants.crear`, `sesiones.revocar_ajenas`.
`superadmin` tiene 25; `admin_hospital`, 8. La excepción de alergia sigue denegada.
Los endpoints, la pertenencia al hospital y la prevención de escalamiento son
responsabilidad del consumidor autenticado; conceder un permiso no implementa su API.

El esquema CENTRAL de Javier requerido por el resolver ya está en develop
`617501619fb308565cbc4a5240928790f91443f0`, revisado mediante fetch el 20 de octubre.
No se agregan migraciones RBAC. La implementación auth de Cindy sigue local y
depende de su publicación separada. No dar por integrada una entrega futura.

## Validación y límites

Antes de distribuir en estas ramas, el contenido final se verificó en worktrees
aislados: RBAC, 22 pruebas/218 aserciones; con Cindy local, 26/241. Los cuatro
scripts de `micro-his/rbac/tests/` pasaron. El resolver usa SQL real con SQLite en
las pruebas nuevas; no acredita migraciones, triggers ni concurrencia PostgreSQL.

La rama adicional de S17 contiene `CentralRolesTest` y `CindyContextTest`.
Ejecutar `php vendor/phpunit/phpunit/phpunit tests/Feature/Rbac` sin Cindy.
Después de integrar su módulo y dependencias, ejecutar también
`php vendor/phpunit/phpunit/phpunit tests/Feature/Rbac tests/Integration/Rbac`.
La suite Integration depende de `TenantAuthTestCase` de Cindy y se ejecuta de forma
explícita. Verifica login JWT real, revocación inmediata, aislamiento por hospital,
headers sin autoridad, persistencia de denegaciones y respuesta 503 ante fallo de auditoría.

Pendientes: PostgreSQL real (daemon Docker no disponible), publicación de Cindy,
CI, E2E de navegador y staging. Estos pendientes no se presentan como aprobados.
Los cambios locales de Cindy que estaban en `s17-rbac` se preservaron en el stash
`respaldo-cindy-s17-antes-integracion-rbac-2026-10-20`, sin mezclarlos en las ramas.
