# Semana 15 — Matriz RF/RNF → casos de prueba → resultado

**Ejecución de referencia:** [s15-ejecucion-suites-rbac-develop.txt](../evidencias/s15-ejecucion-suites-rbac-develop.txt) (2026-10-20, PHP 8.2.12, RBAC de `origin/develop` @ 7c36ab4 y Tenants @ 978ed23): **112 ejecuciones, 112 OK, 0 FALLO**.
**Ejecución local, sin dependencias exportadas:** [s15-ejecucion-suites-rbac-local.txt](../evidencias/s15-ejecucion-suites-rbac-local.txt): 106 OK, 0 FALLO y 6 casos OMITIDOS (TC-I-04…06 y TC-R-01…03), con el motivo impreso.

**Tipos:** U = unitaria; CB = caja blanca; CN = caja negra; C = contrato/sustitución; I = integración; A = arquitectura; M = manual. Los casos de `contract.php` y `blackbox.php` se ejecutan con **ambos** adaptadores (InMemory y PDO): por eso hay 112 ejecuciones de 86 casos distintos.

## 1. Matriz

| Requisito | Casos (tipo) | Positivo / negativo | Resultado |
|---|---|---|---|
| RF-GOB-01 Registrar evento | TC-U-01 (U+), TC-U-02/03/05 (U−), TC-U-19 (U+), TC-C-01…11 (C) | + y − | Aprobado |
| RF-GOB-02 Ocurrido frente a intento | TC-U-04 (U−), TC-U-07 (U), TC-N-04 (CN), TC-W-13 (CB) | + y − | Aprobado |
| RF-GOB-03 Política de fallo | TC-U-17 (U−), TC-U-18 (U−), TC-I-03 (I−), TC-U-21 (U−) | − | Aprobado |
| RF-GOB-04 Filtros | TC-C-03…08, TC-C-11 (C), TC-N-01 (CN+) | + y − | Aprobado |
| RF-GOB-05 Paginación | TC-C-09 (C), TC-N-13 (CN), TC-U-13 (U−) | + y − | Aprobado |
| RF-GOB-06 Validación de filtros | TC-U-10…16, TC-U-22 (U), TC-N-12 (CN−) | + y − | Aprobado |
| RF-GOB-07 Autorización RBAC | TC-N-10 (CN−), TC-N-11 (CN−), TC-N-18 (CN−), TC-I-04 (I+), TC-I-05 (I−), TC-I-06 (I−) | + y − | Aprobado (permiso real pendiente) |
| RF-GOB-08 Auditar consultas | TC-N-14 (CN+), TC-N-17 (CN−) | + y − | Aprobado |
| RF-GOB-09 Denegaciones de RBAC | TC-I-01/02 (I+), TC-I-03 (I−), TC-I-07 (I−) | + y − | Aprobado (falta el enlace en RBAC) |
| RF-GOB-10 Detalle sin datos internos | TC-N-03 (CN), TC-W-14 (CB), TC-W-15 (CB) | − | Aprobado |
| RNF-GOB-01 Aislamiento | TC-C-01 (C), TC-N-02, TC-N-07, TC-N-08, TC-N-09 (CN), TC-L-03 (I), TC-I-06 (I) | + y − | Aprobado |
| RNF-GOB-02 Identidad del servidor | TC-N-05 (CN−), TC-N-19 (CN−), TC-L-02 (I−) | − | Aprobado |
| RNF-GOB-03 Sin datos sensibles | TC-U-06, TC-U-08, TC-U-09, TC-U-20 (U), TC-C-10 (C), TC-W-14 (CB) | − | Aprobado |
| RNF-GOB-04 Consultas parametrizadas | TC-W-10, TC-W-11 (CB), TC-U-16 (U−), TC-C-04 (C) | − | Aprobado |
| RNF-GOB-05 Inmutabilidad y evidencia | TC-W-01…04 (CB), TC-R-01…03 (I) | + y − | Aprobado |
| RNF-GOB-06 Errores seguros | TC-N-16 (CN−), TC-W-06…09 (CB−) | − | Aprobado |
| RNF-GOB-07 Correlación | TC-N-01 (CN+), TC-N-15 (CN−) | + y − | Aprobado |
| RNF-GOB-08 Mantenibilidad | TC-A-01…04 (A), TC-C-* (C), TC-L-05 (I) | + | Aprobado |
| RNF-GOB-09 Rendimiento | — | — | **No ejecutado:** sin PostgreSQL ni datos de volumen (M-07) |
| RNF-GOB-10 Accesibilidad | Revisión manual de la Semana 9 (M) | + y − | Revisión manual: 7 hallazgos, 3 corregidos. No es una prueba automática. |

## 2. Mínimos pedidos

| Exigencia | Casos | Cantidad |
|---|---|---|
| ≥ 3 pruebas unitarias | TC-U-01…22 | 22 |
| ≥ 2 pruebas de caja blanca | TC-W-01…15 (cada una indica la rama de código que recorre) | 15 |
| ≥ 4 pruebas de caja negra | TC-N-01…19 (solo la interfaz HTTP pública) | 19 (34 ejecuciones) |
| Casos positivos y negativos | Ver la columna «Positivo / negativo» | Ambos |
| Permisos | TC-N-10, TC-N-11, TC-N-18, TC-I-04…06 | 6 |
| Aislamiento por tenant | TC-C-01, TC-N-02, TC-N-07…09, TC-L-03, TC-I-06 | 7 |
| Validación de filtros | TC-U-10…16, TC-U-22, TC-N-12 | 9 |
| Fallos de persistencia | TC-U-17/18, TC-W-06…08, TC-N-16/17, TC-I-03 | 8 |

## 3. Detalle de los casos de caja negra (entrada → salida esperada)

| Caso | Entrada | Salida esperada | Obtenida |
|---|---|---|---|
| TC-N-01 | admin@H1, `modulo=rbac&desde=2026-10-15&hasta=2026-10-15` | 200, `meta={page:1, per_page:25, total:2, total_pages:1}`, `trace_id=req-prueba-01`, `no-store` | Igual |
| TC-N-07 | admin@H1 con `X-Tenant-ID: H2` | 403 `TENANT_MISMATCH` | Igual |
| TC-N-10 | med@H1 (sin `auditoria.ver`) | 403 `{code: RBAC_DENIED, trace_id: trace-denegado}` | Igual |
| TC-N-11 | med@H1 con `desde=basura` | 403 (no 422) | Igual |
| TC-N-12 | `desde=2026-13-01&por_pagina=500&foo=1` | 422 con `errors={desde: fecha_invalida, foo: parametro_no_permitido, por_pagina: fuera_de_rango}` | Igual |
| TC-N-16 | Almacén caído | 503 `{code: AUDIT_UNAVAILABLE, trace_id}` sin detalle | Igual |
| TC-N-17 | Lectura correcta, pero falla el registro de la consulta | 503, sin `data` | Igual |
| TC-N-18 | RBAC `resolucion_no_disponible` | 503 `RBAC_UNAVAILABLE` | Igual |

## 4. Resumen de cobertura

- **Requisitos:** 18 de 20 tienen al menos un caso automático aprobado (90 %); de los 15 requisitos M, 15 (100 %).
- **Cobertura de código:** **no se midió**. El entorno no tiene Xdebug ni PCOV, así que no se reporta ningún porcentaje.
- **Adaptadores:** el contrato completo se ejecuta con InMemory y con PDO/SQLite.

## 5. Pruebas que no se pudieron ejecutar y cómo ejecutarlas

| Prueba | Motivo | Comando cuando la dependencia exista |
|---|---|---|
| TC-I-04…06 con la copia de esta rama | `micro-his/rbac` de esta rama no tiene `Application/Authorize.php`. Sí se ejecutaron con la copia de develop. | `RBAC_DIR=<develop>/micro-his/rbac php micro-his/gobernanza-auditoria/tests/rbac_integration.php` |
| TC-R-01…03 sin exportar | `AuditLogRepository` de Tenants solo existe en la rama `semana-16` (se ejecutaron exportándolo). | `TENANTS_AUDIT_DIR=<dir> php micro-his/gobernanza-auditoria/tests/hash_compat.php` |
| `PdoAuditLog` contra **PostgreSQL** | No hay PostgreSQL en el entorno y `audit_log` no está en develop. | Levantar las bases de `phpunit.xml` (55431/55433), ejecutar `composer migrate:central` con `audit_log` fusionado y repetir `contract.php` creando el PDO con `new PDO('pgsql:host=127.0.0.1;port=55431;dbname=his_central_test', 'postgres', 'postgres')` en lugar de `sqlitePdo()`. |
| `GET /api/v1/audit-events` de punta a punta en Laravel | Faltan `App\Models\Tenant`/`User` (H-GOB-07), `RbacServiceProvider` en esta rama, el registro del proveedor y el permiso. | `php artisan route:list --path=api/v1/audit-events` y `php artisan test --filter=AuditEvents` (prueba de Feature por crear en `tests/Feature/GobernanzaAuditoria/`). |
| RNF-GOB-09 Rendimiento | Sin datos de volumen. | `EXPLAIN (ANALYZE, BUFFERS)` de las consultas de la propuesta 001 con 1 M de filas. |

## 6. Limitaciones finales

1. La integración con Laravel se probó en aislamiento (controlador y contenedor), sin los middleware reales en ejecución.
2. El esquema SQLite reproduce las columnas y el trigger de `audit_log`, pero no sus tipos exactos.
3. La accesibilidad se evaluó solo por inspección.
4. La cadena SHA-256 hace evidente una alteración, pero no constituye inmutabilidad criptográfica.
