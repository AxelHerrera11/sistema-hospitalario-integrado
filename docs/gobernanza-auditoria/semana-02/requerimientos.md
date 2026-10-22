# Semana 2 — Requisitos funcionales y no funcionales

**Prioridad (MoSCoW):** M = debe, S = debería, C = podría. La columna «Prueba» enlaza los casos que verifican cada criterio. Sus identificadores coinciden con las salidas de `micro-his/gobernanza-auditoria/tests/*.php` ([matriz final](../semana-15/matriz-pruebas.md)).

## 1. Requisitos funcionales

| Id | Descripción verificable | Prioridad | Criterio de aceptación | Prueba |
|---|---|---|---|---|
| RF-GOB-01 | Registrar un evento con actor, tenant, `modulo.accion`, resultado y criticidad; y, opcionalmente, recurso (tipo e id), correlación, motivo codificado y contexto. | M | Dado un evento completo, se guarda con `event_id` UUID v4 y `occurred_at` del servidor en UTC. Si falta el actor, el tenant, `modulo.accion` o el resultado, se rechaza con `InvalidAuditEvent`. | TC-U-01…06, TC-U-19, TC-C-* |
| RF-GOB-02 | Distinguir los eventos que ocurrieron de los intentos. | M | `success` → `occurred=true`; `failure`/`denied` → `occurred=false`. `unknown` no se acepta al registrar y solo aparece en filas antiguas sin resultado. | TC-U-04, TC-U-07, TC-N-04, TC-W-13 |
| RF-GOB-03 | Aplicar la política de fallo según la criticidad. | M | Si el almacén falla: con `Security` se lanza `AuditStorageFailure`; con `Informational` se devuelve `false`. En ambos casos el fallo se reporta. | TC-U-17, TC-U-18, TC-I-03 |
| RF-GOB-04 | Consultar la bitácora del tenant actual filtrando por usuario, módulo, acción, `desde`, `hasta`, resultado y correlación, solos o combinados. | M | Cada filtro reduce el resultado al subconjunto exacto. `desde` es inclusivo y `hasta` inclusivo por día; el orden es del más reciente al más antiguo. | TC-C-03…08, TC-C-11, TC-N-01 |
| RF-GOB-05 | Paginar los resultados. | M | Por defecto 25 por página, `por_pagina` entre 1 y 100 y `pagina` entre 1 y 10 000. `meta` devuelve `page`, `per_page`, `total` y `total_pages`. | TC-C-09, TC-N-13, TC-U-13 |
| RF-GOB-06 | Validar los filtros y responder con errores por parámetro. | M | Una fecha imposible, `hasta` anterior a `desde`, un formato inválido, un parámetro desconocido o un valor que no es texto devuelven 422 `AUDIT_FILTER_INVALID` con `errors{param: código}`. | TC-U-10…16, TC-N-12 |
| RF-GOB-07 | Autorizar la consulta con el RBAC real del proyecto (`auditoria.ver`, propuesto). | M | Sin permiso → 403 `RBAC_DENIED` con el `trace_id` de RBAC. RBAC no disponible → 503. La autorización ocurre antes de validar los filtros. | TC-N-10, TC-N-11, TC-N-18, TC-I-04…06 |
| RF-GOB-08 | Auditar cada consulta autorizada. | S | Cada 200 genera `gobernanza.auditoria_consultada` (`Security`) con los filtros usados. Si no se puede registrar, se responde 503 sin datos. | TC-N-14, TC-N-17 |
| RF-GOB-09 | Registrar las denegaciones de RBAC mediante `DenialRecorder`. | M | Cada denegación queda como `rbac.access_denied`, `denied`, recurso `permission/<permiso>` y el mismo `trace_id`. Si falla la bitácora, la denegación se mantiene. | TC-I-01…03, TC-I-07 |
| RF-GOB-10 | Mostrar el detalle de un evento sin datos internos. | S | La respuesta no contiene `event_hash`, `previous_hash` ni las claves eliminadas; solo lista `redacted_keys`. | TC-N-03, TC-W-14 |

## 2. Requisitos no funcionales

| Id | Descripción verificable | Prioridad | Criterio de aceptación | Prueba |
|---|---|---|---|---|
| RNF-GOB-01 | Aislamiento por tenant. | M | La consulta solo devuelve filas cuyo `hospital_id` es el del servidor. Una cabecera `X-Tenant-ID` o un parámetro de tenant distintos → 403 `TENANT_MISMATCH`. Un permiso en H1 no concede acceso en H2. | TC-C-01, TC-N-02, TC-N-07…09, TC-L-03, TC-I-06 |
| RNF-GOB-02 | Identidad resuelta por el servidor. | M | El `user_id` sale del guard `api`; las cabeceras del cliente (p. ej. `X-User-ID`) se ignoran. Sin identidad → 401. | TC-N-05, TC-L-02 |
| RNF-GOB-03 | Sin secretos ni datos clínicos en la bitácora. | M | No se guardan claves que contengan `pass`, `token`, `secret`, `authorization`, `cookie`, `credential`, `otp`, `mfa`, `api_key`, `session` ni términos clínicos, ni valores con forma de JWT o Bearer. El motivo es un código, no texto libre. | TC-U-06, TC-U-08, TC-U-09, TC-C-10, TC-W-14 |
| RNF-GOB-04 | Consultas parametrizadas. | M | La persistencia solo usa `prepare()` y `bindValue()`. Un valor malicioso se trata como literal y `_`/`%` se escapan en `LIKE`. | TC-W-10, TC-W-11, TC-U-16, TC-C-04 |
| RNF-GOB-05 | Inmutabilidad operativa y evidencia de manipulación. | M | No existen métodos de edición ni de borrado. La base rechaza `UPDATE`/`DELETE`. Cada fila enlaza con la anterior por SHA-256 y alterar el detalle rompe la verificación. **No** es inmutabilidad criptográfica: quien administra la base puede deshabilitar el trigger. | TC-W-01…04 |
| RNF-GOB-06 | Manejo seguro de errores. | M | Los fallos de persistencia devuelven 503 y los inesperados 500, siempre con `{code, trace_id}` y sin SQL, rutas ni excepciones. El detalle va a `FailureReporter`. | TC-N-16, TC-W-07, TC-W-08 |
| RNF-GOB-07 | Observabilidad y correlación. | S | Toda respuesta lleva `trace_id` y `X-Request-ID`. Se reutiliza el `X-Request-ID` del cliente si cumple `^[A-Za-z0-9][A-Za-z0-9._:-]{0,63}$`; si no, se genera uno. | TC-N-01, TC-N-15 |
| RNF-GOB-08 | Mantenibilidad y sustitución de adaptadores. | M | Domain no depende de PDO ni de capas externas, y Presentation no contiene SQL. InMemory y PDO pasan el mismo contrato. Ningún archivo supera 400 líneas. | TC-A-01…04, TC-C-* |
| RNF-GOB-09 | Rendimiento de la consulta. | S | Con los índices de la [propuesta 001](../propuestas-sql/001_audit_log_indices_consulta.md), una página de 100 filas de un tenant debe responder en menos de 500 ms (p95) con 1 millón de filas. **Objetivo propuesto, no medido** (requiere PostgreSQL y datos de volumen). | Pendiente (Semana 14) |
| RNF-GOB-10 | Accesibilidad del prototipo (WCAG 2.2 AA aplicable). | S | Etiquetas en todos los campos, foco visible, contraste ≥ 4.5:1 en texto y ≥ 3:1 en componentes, errores anunciados, navegación por teclado y diálogo con Esc. | Revisión manual, [Semana 9](../semana-09/evaluacion-usabilidad-accesibilidad.md) |

## 3. Restricciones de seguridad

1. Nunca se confía en el tenant, en el usuario ni en el permiso enviados por el cliente (RN-01, RNF-GOB-01/02).
2. Toda ruta protegida usa el middleware real `rbac:<permiso>` (`App\Rbac\RequirePermission`), después de `tenant` y `jwt.auth`, en el mismo orden que `RbacServiceProvider`.
3. Las respuestas de la bitácora llevan `Cache-Control: no-store, private`.
4. Los errores públicos son códigos estables y el detalle interno solo va al log.
5. No existe ningún endpoint de edición, borrado ni consulta por id que salte el filtro de tenant.

## 4. Restricciones de rendimiento

1. El tamaño de página máximo es 100 y la página máxima 10 000 (desplazamiento ≤ 1 000 000). La paginación por cursor queda como mejora (hallazgo H-GOB-05).
2. La consulta usa el índice compuesto por `hospital_id` y fecha (propuesta 001).
3. La escritura serializa la cadena de hashes con `SELECT … FOR UPDATE`. El impacto bajo concurrencia lo tiene que validar Javier (propuesta 001 §4.6).

## 5. Reglas de aislamiento por tenant

| Id | Regla |
|---|---|
| AT-01 | El tenant activo es el atributo `tenant` que fija `TenantMiddleware`, nunca un parámetro libre. |
| AT-02 | `X-Tenant-ID` es obligatorio y debe ser idéntico al tenant del servidor (`hash_equals`), igual que en `RequirePermission`. |
| AT-03 | Los parámetros `tenant_id`, `hospital_id`, `tenant` y `hospital` solo se aceptan si coinciden; si no, 403. |
| AT-04 | `AuditEventReader::search()` exige el tenant como argumento separado de los filtros, y todas las consultas SQL incluyen `hospital_id = :tenant`. |
| AT-05 | Los permisos son por membresía y tenant: un rol en H1 no autoriza en H2 (`CentralRoles::rolesIn(user, hospital)`). |
