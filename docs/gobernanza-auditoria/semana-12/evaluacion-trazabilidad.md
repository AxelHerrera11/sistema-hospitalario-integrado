# Semana 12 — Evaluación y trazabilidad

## 1. Resumen funcional

El módulo ofrece:

- un contrato de registro de auditoría (`AuditLogger` y `RecordAuditEvent`), con datos mínimos, saneamiento y política obligatoria o informativa;
- una consulta autorizada (`SearchAuditEvents` y `GET /api/v1/audit-events`) del tenant del servidor, con 7 filtros y paginación limitada;
- adaptadores InMemory y PDO sobre `audit_log`, con cadena SHA-256 compatible con Tenants;
- adaptadores del RBAC real;
- un controlador y un proveedor Laravel;
- un prototipo navegable.

## 2. Recorrido demostrativo

Ver el [guion de la Semana 6 §8](../semana-06/evaluacion-parcial.md#8-guion-de-demostración--5-minutos), actualizado con:

- Paso 5b: como «Médico», además del 403 **ya no se ven los filtros** (H-GOB-10).
- Paso 9: ejecutar `tests/hash_compat.php` con `TENANTS_AUDIT_DIR` para mostrar que el hash coincide con el de Tenants (Semana 7).

## 3. Matriz requisito → componente → prueba

| Requisito | Componente(s) | Pruebas |
|---|---|---|
| RF-GOB-01 | `Domain\AuditEvent::record`, `Application\RecordAuditEvent` | TC-U-01…06, 19 y TC-C-* |
| RF-GOB-02 | `Domain\AuditOutcome`, `Presentation\AuditEventPresenter` (`occurred`) | TC-U-04, 07, TC-N-04 y TC-W-13 |
| RF-GOB-03 | `Application\RecordAuditEvent`, `Domain\AuditCriticality` | TC-U-17, 18, 21 y TC-I-03 |
| RF-GOB-04 | `Domain\AuditFilter`, `Persistence\PdoAuditLog::where`, `InMemoryAuditLog::search` | TC-C-03…08, 11 y TC-N-01 |
| RF-GOB-05 | `Domain\AuditFilter`, `AuditPage` | TC-C-09, TC-N-13 y TC-U-13 |
| RF-GOB-06 | `Application\AuditFilterParser`, `InvalidAuditFilter` | TC-U-10…16, 22 y TC-N-12 |
| RF-GOB-07 | `Application\SearchAuditEvents`, `AccessGate`, `Integration\RbacAccessGate`, `GobernanzaAuditoriaServiceProvider` (`rbac:`) | TC-N-10, 11, 18 y TC-I-04…06 |
| RF-GOB-08 | `Application\SearchAuditEvents` | TC-N-14 y 17 |
| RF-GOB-09 | `Integration\RbacDenialRecorder` | TC-I-01…03 y 07 |
| RF-GOB-10 | `Presentation\AuditEventPresenter`, `AuditEvent::rehydrate` | TC-N-03 y TC-W-14, 15 |
| RNF-GOB-01 | `Presentation\AuditHttpHandler`, `AuditEventReader::search(tenant, …)` | TC-C-01, TC-N-02, 07…09, TC-L-03 y TC-I-06 |
| RNF-GOB-02 | `App\GobernanzaAuditoria\AuditEventController`, `AuditHttpHandler` | TC-N-05, 19 y TC-L-02 |
| RNF-GOB-03 | `Domain\ContextSanitizer` | TC-U-06, 08, 09, 20, TC-C-10 y TC-W-14 |
| RNF-GOB-04 | `Persistence\PdoAuditLog` | TC-W-10, 11, TC-U-16 y TC-C-04 |
| RNF-GOB-05 | `AuditLogger` (sin modificación), `Persistence\AuditChainHasher`, trigger | TC-W-01…04 y TC-R-01…03 |
| RNF-GOB-06 | `AuditHttpHandler`, `PdoAuditLog`, `FailureReporter` | TC-N-16 y TC-W-06…09 |
| RNF-GOB-07 | `AuditHttpHandler::correlation` | TC-N-01 y 15 |
| RNF-GOB-08 | Estructura de capas, `GobernanzaModule` | TC-A-01…04, TC-C-* y TC-L-05 |
| RNF-GOB-09 | Propuesta SQL 001 | Pendiente |
| RNF-GOB-10 | `public/prototipo/*` | Revisión manual (Semana 9) |

## 4. Diagramas actualizados

| Diagrama | Semana | Cambio respecto de la versión inicial |
|---|---|---|
| Casos de uso, actividad y secuencia | 1 | Sin cambios: siguen coincidiendo con el código. |
| Componentes | 7 | Agrega `GobernanzaModule` como raíz de composición y la relación con `logRaw` de Tenants. |
| Flujo UX y mapa de navegación | 8 y 11 | Estados = respuestas reales del contrato. |

## 5. Evidencias de ejecución

Ver [../evidencias/](../evidencias/):

- 2 ejecuciones completas de las suites;
- el ejemplo OCP;
- 5 capturas reales del prototipo.

## 6. Riesgos y limitaciones

| Id | Riesgo | Mitigación |
|---|---|---|
| R-01 | El esquema `audit_log` no está fusionado y podría ubicarse en HOSPITAL. | El adaptador es sustituible (OCP); la propuesta 001 queda para Javier. |
| R-02 | La ruta Laravel no funciona por H-GOB-07. | Externo; está documentado. |
| R-03 | Contención de la cadena (H-GOB-02). | Propuesta de cadena por hospital. |
| R-04 | Permiso no aprobado. | Pedido a RBAC; mientras tanto, falla cerrada (403). |

## 7. Pendientes para la integración (Semana 16)

1. Fusionar `audit_log` y `audit_chain_state`, y aplicar la propuesta 001 (Javier y Tenants).
2. Agregar `auditoria.ver` y enlazar `DenialRecorder` → `RbacDenialRecorder` (María).
3. Registrar `App\GobernanzaAuditoria\GobernanzaAuditoriaServiceProvider` en `bootstrap/providers.php` (equipo).
4. Resolver `App\Models\Tenant`/`User` para `TenantMiddleware` y `JwtAuth` (Usuarios/Tenants).
5. Crear la prueba de Feature de Laravel contra PostgreSQL de prueba y ejecutar `contract.php` con PDO pgsql.
6. Construir la pantalla Vue a partir del prototipo, con el menú visible según el permiso.
7. Que el responsable del módulo (README: #22 `bquiroao`) confirme y asuma esta entrega.
