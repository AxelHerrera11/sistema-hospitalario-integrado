# micro-his/gobernanza-auditoria

Núcleo del módulo Gobernanza y Auditoría. Es PHP 8.2 vanilla, igual que `micro-his/rbac`: tiene autocarga propia en `bootstrap.php` y no depende de Composer ni de ningún framework. La documentación semanal está en [`docs/gobernanza-auditoria`](../../docs/gobernanza-auditoria/README.md).

## Estructura

```
src/
  Domain/        AuditEvent, AuditOutcome, AuditCriticality, AuditFilter, AuditPage,
                 ContextSanitizer, AuditLogger «i», AuditEventReader «i», excepciones
  Application/   RecordAuditEvent, SearchAuditEvents, AuditFilterParser,
                 AccessGate «i», Clock «i», FailureReporter «i», AccessContext, AccessDecision
  Persistence/   InMemoryAuditLog, PdoAuditLog, AuditRowMapper, AuditChainHasher
  Presentation/  AuditHttpHandler, AuditEventPresenter
  Integration/   RbacAccessGate, RbacDenialRecorder, SystemClock, ErrorLogFailureReporter
  GobernanzaModule.php   raíz de composición
database/audit_log.sqlite.sql   esquema de PRUEBA (SQLite en memoria; no es una migración)
public/index.php + prototipo/   servidor de DEMOSTRACIÓN y prototipo navegable
tests/                          unit, contract, whitebox, blackbox, rbac_integration,
                                hash_compat, laravel_glue, architecture y run.php
```

## Uso desde otro módulo

```php
require_once base_path('micro-his/gobernanza-auditoria/bootstrap.php');
$recorder = app(\MicroHis\Gobernanza\Application\RecordAuditEvent::class);   // enlazado por GobernanzaAuditoriaServiceProvider
$recorder->record($userId, $hospitalId, 'usuarios', 'usuario.desactivado',
    \MicroHis\Gobernanza\Domain\AuditOutcome::Success, \MicroHis\Gobernanza\Domain\AuditCriticality::Security,
    'central_user', $targetId, $traceId);   // si lanza AuditStorageFailure, el consumidor aborta su operación
```

## Pruebas

```bash
php micro-his/gobernanza-auditoria/tests/run.php
```

Las variables opcionales `RBAC_DIR` y `TENANTS_AUDIT_DIR` permiten probar contra el RBAC de develop y el repositorio de Tenants (ver `docs/gobernanza-auditoria/semana-15/README.md`).
