# Semana 13 — Revisión Técnica Formal (Formal Technical Review - FTR)
## 1. Plan de Revisión Técnica Formal (FTR Plan)
### 1.1 Objetivo de la Revisión
Evaluar la calidad técnica, arquitectura, seguridad y cumplimiento de requisitos del módulo **"Gestión de usuarios, tenants y endurecimiento de autenticación"** antes de su promoción a producción, asegurando:
- Correctitud arquitectónica (Clean Architecture, DDD, Repository Pattern)
- Cumplimiento de RF/RNF y criterios de aceptación
- Seguridad (OWASP ASVS L2, NIST 800-63B AAL2)
- Accesibilidad (WCAG 2.1 AA)
- Performance y escalabilidad
- Mantenibilidad y evolvibilidad

### 1.2 Alcance de la Revisión
| Área                | Componentes a Revisar                                              | Criterios                                           |
|---------------------|--------------------------------------------------------------------|-----------------------------------------------------|
| **Arquitectura**    | Clean Architecture 4 capas, DDD, Repository Pattern                | Separación de responsabilidades, DIP, testabilidad  |
| **Dominio**         | Entities (4), VOs (8), Domain Services (6), Events (14)            | Reglas de negocio, invariantes, consistencia        |
| **Aplicación**      | 5 Use Cases + 1 Orchestrator                                       | Orquestación, coordinación, manejo errores          |
| **Infraestructura** | 13 Repos (3 PDO + 5 Redis + 5 InMemory), Security, Audit, External | Implementación correcta, performance, seguridad     |
| **Presentación**    | Controllers, Request/Response DTOs, Transformers, Middleware       | Separación HTTP/Business, validación, serialización |
| **Seguridad**       | JWT RS256, Refresh Rotation, MFA, Blocklist, RLS, Audit            | OWASP ASVS L2, NIST 800-63B AAL2                    |
| **API**             | OpenAPI 3.1 (25 endpoints)                                         | Contrato completo, versionado, seguridad            |
| **Testing**         | Unit (24), Feature (18), A11y (4), E2E (5)                         | Cobertura ≥80%, axe-core 0 violations               |
| **DevOps**          | CI/CD, Docker, K8s, Observabilidad                                 | Pipeline completo, rollback, observabilidad         |

### 1.3 Criterios de Entrada (Entry Criteria)
| Criterio                         | Estado | Evidencia                                            |
|----------------------------------|--------|------------------------------------------------------|
| Código completo y compilando     | ✅      | `npm run build` exitoso                              |
| Tests unitarios pasando          | ✅      | `npm run test:unit` (24 pass)                        |
| Tests de integración pasando     | ✅      | `npm run test:feature` (18 pass)                     |
| Tests accesibilidad pasando      | ✅      | `npm run test:a11y` (4 violations known, documented) |
| Build de producción exitoso      | ✅      | `npm run build` exitoso                              |
| Documentación completa           | ✅      | `docs/tenants/semana-01` a `semana-12`               |
| Prototipo navegable funcional    | ✅      | `prototype/` + `demo-script.md`                      |
| CI/CD pipeline configurado       | ✅      | `.github/workflows/ci.yml`                           |
| Checklist seguridad revisado     | ✅      | `security-checklist.md`                              |
| Checklist accesibilidad revisado | ✅      | `ux-accessibility-checklist.md`                      |

### 1.4 Criterios de Salida (Exit Criteria)
| Criterio                             | Estado Requerido | Verificación                      |
|--------------------------------------|------------------|-----------------------------------|
| 0 hallazgos Críticos abiertos        | 0                | Revisión de tracker               |
| ≤ 3 hallazgos Altos abiertos         | ≤ 3              | Revisión de tracker               |
| 0 violaciones WCAG 2.1 AA (axe-core) | 0                | `npm run test:a11y`               |
| Cobertura Domain Services ≥ 90%      | ≥ 90%            | `npm run test:unit --coverage`    |
| Cobertura Use Cases ≥ 80%            | ≥ 80%            | `npm run test:feature --coverage` |
| Build producción exitoso             | Pass             | `npm run build`                   |
| Pipeline CI/CD verde                 | Pass             | GitHub Actions green              |
| Documentación actualizada            | ✅                | `docs/tenants/semana-13/`         |
| Plan de acción para hallazgos        | ✅                | `action-plan.md`                  |

## 2. Roles y Responsabilidades
| Rol                              | Responsable             | Responsabilidades                                                   |
|----------------------------------|-------------------------|---------------------------------------------------------------------|
| **Autor (Author)**               | Cindy (Arquitecto Lead) | Presentar artefactos, responder preguntas, implementar correcciones |
| **Moderador (Moderator)**        | Tech Lead Senior        | Facilitar reunión, controlar tiempo, documentar hallazgos, neutral  |
| **Revisor 1 (Security)**         | Security Lead           | Revisar seguridad, JWT, MFA, refresh, blocklist, audit, OWASP       |
| **Revisor 2 (Architecture)**     | Principal Architect     | Revisar arquitectura, Clean Architecture, DDD, Repository Pattern   |
| **Revisor 3 (Quality/Testing)**  | QA Lead                 | Revisar tests, cobertura, testing strategy, CI/CD                   |
| **Revisor 4 (Accessibility/UX)** | UX Lead                 | Revisar UX, WCAG 2.1 AA, usabilidad, mobile                         |
| **Revisor 5 (DevOps/Infra)**     | DevOps Lead             | Revisar CI/CD, Docker, K8s, Observabilidad, Security                |
| **Secretario (Scribe)**          | Junior Developer        | Documentar hallazgos, decisiones, action items                      |

## 3. Agenda de la Sesión de Revisión (3 Horas)
| Tiempo      | Actividad                                                | Responsable          | Duración |
|-------------|----------------------------------------------------------|----------------------|----------|
| 0:00 - 0:10 | Bienvenida, agenda, roles, reglas                        | Moderador            | 10 min   |
| 0:10 - 0:30 | Presentación arquitectura + demo (15 min c/u)            | Autor                | 20 min   |
| 0:30 - 0:50 | Revisión Arquitectura + Clean Architecture               | Revisor Arquitectura | 20 min   |
| 0:50 - 1:10 | Revisión Seguridad + Auth (JWT, MFA, Refresh, Blocklist) | Revisor Seguridad    | 20 min   |
| 1:10 - 1:25 | Revisión Dominio + Use Cases + Repository Pattern        | Revisor Arquitectura | 15 min   |
| 1:25 - 1:40 | Revisión Testing + CI/CD + Cobertura                     | Revisor QA           | 15 min   |
| 1:40 - 1:50 | Revisión Accesibilidad + UX + Mobile                     | Revisor UX/A11y      | 10 min   |
| 1:50 - 2:00 | Revisión DevOps + CI/CD + Observabilidad                 | Revisor DevOps       | 10 min   |
| 2:00 - 2:20 | Revisión Seguridad (OWASP, NIST, Audit)                  | Revisor Seguridad    | 20 min   |
| 2:20 - 2:40 | Discusión hallazgos + Clasificación severidad            | Todos                | 20 min   |
| 2:40 - 2:55 | Plan de acción + Responsables + Fechas                   | Moderador + Autor    | 15 min   |
| 2:55 - 3:00 | Cierre, próximos pasos, firma                            | Moderador            | 5 min    |

## 4. Checklist de Revisión (12-15 Ítems)
| #          | Categoría     | Ítem de Verificación                          | Criterio de Aceptación                                                                    | Estado |
|------------|---------------|-----------------------------------------------|-------------------------------------------------------------------------------------------|--------|
| **ARQ-01** | Arquitectura  | Clean Architecture 4 capas implementada       | Domain 0 deps externos, Use Cases orquestan, Infra implementa interfaces                  | ☐      |
| **ARQ-02** | Arquitectura  | Repository Pattern (5 interfaces + 13 impl)   | Domain define interfaces, Infra implementa, InMemory para tests                           | ☐      |
| **ARQ-03** | Arquitectura  | Controlador Delgado (0 SQL, 0 reglas negocio) | Controllers solo HTTP→DTO→UseCase→Response                                                | ☐      |
| **ARQ-04** | Arquitectura  | Domain Services encapsulan reglas             | PasswordPolicy, MfaPolicy, TokenFactory, RefreshTokenRotator                              | ☐      |
| **ARQ-05** | Arquitectura  | Domain Events + EventPublisher                | 14 eventos, EventPublisher desacoplado, audit async                                       | ☐      |
| **DOM-01** | Dominio       | Value Objects (8) inmutables                  | Email, Password, TenantId, UserId, SessionId, JwtClaims, Role, DeviceFingerprint          | ☐      |
| **DOM-02** | Dominio       | Entities (4) con invariantes                  | User, Tenant, Session, RefreshToken con validaciones                                      | ☐      |
| **DOM-03** | Dominio       | Domain Services (6) encapsulan reglas         | PasswordPolicy, MfaPolicy, TokenFactory, RefreshTokenRotator, DeviceTrust, Argon2idHasher | ☐      |
| **DOM-04** | Dominio       | Domain Events (14) inmutables                 | UserCreated, UserLoggedIn, MfaChallenged, SessionRevoked, RefreshReuseDetected, etc.      | ☐      |
| **APP-01** | Aplicación    | Use Cases (5 + Orchestrator)                  | ValidateCreds, EvaluateMfa, ExecuteMfa, GenerateTokens, HandleAudit, Orchestrator         | ☐      |
| **APP-02** | Aplicación    | Use Cases sin dependencias concretas          | Solo dependen de Domain Services + Repository Interfaces                                  | ☐      |
| **INF-01** | Infra         | PDO Repositories (3)                          | PDOUserRepository, PDOTenantRepository, PDOSessionRepository                              | ☐      |
| **INF-02** | Infra         | Redis Repositories (5)                        | RefreshToken, Session, Blocklist, Lockout, RateLimit                                      | ☐      |
| **INF-02** | Infra         | InMemory Repositories (5)                     | Para tests unitarios rápidos, determinísticos                                             | ☐      |
| **INF-03** | Infra         | Security Services (4)                         | JwtTokenService (RS256), TotpVerifier, WebAuthnVerifier, Argon2idHasher                   | ☐      |
| **INF-04** | Infra         | Audit + Events                                | PdoAuditLogRepository (hash chain), EventPublisher (Kafka)                                | ☐      |
| **INF-05** | Infra         | External Integrations                         | HaveIBeenPwnedClient, EmailNotificationService, SiemForwarder                             | ☐      |
| **SEC-01** | Seguridad     | JWT RS256 + JWKS Rotation (24h)               | Lcobucci JWT, claves rotativas, JWKS endpoint                                             | ☐      |
| **SEC-02** | Seguridad     | Refresh Token Rotation + Reuse Detection      | Rotación cada uso, Reuse Detection → revocación total + alerta CRITICAL                   | ☐      |
| **SEC-03** | Seguridad     | MFA TOTP + WebAuthn                           | TOTP RFC 6238, WebAuthn/FIDO2, policy adaptativa por rol/tenant                           | ☐      |
| **SEC-03** | Seguridad     | Blocklist Access Tokens (Redis)               | JTI en Redis SET + TTL = exp-now, validación O(1) en middleware                           | ☐      |
| **SEC-04** | Seguridad     | Multi-tenant RLS + X-Tenant-ID                | RLS PostgreSQL, TenantMiddleware, CentralUserReadOnlyRepository                           | ☐      |
| **SEC-05** | Seguridad     | Auditoría Inmutable (Hash Chain)              | Append-only, trigger BEFORE UPDATE/DELETE, hash chain SHA-256                             | ☐      |
| **SEC-06** | Seguridad     | Rate Limiting + Lockout                       | Redis sliding window, lockout progresivo (5/10/20), MFA lockout                           | ☐      |
| **SEC-07** | Seguridad     | Rate Limiting + Lockout                       | Redis sliding window, lockout progresivo (5/10/20), MFA lockout                           | ☐      |
| **API-01** | API           | OpenAPI 3.1 completo (25 endpoints)           | Spec completa, schemas, security schemes, ejemplos                                        | ☐      |
| **API-02** | API           | Contrato versionado + versionado semántico    | /v1/, breaking changes → nueva versión                                                    | ☐      |
| **API-03** | API           | Security Schemes (Bearer + TenantHeader)      | BearerAuth (JWT RS256) + TenantHeader (X-Tenant-ID)                                       | ☐      |
| **TST-01** | Testing       | Cobertura Domain Services ≥ 90%               | 24 unit tests, 90%+ coverage                                                              | ☐      |
| **TST-02** | Testing       | Cobertura Use Cases ≥ 80%                     | 18 feature tests, 85% coverage                                                            | ☐      |
| **TST-03** | Testing       | Tests Feature (18 escenarios)                 | Happy Path + Error Path + Edge Cases                                                      | ☐      |
| **TST-04** | Testing       | Tests Accesibilidad (axe-core)                | 0 violaciones WCAG 2.1 AA en CI                                                           | ☐      |
| **TST-05** | Testing       | Tests E2E (Cypress)                           | Happy Path + Critical Error Path                                                          | ☐      |
| **TST-05** | Testing       | Contract Testing (Pact)                       | Consumer-Driven Contracts configurado                                                     | ☐      |
| **DVP-01** | DevOps        | CI/CD Pipeline (GitHub Actions)               | Lint, TypeCheck, Test, Build, A11y, Security, Deploy                                      | ☐      |
| **DVP-02** | DevOps        | Docker + Docker Compose                       | Multi-stage build, non-root, healthchecks                                                 | ☐      |
| **DVP-03** | DevOps        | Kubernetes + Helm                             | K8s manifests, HPA, PodDisruptionBudget, NetworkPolicy                                    | ☐      |
| **DVP-04** | DevOps        | Observabilidad                                | OpenTelemetry, Prometheus, Grafana, Loki, Alertmanager                                    | ☐      |
| **DVP-05** | DevOps        | Security Supply Chain                         | SLSA Level 2, cosign/sigstore, SBOM, Trivy                                                | ☐      |
| **ACC-01** | Accesibilidad | WCAG 2.1 AA (axe-core)                        | 0 violaciones AA en CI (7 known, documented, sprint 2 fix)                                | ☐      |
| **ACC-02** | Accesibilidad | Navegación teclado                            | 100% navegable solo teclado                                                               | ☐      |
| **ACC-03** | Accesibilidad | Screen Readers (NVDA/VoiceOver)               | Testing manual completado                                                                 | ☐      |
| **ACC-03** | Accesibilidad | Screen Readers (NVDA/VoiceOver)               | Testing manual completado                                                                 | ☐      |
| **ACC-04** | Accesibilidad | Contraste 4.5:1                               | axe-core + manual verification                                                            | ☐      |
| **ACC-05** | Accesibilidad | Focus visible + Focus trap                    | 3px ring, focus trap en modales                                                           | ☐      |
| **PER-01** | Performance   | Login p95 < 300ms                             | k6 load test                                                                              | ☐      |
| **PER-02** | Performance   | Token validation p99 < 50ms                   | Synthetic monitoring                                                                      | ☐      |
| **PER-03** | Performance   | Refresh p95 < 100ms                           | k6 load test                                                                              | ☐      |
| **PER-04** | Performance   | Throughput 500-2000 req/s                     | k6 stress test                                                                            | ☐      |

## 5. Formato de Hallazgos (Finding Report Template)
| Campo                   | Descripción                                                                                    |
|-------------------------|------------------------------------------------------------------------------------------------|
| **ID**                  | FTR-YYYY-NNN (ej: FTR-2026-001)                                                                |
| **Título**              | Descripción breve del hallazgo                                                                 |
| **Severidad**           | Crítica / Alta / Media / Baja / Informativa                                                    |
| **Categoría**           | Arquitectura / Seguridad / Performance / Accesibilidad / UX / Testing / DevOps / Documentación |
| **Componente Afectado** | Componente/módulo específico                                                                   |
| **Descripción**         | Detalle técnico del hallazgo                                                                   |
| **Evidencia**           | Logs, capturas, logs de test, links a código                                                   |
| **Impacto**             | Descripción del impacto en usuario/negocio/seguridad                                           |
| **Recomendación**       | Acción sugerida para corregir                                                                  |
| **Prioridad**           | Inmediata / Sprint Actual / Próximo Sprint / Backlog                                           |
| **Responsable**         | Equipo/persona asignada                                                                        |
| **Fecha Límite**        | Fecha compromiso de corrección                                                                 |
| **Estado**              | Abierto / En Progreso / En Revisión / Cerrado / Rechazado                                      |
| **Verificación**        | Cómo se valida la corrección (test, demo, revisión código)                                     |

## 6. Plantilla de Plan de Acción (Action Plan)
| ID Hallazgo  | Descripción Breve             | Severidad | Acción Correctiva                               | Responsable        | Fecha Límite   | Estado  |
|--------------|-------------------------------|-----------|-------------------------------------------------|--------------------|----------------|---------|
| FTR-2026-001 | Contraste countdown MFA 2.8:1 | Crítica   | Cambiar color a #666666 (4.5:1)                 | Frontend           | Sprint 1 Día 1 | Abierto |
| FTR-2026-002 | Force-Revoke sin Deshacer     | Crítica   | Toast "Deshacer 5s" + doble confirmación mobile | Frontend + Backend | Sprint 1 Día 3 | Abierto |
| FTR-2026-003 | Tabla auditoría sin scope=col | Alta      | Añadir scope="col" a todos <th>                 | Frontend           | Sprint 2 Día 1 | Abierto |
| FTR-2026-004 | Panel detalle sin focus       | Alta      | focus() en panel + tabindex="-1"                | Frontend           | Sprint 2 Día 3 | Abierto |
| FTR-2026-005 | Selector Roles sin label      | Alta      | <label for="roles"> + aria-describedby          | Frontend           | Sprint 2 Día 1 | Abierto |
| FTR-2026-006 | Timeout TOTP 30s→60s          | Alta      | TOTP_WINDOW=2 + warning 10s                     | Backend + Frontend | Sprint 2 Día 2 | Abierto |
| FTR-2026-014 | Help links MFA rotos (404)    | Alta      | Corregir ruta /mfa/recovery                     | Frontend           | Sprint 1 Día 1 | Abierto |
| ...          | ...                           | ...       | ...                                             | ...                | ...            | ...     |

## 6. Registro de Decisiones (Decision Log)
| Decisión ID | Tema                                                           | Decisión         | Justificación                                            | Alternativas Consideradas       | Fecha      | Aprobado Por |
|-------------|----------------------------------------------------------------|------------------|----------------------------------------------------------|---------------------------------|------------|--------------|
| DEC-001     | Clean Architecture 4 capas                                     | Aprobada         | SRP, DIP, testabilidad, evolución independiente          | Layered, Hexagonal              | 2026-10-18 | Tech Lead    |
| DEC-002     | SRP aplicado (12+ clases)                                      | Aprobada         | Evita God Classes, facilita testing                      | Monolito UseCase                | 2026-10-18 | Tech Lead    |
| DEC-003     | Repository Pattern (5 interfaces + 13 impl)                    | Aprobada         | DIP, intercambiabilidad PDO/Redis/InMemory               | Active Record, Direct SQL       | 2026-10-18 | Tech Lead    |
| DEC-004     | Controlador Delgado                                            | Aprobada         | Separación HTTP/Business, testabilidad                   | Fat Controller                  | 2026-10-18 | Tech Lead    |
| DEC-005     | Domain Services para políticas                                 | Aprobada         | Encapsula reglas que cambian por negocio                 | Lógica en Use Cases             | 2026-10-18 | Tech Lead    |
| DEC-005     | Domain Events para auditoría                                   | Aprobada         | Desacopla Use Cases de Infrastructure                    | Direct DB writes                | 2026-10-18 | Tech Lead    |
| DEC-006     | Multi-tenant: RLS + X-Tenant-ID + Adapter Read-Only            | Aprobada         | Defensa en profundidad, Single Writer                    | Shared DB sin aislamiento       | 2026-10-18 | Tech Lead    |
| DEC-007     | JWT RS256 + JWKS Rotation + Refresh Rotation + Reuse Detection | Aprobada         | Seguridad, revocación inmediata, detección compromiso    | HS256, Sin rotation             | 2026-10-18 | Tech Lead    |
| DEC-008     | OpenAPI 3.1 + OpenAPI 3.1 + Decision Matrix                    | Aprobada         | Contrato único, versionable, genera clientes             | Sin contrato formal             | 2026-10-18 | Tech Lead    |
| DEC-009     | Evaluación Microservicio                                       | Monolito Modular | Sin triggers medibles, acoplamiento transaccional fuerte | Microservicios completos        | 2026-10-18 | Tech Lead    |
| DEC-009     | Auditoría Inmutable (Hash Chain + Trigger)                     | Aprobada         | Cumplimiento legal (Ley 26.529/HIPAA 7 años)             | Soft delete, Audit table simple | 2026-10-18 | Tech Lead    |
| DEC-010     | Pipeline MVC + Middleware Chain                                | Aprobada         | Separación concerns, extensibilidad, PSR-15              | Monolito middleware             | 2026-10-18 | Tech Lead    |
| DEC-011     | Contratos Micro preparados                                     | Aprobada         | ROI inmediato sin extracción                             | Sin contratos                   | 2026-10-18 | Tech Lead    |
| DEC-012     | Auditoría Inmutable (Hash Chain + Trigger)                     | Aprobada         | Cumplimiento legal, integridad verificable               | Solo application-level          | 2026-10-18 | Tech Lead    |
| DEC-011     | Pipeline MVC + Middleware Chain                                | Aprobada         | Separación concerns, extensibilidad, PSR-15              | Monolito middleware             | 2026-10-18 | Tech Lead    |
| DEC-012     | Contratos Micro preparados (gRPC, Kafka, Avro)                 | Aprobada         | Preparación ROI inmediato sin extracción                 | Sin contratos                   | 2026-10-18 | Tech Lead    |
| DEC-013     | Value Objects tipados + InMemory Repos                         | Aprobada         | Primitive obsession evitado, tests rápidos               | Primitives, Solo PDO            | 2026-10-18 | Tech Lead    |

## 7. Firma y Aprobación
| Rol                      | Nombre                | Firma           | Fecha      | Aprobado    |
|--------------------------|-----------------------|-----------------|------------|-------------|
| **Autor**                | Cindy                 | _______________ | 2026-10-18 | ☐ Sí / ☐ No |
| **Moderador**            | [Tech Lead Senior]    | _______________ | 2026-10-18 | ☐ Sí / ☐ No |
| **Revisor Seguridad**    | [Security Lead]       | _______________ | 2026-10-18 | ☐ Sí / ☐ No |
| **Revisor Arquitectura** | [Principal Architect] | _______________ | 2026-10-18 | ☐ Sí / ☐ No |
| **Revisor QA/Testing**   | [QA Lead]             | _______________ | 2026-10-18 | ☐ Sí / ☐ No |
| **Revisor UX/A11y**      | [UX Lead]             | _______________ | 2026-10-18 | ☐ Sí / ☐ No |
| **Revisor DevOps**       | [DevOps Lead]         | _______________ | 2026-10-18 | ☐ Sí / ☐ No |

## 8. Próximos Pasos Post-Revisión
| Acción                                               | Responsable      | Fecha Límite    | Dependencias                      |
|------------------------------------------------------|------------------|-----------------|-----------------------------------|
| Corregir hallazgos Críticos (FTR-2026-001, 002, 014) | Frontend/Backend | Sprint 1 Día 3  | Ninguna                           |
| Corregir hallazgos Altos (FTR-2026-003 a 006, 014)   | Frontend/Backend | Sprint 2 fin    | FTR-001, 002 resueltos            |
| Corregir hallazgos Medios (FTR-2026-007 a 013, 015)  | Frontend/Design  | Sprint 3 fin    | Hallazgos Críticos/Alta resueltos |
| Corregir hallazgos Bajos (FTR-2026-011, 012, 016)    | Design/Frontend  | Sprint 4 fin    | Hallazgos Medios resueltos        |
| Ejecutar suite completa validación                   | QA + Dev         | Sprint 4 Día 5  | Todas las correcciones aplicadas  |
| Demo a Stakeholders                                  | Autor + PM       | Semana 13 Día 5 | Validación completa               |
| Documentar lecciones aprendidas                      | Autor            | Semana 13 Día 5 | Revisión completada               |
| Preparar Semana 14 (Plan SQA)                        | QA Lead          | Semana 14 Día 1 | Revisión aprobada                 |
