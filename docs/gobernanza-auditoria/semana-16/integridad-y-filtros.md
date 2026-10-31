# Semana 16 — Verificación de integridad y catálogo de filtros (parte 3)

Completa el proceso del módulo, «registro inmutable y consulta filtrada de movimientos por usuario, módulo y fechas»:

- **Inmutable y verificable.** La base ya rechaza `UPDATE` y `DELETE` sobre `audit_log` (trigger append-only), y cada
  fila guarda `previous_hash` y `event_hash`, pero nada recalculaba la cadena. Ahora se puede comprobar desde la API.
- **Filtrar por usuario sin conocer su UUID.** El filtro `usuario` espera el identificador CENTRAL; el catálogo da el
  nombre y el correo del personal del hospital para que la pantalla (parte 4) ofrezca una lista.

## Contrato

| Método | Ruta | Permiso | Respuesta |
|---|---|---|---|
| GET | `/api/v1/audit-events/integrity` | `auditoria.ver` | `valid`, `checked`, `total`, `broken` (`position`, `event_id`, `cause`) |
| GET | `/api/v1/audit-events/catalog` | `auditoria.ver` | `actors` (`user_id`, `display_name`, `email`) y `modules` |

`cause`: `hash_no_coincide` (fila alterada), `eslabon_faltante` (fila borrada o fuera de orden) o `bifurcacion` (dos
filas con el mismo `previous_hash`). Errores: 403 `RBAC_DENIED`, 503 `AUDIT_UNAVAILABLE`. Ambas respuestas llevan
`Cache-Control: no-store, private`.

## Reglas

- `AuditChainVerifier` sigue `previous_hash` desde el génesis, recalcula cada hash con `AuditChainHasher` (el formato
  común de los dos escritores, Tenants y Gobernanza) y se detiene en la cabeza de `audit_chain_state` leída al
  empezar: lo que se agregue durante la verificación no la invalida.
- La cadena es única para todo CENTRAL. Si el eslabón roto es de otro hospital, solo se informa la posición; el
  `event_id` se revela únicamente cuando el evento pertenece al hospital de la sesión.
- Cada verificación queda en la bitácora: `gobernanza.integridad_verificada` (Security, motivo `chain_valid` o
  `chain_broken`, con `checked` y `total`).
- `AuditFilterCatalog` lista el personal con membresía en el hospital de la sesión y los módulos que ya tienen
  eventos ahí; no expone usuarios ni módulos de otros hospitales.

## Validación

- `GovernanceIntegrityTest` (4 pruebas): cadena escrita por ambos escritores válida y verificación auditada;
  alteración de una fila de otro hospital detectada sin revelar su `event_id` y de una propia con su `event_id`;
  catálogo aislado por hospital; admin_hospital sin `auditoria.ver` → 403.
- `php vendor/bin/phpunit tests/Feature/Rbac` → `OK (44 tests, 4163 assertions)`; suites del micro-HIS → aprobadas.
- PostgreSQL local con datos ficticios: `integrity` → `valid: true`, 107 de 107 eventos.
