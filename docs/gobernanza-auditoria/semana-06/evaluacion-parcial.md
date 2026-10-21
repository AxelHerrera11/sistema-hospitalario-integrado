# Semana 6 — Paquete de evaluación parcial

## 1. Resumen del alcance

| Estado | Funcionalidad |
|---|---|
| **Implementado y probado** | Registro de eventos con datos mínimos, saneamiento y política de fallo; consulta con 7 filtros, paginación y validación; aislamiento por tenant; adaptadores InMemory y PDO (SQLite) con cadena SHA-256 compatible con Tenants; adaptadores de RBAC (`AccessGate` y `DenialRecorder`); contrato HTTP; controlador Laravel aislado; prototipo navegable. |
| **Implementado sin integrar** | `GobernanzaAuditoriaServiceProvider` (ruta `rbac:auditoria.ver`) y `PdoAuditLog` contra PostgreSQL. |
| **Propuesto** | Permiso `auditoria.ver`, [propuesta SQL 001](../propuestas-sql/001_audit_log_indices_consulta.md), enlace de `RbacDenialRecorder` en `RbacServiceProvider`, lista de eventos `security` por módulo y meta de rendimiento RNF-GOB-09. |
| **Bloqueado por dependencias externas** | Ejecución de punta a punta en Laravel (modelos `Tenant`/`User`, PostgreSQL, `audit_log` fusionado, permiso aprobado). Pantalla Vue definitiva. |

## 2. Requisitos cumplidos y pendientes

| Requisito | Estado | Evidencia |
|---|---|---|
| RF-GOB-01…06, 08, 10 | Cumplido en el núcleo. | Suites `unit`, `contract`, `blackbox` y `whitebox` |
| RF-GOB-07 | Cumplido con el `Authorize` real de develop; falta aprobar el permiso. | TC-I-04…06 |
| RF-GOB-09 | Cumplido en el adaptador; falta el enlace en RBAC. | TC-I-01…03, 07 |
| RNF-GOB-01…08 | Cumplido en el núcleo. | Ver la [matriz](../semana-15/matriz-pruebas.md) |
| RNF-GOB-09 | **Pendiente** (sin medición). | — |
| RNF-GOB-10 | Revisión manual (Semana 9). | [Semana 9](../semana-09/evaluacion-usabilidad-accesibilidad.md) |

## 3. Diagramas actualizados

- [Semana 1](../semana-01/README.md): casos de uso, actividad y secuencia.
- [Semana 2](../semana-02/diseno-solid.md): antes y después.
- [Semana 3](../semana-03/arquitectura.md): C4, componentes y modelo de dominio.

Los nombres coinciden con las clases de `src/`.

## 4. Matriz de trazabilidad

La matriz completa RF/RNF → componente → prueba → resultado está en la [Semana 15](../semana-15/matriz-pruebas.md) (la de la Semana 12 se mantiene sincronizada con ella).

## 5. Evidencias reales de pruebas

- [Ejecución completa con el RBAC de develop](../evidencias/s15-ejecucion-suites-rbac-develop.txt)
- [Ejecución con el RBAC de esta rama](../evidencias/s15-ejecucion-suites-rbac-local.txt)
- [Ejemplo OCP](../evidencias/s02-ocp-antes-despues.txt)

## 6. Decisiones arquitectónicas (ADR breves)

| Id | Decisión | Alternativas | Justificación |
|---|---|---|---|
| ADR-GOB-01 | El núcleo va en `micro-his/gobernanza-auditoria` (PHP vanilla) con adaptador Laravel. | Todo dentro de `app/`. | Es la convención de `rbac` y `ocupacion-hospitalaria`; se puede probar sin base de datos ni framework. |
| ADR-GOB-02 | Persistir en `audit_log` (CENTRAL). | `local_access_audits` (HOSPITAL, Anexo A). | Es el único esquema general con migración escrita y RBAC ya lo referencia. El cambio se resuelve con un adaptador (OCP), y la decisión final la toma Javier. |
| ADR-GOB-03 | Reutilizar la cadena SHA-256 de Tenants. | Una cadena propia, o ninguna. | Mantener una sola cadena verificable; la compatibilidad está probada (TC-R-01…03). |
| ADR-GOB-04 | Política obligatoria o informativa según la criticidad. | Fallar siempre, o ignorar siempre. | Cumple el requisito de no resolver en silencio y permite eventos de baja importancia. |
| ADR-GOB-05 | Auditar las consultas a la bitácora con falla cerrada. | No auditarlas. | Quien mira la bitácora también debe quedar registrado. |
| ADR-GOB-06 | No guardar `module` en una columna aparte: se deriva de `event`. | Una columna `module`. | Evita duplicación e inconsistencias, y no exige migración. |

## 7. Limitaciones conocidas

1. No hubo ejecución con PostgreSQL ni con Laravel de punta a punta.
2. La cadena de hashes hace visible una manipulación, pero **no** impide que un administrador de la base deshabilite el trigger o reescriba la cadena completa. No se afirma inmutabilidad criptográfica.
3. La paginación por `OFFSET` se degrada con páginas profundas.
4. `ContextSanitizer` es conservador: puede eliminar claves legítimas que contengan, por ejemplo, `medica`.

## 8. Guion de demostración (≈ 5 minutos)

1. `php micro-his/gobernanza-auditoria/tests/run.php` → todas las suites aprobadas (mostrar el resumen).
2. `php docs/gobernanza-auditoria/semana-02/ejemplo/ocp-antes-despues.php` → el mismo caso de uso con 3 almacenes.
3. Levantar la demostración: `php -S 127.0.0.1:8091 -t micro-his/gobernanza-auditoria/public micro-his/gobernanza-auditoria/public/index.php`, y abrir `http://127.0.0.1:8091/prototipo/`.
4. Como administrador, filtrar `modulo=rbac` y `resultado=denied`, y abrir el detalle (`occurred=false`).
5. Cambiar a «Médico» → 403 con referencia.
6. Simular «Bitácora no disponible» → 503 con el mensaje genérico.
7. Poner «Hasta» antes de «Desde» → 422 con el resumen de errores y el foco en el resumen.
8. Mostrar TC-W-03 (UPDATE/DELETE rechazados) y TC-W-01 (cadena verificable).
