# Semana 13 — Checklist de Revisión Técnica Formal (FTR)
## Instrucciones de Uso
- Marcar ✅ si cumple, ❌ si no cumple, ⚠️ si parcial/observación
- Completar campo "Evidencia" con referencia a código, test, documento
- Asignar severidad: 🔴 Crítica | 🟠 Alta | 🟡 Media | 🟢 Baja | ℹ️ Informativa
- Registrar ID hallazgo si no cumple (formato: FTR-2026-NNN)

## 1. Arquitectura y Clean Architecture (ARQ-01 a ARQ-05)
| ID     | Ítem                       | Criterio                                                                                                                 | ✅/❌/⚠️ | Evidencia | Hallazgo ID |
|--------|----------------------------|--------------------------------------------------------------------------------------------------------------------------|--------|-----------|-------------|
| ARQ-01 | Clean Architecture 4 capas | Domain 0 deps externos, Use Cases orquestan, Infra implementa interfaces                                                 |        |           |             |
| ARQ-02 | Repository Pattern         | 5 interfaces en Domain, 13 implementaciones (3 PDO + 5 Redis + 5 InMemory)                                               |        |           |             |
| ARQ-03 | Controlador Delgado        | 0 SQL, 0 reglas negocio en Controllers                                                                                   |        |           |             |
| ARQ-04 | Domain Services            | 6 services encapsulan reglas (PasswordPolicy, MfaPolicy, TokenFactory, RefreshTokenRotator, DeviceTrust, Argon2idHasher) |        |           |             |
| ARQ-05 | Domain Events              | 14 eventos, EventPublisher desacoplado, auditoría async                                                                  |        |           |             |

**Observaciones:**

## 2. Dominio y Reglas de Negocio (DOM-01 a DOM-04)
| ID     | Ítem                | Criterio                                                                                    | ✅/❌/⚠️ | Evidencia | Hallazgo ID |
|--------|---------------------|---------------------------------------------------------------------------------------------|--------|-----------|-------------|
| DOM-01 | Value Objects (8)   | Email, Password, TenantId, UserId, SessionId, JwtClaims, Role, DeviceFingerprint inmutables |        |           |             |
| DOM-02 | Entities (4)        | User, Tenant, Session, RefreshToken con invariantes                                         |        |           |             |
| DOM-03 | Domain Services (6) | PasswordPolicy, MfaPolicy, TokenFactory, RefreshTokenRotator, DeviceTrust, Argon2idHasher   |        |           |             |
| DOM-04 | Domain Events (14)  | UserCreated, UserLoggedIn, MfaChallenged, SessionRevoked, RefreshReuseDetected, etc.        |        |           |             |

**Observaciones:**

## 3. Capa de Aplicación - Use Cases (APP-01 a APP-02)
| ID     | Ítem                         | Criterio                                                                          | ✅/❌/⚠️ | Evidencia | Hallazgo ID |
|--------|------------------------------|-----------------------------------------------------------------------------------|--------|-----------|-------------|
| APP-01 | Use Cases (5 + Orchestrator) | ValidateCreds, EvaluateMfa, ExecuteMfa, GenerateTokens, HandleAudit, Orchestrator |        |           |             |
| APP-02 | Sin dependencias concretas   | Solo Domain Services + Repository Interfaces                                      |        |           |             |

**Observaciones:**

## 4. Infraestructura - Repositorios (INF-01 a INF-05)
| ID     | Ítem                      | Criterio                                                                | ✅/❌/⚠️ | Evidencia | Hallazgo ID |
|--------|---------------------------|-------------------------------------------------------------------------|--------|-----------|-------------|
| INF-01 | PDO Repositories (3)      | PDOUserRepository, PDOTenantRepository, PDOSessionRepository            |        |           |             |
| INF-02 | Redis Repositories (5)    | RefreshToken, Session, Blocklist, Lockout, RateLimit                    |        |           |             |
| INF-03 | InMemory Repositories (5) | Tests unitarios rápidos, determinísticos                                |        |           |             |
| INF-03 | Shared Adapter            | CentralUserReadOnlyRepository (read-only para otros módulos)            |        |           |             |
| INF-04 | Security Services (4)     | JwtTokenService (RS256), TotpVerifier, WebAuthnVerifier, Argon2idHasher |        |           |             |
| INF-04 | Audit + Events            | PdoAuditLogRepository (hash chain), EventPublisher (Kafka)              |        |           |             |
| INF-05 | External Integrations     | HaveIBeenPwnedClient, EmailNotificationService, SiemForwarder           |        |           |             |

## 5. Seguridad (SEC-01 a SEC-07)
| ID     | Ítem                               | Criterio                                                                | ✅/❌/⚠️ | Evidencia | Hallazgo ID |
|--------|------------------------------------|-------------------------------------------------------------------------|--------|-----------|-------------|
| SEC-01 | JWT RS256 + JWKS Rotation (24h)    | Lcobucci JWT, claves rotativas, JWKS endpoint                           |        |           |             |
| SEC-02 | Refresh Rotation + Reuse Detection | Rotación cada uso, Reuse Detection → revocación total + alerta CRITICAL |        |           |             |
| SEC-03 | MFA TOTP + WebAuthn                | TOTP RFC 6238, WebAuthn/FIDO2, policy adaptativa por rol/tenant         |        |           |             |
| SEC-04 | Blocklist Access Tokens (Redis)    | JTI en Redis SET + TTL = exp-now, validación O(1) en middleware         |        |           |             |
| SEC-04 | Multi-tenant RLS + X-Tenant-ID     | RLS PostgreSQL, TenantMiddleware, CentralUserReadOnlyRepository         |        |           |             |
| SEC-05 | Auditoría Inmutable (Hash Chain)   | Append-only, trigger BEFORE UPDATE/DELETE, hash chain SHA-256           |        |           |             |
| SEC-06 | Rate Limiting + Lockout            | Redis sliding window, lockout progresivo (5/10/20), MFA lockout         |        |           |             |
| SEC-07 | Rate Limiting + Lockout            | Redis sliding window, lockout progresivo (5/10/20), MFA lockout         |        |           |             |

**Observaciones:**

## 6. API y Contratos (API-01 a API-03)
| ID     | Ítem                 | Criterio                                              | ✅/❌/⚠️ | Evidencia | Hallazgo ID |
|--------|----------------------|-------------------------------------------------------|--------|-----------|-------------|
| API-01 | OpenAPI 3.1 completo | 25 endpoints, 40+ schemas, security schemes, ejemplos |        |           |             |
| API-02 | Contrato versionado  | /v1/, breaking changes → nueva versión                |        |           |             |
| API-03 | Security Schemes     | BearerAuth (JWT RS256) + TenantHeader (X-Tenant-ID)   |        |           |             |

## 6. Testing (TST-01 a TST-05)
| ID     | Ítem                            | Criterio                                              | ✅/❌/⚠️ | Evidencia | Hallazgo ID |
|--------|---------------------------------|-------------------------------------------------------|--------|-----------|-------------|
| TST-01 | Cobertura Domain Services ≥ 90% | 24 unit tests, 90%+ coverage                          |        |           |             |
| TST-02 | Cobertura Use Cases ≥ 80%       | 18 feature tests, 85% coverage                        |        |           |             |
| TST-04 | Tests Feature (18 escenarios)   | Happy Path + Error Path + Edge Cases                  |        |           |             |
| TST-05 | Tests Accesibilidad (axe-core)  | 0 violaciones WCAG 2.1 AA en CI (7 known, documented) |        |           |             |
| TST-05 | Tests E2E (Cypress)             | Happy Path + Critical Error Path                      |        |           |             |
| TST-05 | Contract Testing (Pact)         | Consumer-Driven Contracts configurado (pendiente)     |        |           |             |

## 6. DevOps y CI/CD (DVP-01 a DVP-05)
| ID     | Ítem                    | Criterio                                               | ✅/❌/⚠️ | Evidencia | Hallazgo ID |
|--------|-------------------------|--------------------------------------------------------|--------|-----------|-------------|
| DVP-01 | CI/CD Pipeline          | Lint, TypeCheck, Test, Build, A11y, Security, Deploy   |        |           |             |
| DVP-02 | Docker + Docker Compose | Multi-stage build, non-root, healthchecks              |        |           |             |
| DVP-03 | Kubernetes + Helm       | K8s manifests, HPA, PDB, NetworkPolicy                 |        |           |             |
| DVP-04 | Observabilidad          | OpenTelemetry, Prometheus, Grafana, Loki, Alertmanager |        |           |             |
| DVP-05 | Security Supply Chain   | SLSA Level 2, cosign/sigstore, SBOM, Trivy             |        |           |             |

## 7. Accesibilidad WCAG 2.1 AA (ACC-01 a ACC-05)
| ID     | Ítem                       | Criterio                                                   | ✅/❌/⚠️ | Evidencia | Hallazgo ID |
|--------|----------------------------|------------------------------------------------------------|--------|-----------|-------------|
| ACC-01 | WCAG 2.1 AA (axe-core)     | 0 violaciones AA en CI (7 known, documented, sprint 2 fix) |        |           |             |
| ACC-02 | Navegación solo teclado    | 100% navegable solo teclado                                |        |           |             |
| ACC-03 | Screen Readers             | NVDA/VoiceOver testing manual completado                   |        |           |             |
| ACC-04 | Contraste 4.5:1            | axe-core + manual verification (H-001 fixed)               |        |           |             |
| ACC-05 | Focus visible + Focus trap | 3px ring, focus trap en modales                            |        |           |             |

## 8. Performance (PER-01 a PER-04)
| ID     | Ítem                        | Criterio             | ✅/❌/⚠️ | Evidencia | Hallazgo ID |
|--------|-----------------------------|----------------------|--------|-----------|-------------|
| PER-01 | Login p95 < 300ms           | k6 load test         |        |           |             |
| PER-02 | Token validation p99 < 50ms | Synthetic monitoring |        |           |             |
| PER-03 | Refresh p95 < 100ms         | k6 load test         |        |           |             |
| PER-04 | Throughput 500-2000 req/s   | k6 stress test       |        |           |             |

## 9. Resumen de Hallazgos (Para Completar Durante Revisión)
| ID Hallazgo  | Título | Severidad | Componente | Descripción Breve | Estado |
|--------------|--------|-----------|------------|-------------------|--------|
| FTR-2026-001 |        |           |            |                   |        |
| FTR-2026-002 |        |           |            |                   |        |
| FTR-2026-003 |        |           |            |                   |        |
| FTR-2026-004 |        |           |            |                   |        |
| FTR-2026-005 |        |           |            |                   |        |
| FTR-2026-006 |        |           |            |                   |        |
| FTR-2026-007 |        |           |            |                   |        |
| FTR-2026-008 |        |           |            |                   |        |
| FTR-2026-009 |        |           |            |                   |        |
| FTR-2026-010 |        |           |            |                   |        |
| FTR-2026-011 |        |           |            |                   |        |
| FTR-2026-012 |        |           |            |                   |        |
| FTR-2026-013 |        |           |            |                   |        |
| FTR-2026-014 |        |           |            |                   |        |
| FTR-2026-015 |        |           |            |                   |        |
| FTR-2026-016 |        |           |            |                   |        |
**Total Hallazgos: ___ | Críticos: ___ | Altos: ___ | Medios: ___ | Bajos: ___**

## 9. Firmas y Aprobación Final
| Rol                      | Nombre                | Firma           | Fecha      | Aprobado    |
|--------------------------|-----------------------|-----------------|------------|-------------|
| **Autor**                | Cindy                 | _______________ | 2026-10-18 | ☐ Sí / ☐ No |
| **Moderador**            | [Tech Lead Senior]    | _______________ | 2026-10-18 | ☐ Sí / ☐ No |
| **Revisor Seguridad**    | [Security Lead]       | _______________ | 2026-10-18 | ☐ Sí / ☐ No |
| **Revisor Arquitectura** | [Principal Architect] | _______________ | 2026-10-18 | ☐ Sí / ☐ No |
| **Revisor QA/Testing**   | [QA Lead]             | _______________ | 2026-10-18 | ☐ Sí / ☐ No |
| **Revisor UX/A11y**      | [UX Lead]             | _______________ | 2026-10-18 | ☐ Sí / ☐ No |
| **Revisor DevOps**       | [DevOps Lead]         | _______________ | 2026-10-18 | ☐ Sí / ☐ No |

**Resultado Final:** ☐ **APROBADO** | ☐ **APROBADO CON OBSERVACIONES** | ☐ **RECHAZADO**
**Observaciones Generales:**
