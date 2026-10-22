# Gobernanza y Auditoría — Semana 15: matriz y pruebas finales

## Objetivo

Completar la trazabilidad RF/RNF → caso de prueba → resultado y entregar pruebas unitarias, de caja blanca y de caja negra, con casos positivos y negativos y con pruebas de permisos, aislamiento, validación de filtros y fallos de persistencia. Todo con evidencia de ejecución real.

## Entregables

| Entregable | Ubicación |
|---|---|
| Matriz RF/RNF → caso → resultado | [matriz-pruebas.md §1](matriz-pruebas.md) |
| 22 unitarias, 15 de caja blanca y 19 de caja negra | `micro-his/gobernanza-auditoria/tests/unit.php`, `whitebox.php` y `blackbox.php` |
| Contrato, integración, Laravel y arquitectura | `contract.php`, `rbac_integration.php`, `hash_compat.php`, `laravel_glue.php` y `architecture.php` |
| Evidencia de ejecución | [../evidencias/s15-ejecucion-suites-rbac-develop.txt](../evidencias/s15-ejecucion-suites-rbac-develop.txt) y [../evidencias/s15-ejecucion-suites-rbac-local.txt](../evidencias/s15-ejecucion-suites-rbac-local.txt) |
| Resumen de cobertura y limitaciones | [§4–6](matriz-pruebas.md#4-resumen-de-cobertura) |

## Herramientas

Se reutiliza el estilo de pruebas de `micro-his/rbac/tests` y `micro-his/ocupacion-hospitalaria/tests`: PHP vanilla con un arnés `check()`/`summary()` y código de salida distinto de cero si algo falla. `laravel_glue.php` usa el `vendor/` de Composer del proyecto. No se agregaron dependencias.

## Instrucciones

```bash
php micro-his/gobernanza-auditoria/tests/run.php
```

Ejecución completa con las dependencias de develop y de Tenants, exportadas sin cambiar de rama:

```bash
git archive origin/develop micro-his/rbac | tar -x -C /tmp/rbac-develop
```

```bash
mkdir -p /tmp/ten
```

```bash
git show origin/feature/asii-25-semana-16-tenants-auditoria:app/Modules/Tenants/Domain/Audit/AuditLogRepositoryInterface.php > /tmp/ten/AuditLogRepositoryInterface.php
```

```bash
git show origin/feature/asii-25-semana-16-tenants-auditoria:app/Modules/Tenants/Infrastructure/Audit/AuditLogRepository.php > /tmp/ten/AuditLogRepository.php
```

```bash
RBAC_DIR=/tmp/rbac-develop/micro-his/rbac TENANTS_AUDIT_DIR=/tmp/ten php micro-his/gobernanza-auditoria/tests/run.php
```

## Resultados reales (2026-10-20)

| Configuración | OK | FALLO | Omitidas |
|---|---|---|---|
| Con el RBAC de develop y Tenants | 112 | 0 | 0 |
| Solo esta rama | 106 | 0 | 6 (con el motivo impreso) |

## Relación con semanas anteriores

Cierra la matriz de las Semanas 1, 2 y 12. Incluye las pruebas de regresión de los hallazgos de la Semana 13.

## Dependencias pendientes

Ver [matriz-pruebas.md §5](matriz-pruebas.md#5-pruebas-que-no-se-pudieron-ejecutar-y-cómo-ejecutarlas): PostgreSQL, Laravel de punta a punta y rendimiento.

## Limitaciones conocidas

No se midió la cobertura de líneas (no hay PCOV ni Xdebug). No se probó con PostgreSQL ni con usuarios reales.
