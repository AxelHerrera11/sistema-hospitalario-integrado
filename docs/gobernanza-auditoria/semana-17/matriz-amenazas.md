# Matriz de amenazas — Gobernanza y auditoría

Método: STRIDE sobre el registro (`RecordAuditEvent`) y la consulta (`GET /api/v1/audit-events`) de la bitácora.
Activos: integridad y completitud de la bitácora, identidad de quien actuó, datos personales y clínicos que otros
módulos podrían enviar en el contexto. Pruebas `TC-S-*` nuevas de esta semana; el resto ya existía.
Evidencia: [`evidencias/01-pruebas-seguridad.txt`](evidencias/01-pruebas-seguridad.txt).

P × I de 1 a 3 antes del control. Residual: Bajo / Medio / Alto.

| Id | STRIDE | Amenaza | P×I | Control implementado | Prueba | Residual |
|---|---|---|---|---|---|---|
| G-01 | Suplantación | Consultar la bitácora con la identidad de otro | 2×3 | En Laravel la identidad sale del JWT (`JwtAuth`); en el micro-HIS del servidor, no del cliente | `GovernanceHttpTest`; TC-N-05 | Bajo |
| G-02 | Suplantación | Elegir otro hospital por cabecera o por query | 3×3 | El tenant del servidor manda; cabecera o `tenant_id` distintos → 403 | TC-N-07, TC-N-08; «Real token cannot select foreign hospital…» | Bajo |
| G-03 | Manipulación | Modificar o borrar eventos | 2×3 | `audit_log` append-only (trigger) y cadena SHA-256 compartida con Tenants | Smoke de la Semana 16; `hash_compat` TC-R-01…03 | Bajo (PostgreSQL sin ensayar) |
| G-04 | Manipulación | Inyección en filtros | 2×3 | Lista blanca de parámetros, validación y `prepare()` en todo el adaptador | TC-W-10, TC-W-11, TC-S-06 | Bajo |
| G-05 | Repudio | Una operación sensible ocurre sin evento | 2×3 | Si la bitácora no escribe, el consumidor recibe `AuditStorageFailure` y aborta | Contrato C-*; expediente S-08 | Bajo |
| G-06 | Repudio | Consultar la bitácora sin dejar rastro | 2×2 | Cada consulta exitosa registra `gobernanza.auditoria_consultada` | TC-N-14 | Bajo |
| G-07 | Divulgación | Secretos o datos clínicos en el contexto | 2×3 | `ContextSanitizer` elimina claves de secretos y clínicas y valores con forma de token; lo omitido queda en `redacted_keys` | TC-C-10, TC-W-14 | Bajo |
| G-08 | Divulgación | **Datos personales** (correo, DPI, teléfono, dirección, nacimiento) en el contexto | 2×3 | **Nuevo:** claves personales y valores con forma de correo se eliminan al escribir y al leer | TC-S-01…TC-S-04 | Bajo (corregido, H-GOB-17-01) |
| G-09 | Divulgación | Ver eventos de otro hospital | 2×3 | Toda búsqueda se limita al tenant del servidor | TC-N-02 | Bajo |
| G-10 | Divulgación | Aprender la validación sin permiso | 1×2 | RBAC se evalúa antes que los filtros (403 antes que 422) | TC-N-11, TC-S-05 | Bajo |
| G-11 | Divulgación | Respuestas en cachés intermedias | 2×2 | `Cache-Control: no-store, private` en toda respuesta | TC-N-01, TC-S-07 | Bajo |
| G-12 | Denegación de servicio | Consultas enormes | 2×2 | `por_pagina` acotado y validado; sin límite de peticiones por usuario | TC-N-12, TC-N-13 | **Medio** (sin límite por usuario) |
| G-13 | Elevación | Ver la bitácora sin `auditoria.ver` | 2×3 | `RequirePermission:auditoria.ver`; hoy solo lo tiene `superadmin` | TC-N-10; «Medico is denied before filters…» | Bajo |

## Hallazgo corregido esta semana

**H-GOB-17-01 (G-08, Mayor):** `ContextSanitizer` quitaba secretos y datos clínicos, pero dejaba pasar correo, DPI,
teléfono, dirección y fecha de nacimiento, también un correo escrito bajo una clave neutra como `usuario`. Ningún
módulo los envía hoy (búsqueda en `app/` y `micro-his/`), pero nada lo impedía. Corrección: `PERSONAL_KEY` y
`EMAIL_VALUE`; el filtro se aplica al escribir y al leer filas de otros productores. Sin la corrección, 7 de las 10
pruebas de `tests/seguridad.php` fallan.

## Riesgo residual

G-12 queda en **Medio**: falta un limitador por usuario común a la API (decisión del grupo). G-03 depende del
trigger de PostgreSQL, que solo se probó con su equivalente en SQLite.
