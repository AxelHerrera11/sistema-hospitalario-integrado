# Gobernanza y Auditoría — Semana 17: seguridad y despliegue final

## Objetivo

Analizar las amenazas del registro y la consulta de la bitácora, probar permisos y protección de datos sensibles,
corregir lo encontrado y ejecutar el despliegue final con su bitácora.

## Entregables

| Entregable | Ubicación |
|---|---|
| Matriz de 13 amenazas STRIDE con control, prueba y riesgo residual | [matriz-amenazas.md](matriz-amenazas.md) |
| Bitácora del despliegue on-premise con rollback ensayado | [bitacora-despliegue.md](bitacora-despliegue.md) |
| Suite nueva de seguridad (10 pruebas) | `micro-his/gobernanza-auditoria/tests/seguridad.php` |
| Evidencia de pruebas | [evidencias/01-pruebas-seguridad.txt](evidencias/01-pruebas-seguridad.txt) |
| Evidencia del despliegue | [evidencias/02-ensayo-despliegue.txt](evidencias/02-ensayo-despliegue.txt) |

## Pruebas de permisos y datos sensibles

| Grupo | Pruebas | Resultado |
|---|---|---|
| Datos personales en el contexto (InMemory y PDO) | TC-S-01…03 | 6/6 |
| Datos personales en filas de otro productor | TC-S-04 | 1/1 |
| Orden de comprobaciones, inyección y caché | TC-S-05…07 | 3/3 |
| Permisos, tenant y aislamiento (existentes) | TC-N-01…15 | 15/15 por adaptador |
| Endpoint Laravel con JWT y RBAC reales | `GovernanceHttpTest` | 5/5 |
| Todas las suites del módulo | `tests/run.php` | 8 suites aprobadas |

## Hallazgo y corrección

**H-GOB-17-01 (Mayor, divulgación):** la bitácora aceptaba datos personales en el contexto (correo, DPI, teléfono,
dirección y fecha de nacimiento) y no los listaba como omitidos. Corrección en `Domain/ContextSanitizer.php`: claves
personales y valores con forma de correo se eliminan y quedan en `redacted_keys`. Sin la corrección fallan 7 de las
10 pruebas nuevas; con ella pasan todas, y las 147 pruebas Laravel de RBAC y Tenants siguen igual (1 fallo previo
de otro módulo).

## Archivos cambiados

`micro-his/gobernanza-auditoria/src/Domain/ContextSanitizer.php`, `tests/seguridad.php` (nuevo), `tests/run.php` y
esta carpeta.

## Limitaciones

No se probó con PostgreSQL ni con usuarios reales; sin límite de peticiones por usuario (G-12).
