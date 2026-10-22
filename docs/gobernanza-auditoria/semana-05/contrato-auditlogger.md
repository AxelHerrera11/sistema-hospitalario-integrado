# Contrato `AuditLogger` (registro de eventos)

**Estado:** implementado en `micro-his/gobernanza-auditoria`. Antes de definirlo se buscaron contratos equivalentes en el repositorio:

| Contrato existente | Dónde | Evaluación |
|---|---|---|
| `MicroHis\Rbac\Domain\DenialRecorder::record(array)` | develop | Solo cubre denegaciones y recibe un arreglo libre. **Se reutiliza:** `RbacDenialRecorder` lo implementa sobre `AuditLogger`. |
| `App\Modules\Tenants\Domain\Audit\AuditLogRepositoryInterface::logRaw(string, array, ?string)` | Rama de Tenants, sin fusionar; referenciado por `app/Rbac/AuditDenials.php` en develop | El payload es libre, no hay política de fallo, no exige resultado y usa `'system'`/`'unscoped'` por defecto cuando faltan datos (el requisito pide no inventar datos). **Se mantiene compatible:** se escribe en la misma tabla con el mismo algoritmo de cadena (TC-R-01…03) y se pueden leer sus filas (TC-W-13/14). |
| `App\Modules\Tenants\Domain\Repositories\AuditLogRepositoryInterface` (esta rama) | `app/Modules/Tenants` | Pertenece al módulo de Tenants y está ligado a sus eventos de dominio. No se modifica. |

Ninguno cubre a la vez los datos mínimos, la distinción entre ocurrido e intento y la política obligatoria o informativa. Por eso se define `AuditLogger` dentro de los archivos propios del módulo.

## 1. Interfaz

```php
namespace MicroHis\Gobernanza\Domain;
interface AuditLogger { /** @throws AuditStorageFailure */ public function append(AuditEvent $event): void; }
```

Los consumidores usan la fachada `Application\RecordAuditEvent::record(...)`, que construye el evento y aplica la política. Solo un adaptador nuevo implementa `AuditLogger`.

## 2. Datos mínimos del evento

| Campo | Obligatorio | Origen | Formato |
|---|---|---|---|
| `event_id` | Sí | Servidor (UUID v4) | `RecordAuditEvent` |
| `occurred_at` | Sí | Reloj del servidor (`Clock`), UTC con microsegundos | Nunca lo envía el cliente. |
| `actor_id` | Sí | Identidad del servidor (guard `api` o `user_id` de RBAC) | `^[A-Za-z0-9][A-Za-z0-9._:@-]{0,127}$` |
| `tenant_id` (`hospital_id`) | Sí | Tenant del servidor | Mismo formato. |
| `module` | Sí | Consumidor | `^[a-z][a-z0-9_]{0,39}$` (`rbac`, `notas_soap`…) |
| `action` | Sí | Consumidor | `^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)*$`, hasta 80 caracteres (`access_denied`, `nota.firmada`) |
| `outcome` | Sí | Consumidor | `success` (ocurrió) · `failure` (intentado y fallido) · `denied` (intentado y denegado) |
| `criticality` | Sí | Consumidor | `security` (obligatorio) · `informational` |
| `resource.type` / `resource.id` | No (`id` exige `type`) | Consumidor | Formato de módulo / de identificador. |
| `correlation_id` | No | `trace_id` de RBAC o `X-Request-ID` | `^[A-Za-z0-9][A-Za-z0-9._:-]{0,63}$` |
| `reason` | No | Consumidor | Código `^[a-z][a-z0-9_]{0,63}$`, nunca texto libre. |
| `context` | No | Consumidor, filtrado | Ver §3. |

Se guarda en `audit_log` así:

- `event = modulo.accion`
- `actor_id`, `hospital_id`
- `trace_id = correlation_id`
- `result = outcome`
- `detail = {context, criticality, reason, redacted_keys, resource, user_id, hospital_id, tenant_id, trace_id}`
- `previous_hash` y `event_hash`

## 3. Contexto permitido

`ContextSanitizer` aplica estas reglas:

- hasta 20 claves `^[a-z][a-z0-9_]{0,39}$`;
- valores escalares, `null` o listas de hasta 20 escalares;
- cadenas de hasta 200 caracteres, sin caracteres de control.

**Se eliminan** las claves que nombran secretos (`pass`, `token`, `secret`, `authorization`, `cookie`, `credential`, `otp`, `mfa`, `private_key`, `api_key`, `session`, `signature`) o datos clínicos (`diagnos`, `nota`, `note`, `soap`, `allerg`, `vital`, `prescri`, `medica`, `clinic`, `sintoma`, `tratamiento`, `patient_name`…). También se eliminan los valores con forma de JWT, `Bearer …`, hexadecimal de 40 o más caracteres o base64 de 60 o más.

Las claves eliminadas se informan **por nombre** en `redacted_keys`, para que la omisión sea visible. El mismo filtro se vuelve a aplicar al leer filas de otros productores.

## 4. Política de errores

| Situación | Security | Informational |
|---|---|---|
| Evento inválido (falta un dato o el formato es incorrecto) | Se reporta `audit_event_invalid` y se lanza `InvalidAuditEvent`. | Igual. |
| Almacén caído o error de escritura | Se reporta `audit_record_failed` y se **lanza** `AuditStorageFailure`: el consumidor debe abortar o revertir. | Se reporta `audit_record_failed` y se devuelve `false`: la operación continúa. |
| Éxito | `true` | `true` |

El fallo de auditoría **nunca** se resuelve en silencio: siempre queda el reporte en `FailureReporter` (log). En los eventos `Security`, el consumidor no puede continuar como si se hubiera registrado.

## 5. Comportamiento ante fallos de persistencia (`PdoAuditLog`)

1. **Sin transacción abierta:** `BEGIN` → `SELECT head_hash … FOR UPDATE` → `INSERT` → `UPDATE audit_chain_state … WHERE head_hash = :previous` → `COMMIT`. Ante cualquier error hace `ROLLBACK` y lanza `AuditStorageFailure('audit_write_failed' | 'audit_chain_missing' | 'audit_chain_conflict')`.
2. **Con la transacción del consumidor abierta:** participa sin hacer commit. Si el consumidor hace `ROLLBACK`, desaparecen el evento y el avance de la cadena (TC-W-05).
3. Los mensajes de excepción son códigos fijos: no incluyen SQL, credenciales ni el detalle del driver (TC-W-07).

## 6. Eventos definidos por el módulo

| Evento | Criticidad | Emisor |
|---|---|---|
| `rbac.access_denied` (denied) | security | `RbacDenialRecorder` (cuando RBAC lo enlace) |
| `gobernanza.auditoria_consultada` (success) | security | `SearchAuditEvents` |

**A validar con el equipo:** la lista de eventos obligatorios (`security`) de cada módulo. Propuesta mínima:

- asignación o revocación de rol;
- desactivación de usuario;
- revocación de sesión;
- firma y corrección de nota;
- bloqueo o excepción de prescripción;
- denegaciones de RBAC;
- consultas a la bitácora.
