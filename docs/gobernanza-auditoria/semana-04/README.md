# Gobernanza y Auditoría — Semana 4: MVC y persistencia

## Objetivo

Implementar la entrada (controladores delgados), los casos de uso de registro y consulta, las interfaces de repositorio, los adaptadores InMemory y PDO, la validación, el manejo explícito de errores y las pruebas de sustitución de adaptadores.

## Entregables

| Entregable | Ubicación |
|---|---|
| Controladores delgados | `app/GobernanzaAuditoria/AuditEventController.php`, `micro-his/gobernanza-auditoria/src/Presentation/AuditHttpHandler.php` |
| Casos de uso | `src/Application/RecordAuditEvent.php`, `SearchAuditEvents.php` |
| Interfaces de repositorio | `src/Domain/AuditLogger.php`, `AuditEventReader.php` |
| Adaptador InMemory | `src/Persistence/InMemoryAuditLog.php` |
| Adaptador PDO | `src/Persistence/PdoAuditLog.php`, `AuditRowMapper.php`, `AuditChainHasher.php` |
| Validación de entradas | `src/Application/AuditFilterParser.php` |
| Manejo de errores | [capas-persistencia.md §5](capas-persistencia.md#5-manejo-explícito-de-errores) |
| Pruebas de sustitución | `tests/contract.php`, `tests/blackbox.php` |
| Documento de capas | [capas-persistencia.md](capas-persistencia.md) |

## Archivos creados o modificados

Se crearon `src/Persistence/PdoAuditLog.php`, `AuditRowMapper.php`, `AuditChainHasher.php`, `database/audit_log.sqlite.sql` (esquema de prueba), `tests/contract.php`, `tests/whitebox.php`, `app/GobernanzaAuditoria/AuditEventController.php` y `LaravelLogFailureReporter.php`. No se modificó ningún archivo de otro módulo.

## Relación con semanas anteriores

Se amplía el prototipo de la Semana 3 con persistencia real (PDO) sin cambiar los casos de uso, tal como se planteó con OCP en la Semana 2.

## Instrucciones

```bash
php micro-his/gobernanza-auditoria/tests/contract.php
```

```bash
php micro-his/gobernanza-auditoria/tests/whitebox.php
```

## Pruebas ejecutadas y resultados reales

| Suite | Resultado |
|---|---|
| `contract.php` | 22 pruebas, 0 fallos (11 escenarios × 2 adaptadores) |
| `whitebox.php` | 14 pruebas, 0 fallos (la Semana 13 agrega TC-W-15) |
| `blackbox.php` | 34 pruebas, 0 fallos |

La salida está en [../evidencias/s15-ejecucion-suites-rbac-develop.txt](../evidencias/s15-ejecucion-suites-rbac-develop.txt).

## Dependencias pendientes

- Probar `PdoAuditLog` contra **PostgreSQL** real. Requiere `audit_log` fusionado y una base de prueba (en `phpunit.xml`, puertos 55431/55433). No había PostgreSQL disponible en este entorno.
- La [propuesta SQL 001](../propuestas-sql/001_audit_log_indices_consulta.md) de índices debe validarla Javier.

## Limitaciones conocidas

- En SQLite, `occurred_at` es texto. Las filas del módulo usan un formato UTC fijo, así que el orden es correcto, pero filas de otros productores con otro formato podrían ordenarse mal (solo en pruebas; PostgreSQL usa `timestamptz`).
- El constructor de `PdoAuditLog` fija `PDO::ERRMODE_EXCEPTION` en la conexión recibida. Laravel ya trabaja en ese modo, pero el efecto queda documentado (H-GOB-01).
