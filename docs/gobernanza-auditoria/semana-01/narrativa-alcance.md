# Semana 1 — Narrativa, alcance y análisis del proceso de auditoría

## 1. Narrativa del proceso

En el Sistema Hospitalario Integrado, cada módulo ejecuta operaciones con efecto de seguridad o de gobierno: asignar o revocar un rol, desactivar un usuario, revocar una sesión, firmar una nota SOAP o bloquear una prescripción. Además, RBAC deniega a diario intentos de acceso sin permiso. Hoy esas acciones no quedan en una bitácora común y consultable:

- en `origin/develop`, `RbacServiceProvider` enlaza `LogDenials`, que solo escribe en el log de Laravel;
- `AuditDenials`, que apunta a `audit_log`, existe pero no está activo;
- la tabla `audit_log` solo está en una rama de Tenants que no se ha fusionado.

El módulo **Gobernanza y Auditoría de movimientos** ofrece dos cosas:

1. **Registro:** un contrato único, `AuditLogger`, que todos los módulos usan para registrar *quién* (usuario y tenant) hizo *qué* (`modulo.accion`), *sobre qué* (recurso), *cuándo* (reloj del servidor, UTC) y *con qué resultado*. El contrato distingue lo que **ocurrió** (`success`) de lo que solo se **intentó** (`failure`, `denied`).
2. **Consulta controlada:** un endpoint y una pantalla para que un rol autorizado consulte la bitácora **de su propio hospital**, filtrando por usuario, módulo, acción, rango de fechas (`desde`/`hasta`), resultado e identificador de correlación, con paginación limitada.

## 2. Alcance

| Incluido | Excluido (y quién lo cubre) |
|---|---|
| Contrato `AuditLogger` (escritura) y `AuditEventReader` (lectura). | Autenticación y emisión del JWT (Usuarios/Tenants, ASII-01 y Cindy). |
| Política de fallo: eventos de seguridad obligatorios frente a informativos. | Definición de roles y concesión de permisos (RBAC, María). |
| Adaptadores InMemory y PDO sobre `audit_log`. | Creación y migración de tablas (Javier). Solo se proponen, ver [propuesta SQL 001](../propuestas-sql/001_audit_log_indices_consulta.md). |
| Adaptador de RBAC (`DenialRecorder`) para registrar las denegaciones. | Cambios en archivos de RBAC o Tenants: se documentan como propuestas. |
| `GET /api/v1/audit-events` con filtros, paginación y errores 401/400/403/422/503. | Exportación CSV/PDF (Reportes operativos, Lis). |
| Prototipo navegable con datos ficticios. | Edición o borrado de eventos: está **prohibido** por diseño. |
| Pruebas unitarias, de caja blanca, de caja negra, de contrato e integración. | Despliegue (Semanas 16–17). |

## 3. Actores, sistemas externos y responsabilidades

| Actor / sistema | Tipo | Responsabilidad frente al módulo |
|---|---|---|
| Administrador de gobernanza | Actor principal humano | Consulta la bitácora de su hospital. Según el README es el *super admin*. Requiere el permiso propuesto `auditoria.ver`. |
| Usuario sin permiso (médico, enfermería, recepción…) | Actor humano | Si intenta consultar, se le deniega (403) y la denegación queda registrada. |
| Módulo consumidor (RBAC, Tenants, Notas SOAP, Prescripciones…) | Actor sistema | Llama a `RecordAuditEvent`. Si el evento es `Security` y falla el registro, debe abortar su operación. |
| Autenticación JWT + `TenantMiddleware` | Sistema externo | Resuelve en el servidor el `user_id` (guard `api`) y el `tenant` (atributo de la petición). |
| RBAC (`MicroHis\Rbac\Application\Authorize`) | Sistema externo | Decide `allowed/reason/trace_id` y llama a `DenialRecorder` al denegar. |
| Base CENTRAL (`audit_log`, `audit_chain_state`) | Sistema externo | Almacena los eventos en modo append-only, con trigger y cadena SHA-256. |
| Log de la aplicación | Sistema externo | Recibe los fallos (`FailureReporter`) que no deben exponerse al cliente. |

## 4. Precondiciones y postcondiciones

| Caso | Precondiciones | Postcondición de éxito | Postcondición de fallo |
|---|---|---|---|
| UC-GOB-01 Registrar evento | El consumidor conoce al actor y el tenant **resueltos por el servidor**. | Se agrega una fila a la bitácora con `event_id` y `occurred_at` del servidor; la cadena avanza. | `Security`: no hay fila y el consumidor recibe `AuditStorageFailure`. `Informational`: no hay fila, se reporta el fallo y se devuelve `false`. Evento inválido: se reporta y se rechaza. |
| UC-GOB-02 Registrar denegación | RBAC denegó y generó un `trace_id`. | `rbac.access_denied` con `outcome=denied` y el mismo `trace_id`. | La operación sigue denegada (falla segura) y RBAC responde 503 `registro_no_disponible`. |
| UC-GOB-03 Consultar | JWT válido, tenant resuelto, `X-Tenant-ID` coincidente y permiso concedido. | 200 con una página del tenant actual, y la consulta queda auditada. | 401/400/403/422/503, sin datos de la bitácora. |
| UC-GOB-04 Ver detalle | El evento pertenece a una página ya autorizada. | Se muestran todos los campos públicos, sin hashes ni claves omitidas. | — (no existe consulta por id de otro tenant). |

## 5. Flujos principales y alternativos

**FP-01 Registrar evento:**
1. El consumidor llama a `record()`.
2. Se valida el formato.
3. El servidor asigna id y hora.
4. Se filtra el contexto.
5. `append()` guarda el evento.
6. Se devuelve `true`.

- **FA-01a:** el dato es inválido → se reporta `audit_event_invalid` y se lanza `InvalidAuditEvent`.
- **FA-01b:** el almacén falla y el evento es `Security` → se reporta y se lanza `AuditStorageFailure`.
- **FA-01c:** el almacén falla y el evento es `Informational` → se reporta y se devuelve `false`.

**FP-03 Consultar:**
1. Se valida la identidad.
2. Se valida el tenant.
3. Se autoriza con RBAC.
4. Se validan los filtros.
5. Se lee el tenant.
6. Se audita la consulta.
7. Se responde 200.

- **FA-03a:** sin sesión → 401.
- **FA-03b:** sin `X-Tenant-ID` → 400.
- **FA-03c:** tenant distinto (en la cabecera o en un parámetro) → 403 `TENANT_MISMATCH`.
- **FA-03d:** sin permiso → 403 `RBAC_DENIED`, y RBAC registra la denegación.
- **FA-03e:** RBAC caído → 503 `RBAC_UNAVAILABLE`.
- **FA-03f:** filtros inválidos → 422.
- **FA-03g:** almacén caído al leer o al auditar la consulta → 503 `AUDIT_UNAVAILABLE`.
- **FA-03h:** sin resultados → 200 con `data: []`.

## 6. Excepciones y reglas de negocio

| Id | Regla | Dónde se aplica |
|---|---|---|
| RN-01 | El tenant de la consulta lo fija el servidor. Un tenant enviado por el cliente solo se tolera si es idéntico. | `AuditHttpHandler` |
| RN-02 | Ningún dato se completa por defecto: si falta el actor, el tenant, `modulo.accion` o el resultado, el evento se rechaza. | `AuditEvent::record` |
| RN-03 | Solo `success` cuenta como ocurrido. `failure` y `denied` son intentos. `unknown` solo aparece al leer filas antiguas sin resultado. | `AuditOutcome` |
| RN-04 | Un evento `Security` que no se puede guardar aborta la operación del consumidor. | `RecordAuditEvent` |
| RN-05 | El contexto solo admite escalares. Se eliminan las claves de secretos, tokens o datos clínicos y se listan por nombre en `redacted_keys`. | `ContextSanitizer` |
| RN-06 | `reason` es un código (`^[a-z][a-z0-9_]*$`), nunca texto libre. | `AuditEvent` |
| RN-07 | La bitácora es de solo inserción: no hay operación de edición ni de borrado en ninguna capa. | `AuditLogger`, trigger de la base |
| RN-08 | Se autoriza **antes** de validar los filtros, para no revelar nada a quien no tiene permiso. | `SearchAuditEvents` |
| RN-09 | Cada consulta autorizada se audita. Si no se puede auditar, no se entregan datos. | `SearchAuditEvents` |
| RN-10 | Hay un máximo de 100 resultados por página y 10 000 páginas. | `AuditFilter` |

## 7. Matriz de trazabilidad requisitos ↔ diagramas

Los requisitos se definen en la [Semana 2](../semana-02/requerimientos.md). Los números de la columna de secuencia son los mensajes `autonumber` del diagrama.

| UC | Requisitos | Casos de uso | Actividad | Secuencia |
|---|---|---|---|---|
| UC-GOB-01 | RF-GOB-01, RF-GOB-02, RF-GOB-03, RNF-GOB-03, RNF-GOB-05 | UC01, extendido por UC08 | Partición A completa | 24–26 (y 27–29 si falla) |
| UC-GOB-02 | RF-GOB-09 | UC02 «include» UC01 | A (invocado por RBAC) y B4 "No (permiso)" | 8–11 |
| UC-GOB-03 | RF-GOB-04, RF-GOB-05 | UC03 | B1, B6, B10 | 1, 4, 7, 17–18, 22–23, 30–31 |
| UC-GOB-04 | RF-GOB-10 | UC04 «extend» UC03 | B11–B12 | (en el cliente, después del 31) |
| UC-GOB-05 | RF-GOB-07, RNF-GOB-01, RNF-GOB-02 | UC05 | B2–B4 | 1–13 |
| UC-GOB-06 | RF-GOB-06 | UC06 | B5 | 14–16 |
| UC-GOB-07 | RF-GOB-08 | UC07 «include» UC01 | B8–B9 | 24–29 |
| UC-GOB-08 | RNF-GOB-06 | UC08 «extend» UC01/UC03 | A6–A10, B7, B9 | 19–21, 27–29 |
