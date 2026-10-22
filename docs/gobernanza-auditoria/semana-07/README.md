# Gobernanza y Auditoría — Semana 7: componentes y refactorización

## Objetivo

Revisar la modularidad. Esto incluye detectar responsabilidades mezcladas y duplicaciones, aplicar refactorizaciones justificadas dentro del módulo, definir interfaces para las dependencias externas y verificar con pruebas de regresión que el comportamiento se conserva.

## Entregables

| Entregable | Archivo |
|---|---|
| Responsabilidades mezcladas (RM-01…03) | [refactorizacion.md §1](refactorizacion.md) |
| Duplicaciones (DU-01…03) | [§2–3](refactorizacion.md#2-duplicaciones-detectadas) |
| Refactorización y comparación antes/después | [§3](refactorizacion.md#3-refactorizaciones-aplicadas-en-el-módulo) |
| Interfaces para dependencias externas | [§4](refactorizacion.md#4-interfaces-para-dependencias-externas) |
| Diagrama de componentes | `01-componentes.svg`, [fuente](fuentes/01-componentes.mmd) |
| Pruebas de regresión | [§6](refactorizacion.md#6-pruebas-de-regresión-y-conservación-del-comportamiento), `tests/hash_compat.php` y TC-L-05 |

## Archivos creados o modificados

- **Creados:** `micro-his/gobernanza-auditoria/src/GobernanzaModule.php` y `tests/hash_compat.php`.
- **Modificados (propios):** `app/GobernanzaAuditoria/GobernanzaAuditoriaServiceProvider.php` (usa `GobernanzaModule`), `tests/fixtures.php`, `tests/rbac_integration.php`, `tests/laravel_glue.php` (TC-L-05), `tests/run.php` y `tests/architecture.php` (incluye `src/*.php`).
- **Documentación:** `refactorizacion.md`, `fuentes/01-componentes.mmd` y `01-componentes.svg`.

## Relación con semanas anteriores

Actualiza el diagrama de componentes de la Semana 3. Las interfaces de la Semana 3 permiten la refactorización sin tocar los casos de uso.

## Instrucciones

```bash
git show origin/feature/asii-25-semana-16-tenants-auditoria:app/Modules/Tenants/Domain/Audit/AuditLogRepositoryInterface.php > /tmp/ten/AuditLogRepositoryInterface.php
```

```bash
git show origin/feature/asii-25-semana-16-tenants-auditoria:app/Modules/Tenants/Infrastructure/Audit/AuditLogRepository.php > /tmp/ten/AuditLogRepository.php
```

```bash
TENANTS_AUDIT_DIR=/tmp/ten php micro-his/gobernanza-auditoria/tests/hash_compat.php
```

## Pruebas ejecutadas y resultados reales

- **`hash_compat.php`:** 3 pruebas, 0 fallos.
- **Suite completa después de la refactorización:** todas aprobadas (ver [../evidencias/s15-ejecucion-suites-rbac-develop.txt](../evidencias/s15-ejecucion-suites-rbac-develop.txt)).
- **`laravel_glue.php`:** 5 pruebas, 0 fallos (incluye TC-L-05).

## Dependencias pendientes

- **RM-01 y DU-01:** propuestas para Tenants y Javier.
- **RM-02:** propuesta para RBAC (enlazar `RbacDenialRecorder`).

## Limitaciones conocidas

`hash_compat.php` necesita exportar el archivo de la rama de Tenants. Sin `TENANTS_AUDIT_DIR`, se informa como OMITIDA y no cuenta como aprobada.
