# Semana 06 — Matriz de Trazabilidad Completa: RF/RNF → Código → Tests
## 1. Requisitos Funcionales (RF) - Trazabilidad Completa
| RF        | Descripción                                                           | Código Fuente (Clases/Archivos)                                                                                                                           | Tests                                                                                                                                                                                                                                                                                                             | Cobertura                                          | Estado      |
|-----------|-----------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------------------------------------------|-------------|
| **RF-01** | Alta usuario por tenant (validación tenant activo, cupo, email único) | `RegisterUserUseCase.php`<br>`PDOUserRepository.php`<br>`PDOTenantRepository.php`<br>`Tenant.php` (Entity)                                                | `RegisterUserFeatureTest.php`<br>• testSuccessfulUserRegistration<br>• testFailsWhenTenantInactive<br>• testFailsWhenQuotaExceeded<br>• testFailsWhenEmailAlreadyExists<br>• testFailsWhenPasswordPolicyViolated                                                                                                  | Feature: 100%<br>Unit: 90% (UseCase)               | ✅ Completo  |
| **RF-02** | Password Policy (min 12, complejidad, historial 5, HIBP)              | `PasswordPolicy.php` (Domain Service)<br>`Argon2idHasher.php`<br>`HaveIBeenPwnedClient.php`<br>`ValidationResult.php`                                     | `PasswordPolicyTest.php`<br>• testValidPasswordPasses<br>• testTooShortPasswordFails<br>• testMissingUppercaseFails<br>• testMissingLowercaseFails<br>• testMissingNumberFails<br>• testMissingSymbolFails<br>• testCompromisedPasswordFails<br>• testPasswordInHistoryFails<br>• testHashProducesValidArgon2id   | Unit: 100%                                         | ✅ Completo  |
| **RF-03** | Roles por tenant (TENANT_ADMIN, MEDICO, ENFERMERO, PACIENTE, AUDITOR) | `Role.php` (Value Object)<br>`MfaPolicy.php`<br>`User.php` (Entity - roles array)<br>`RegisterUserUseCase.php`                                            | `MfaPolicyTest.php`<br>• testTenantAdminRequiresMfa<br>• testAuditorRequiresMfa<br>• testPlatformAdminRequiresMfa<br>• testMedicoDoesNotRequireMfaByDefault<br>• testEnfermeroDoesNotRequireMfaByDefault<br>• testTenantPolicyMfaRequiredOverrides<br>• testUserMfaRequiredOverrides<br>• testWebAuthnWhenEnabled | Unit: 100%                                         | ✅ Completo  |
| **RF-04** | Autenticación credenciales + tenant validation                        | `AuthenticateUserUseCase.php`<br>`PDOUserRepository.php` (findByEmailAndTenant)<br>`Argon2idHasher.php` (verify)<br>`TenantMiddleware.php`                | `LoginMfaFeatureTest.php`<br>• testSuccessfulLoginWithoutMfa<br>• testSuccessfulLoginWithMfaTotp<br>• testFailsWithInvalidCredentials<br>• testFailsWithWrongPassword<br>• testFailsWithInactiveUser                                                                                                              | Feature: 100%                                      | ✅ Completo  |
| **RF-05** | MFA Adaptativo (TOTP/WebAuthn) - TENANT_ADMIN/AUDITOR siempre         | `MfaPolicy.php`<br>`TotpVerifier.php`<br>`WebAuthnVerifier.php`<br>`AuthenticateUserUseCase.php` (MFA flow)                                               | `MfaPolicyTest.php` (ver RF-03)<br>`LoginMfaFeatureTest.php`<br>• testSuccessfulLoginWithMfaTotp                                                                                                                                                                                                                  | Unit: 90%<br>Feature: 80%                          | ✅ Completo  |
| **RF-06** | JWT RS256 (15min) + Refresh Opaco (7d, rotación)                      | `JwtTokenService.php` (RS256)<br>`TokenFactory.php`<br>`AccessToken.php`<br>`RefreshTokenRotator.php`<br>`JwtClaims.php`                                  | `TokenFactoryTest.php`<br>• testCreateAccessToken<br>• testValidateValidToken<br>• testValidateInvalidTokenThrows<br>`RefreshTokenRotatorTest.php`<br>• testGenerateAndStore<br>• testRotateSuccess<br>• testRotateReuseDetected<br>• testRotateTokenNotFound                                                     | Unit: 95%                                          | ✅ Completo  |
| **RF-07** | Refresh Rotation + Reuse Detection → Revocación total + alerta        | `RefreshTokenRotator.php`<br>`RedisRefreshTokenRepository.php`<br>`RedisBlocklistRepository.php`<br>`RefreshTokenUseCase.php`                             | `RefreshTokenRotatorTest.php`<br>• testRotateReuseDetected<br>`RefreshTokenFeatureTest.php`<br>• testFailsWithReuseDetected                                                                                                                                                                                       | Unit: 100%<br>Feature: 90%                         | ✅ Completo  |
| **RF-08** | Blocklist Access Tokens (Redis O(1))                                  | `RedisBlocklistRepository.php`<br>`JwtAuthMiddleware.php` (blocklist check)<br>`BlocklistService.php` (Interface)                                         | `RefreshTokenFeatureTest.php`<br>• testFailsWithReuseDetected (verifica blocklist)<br>`MultiTenantIsolationFeatureTest.php`                                                                                                                                                                                       | Feature: 85%                                       | ✅ Completo  |
| **RF-09** | Revocación: Logout propio + Force-revoke admin + Evento               | `RevokeSessionUseCase.php`<br>`RedisBlocklistRepository.php`<br>`RedisRefreshTokenRepository.php`<br>`RedisSessionRepository.php`<br>`EventPublisher.php` | `RevokeSessionFeatureTest.php`<br>• testRevokeOwnSession<br>• testForceRevokeAllSessions<br>• testRevokeNonExistentSession                                                                                                                                                                                        | Feature: 100%                                      | ✅ Completo  |
| **RF-10** | Aislamiento Multi-tenant (RLS PostgreSQL + X-Tenant-ID)               | `TenantMiddleware.php`<br>`JwtAuthMiddleware.php` (tenant consistency)<br>`PDOTenantRepository.php`<br>`CentralUserReadOnlyRepository.php`                | `MultiTenantIsolationFeatureTest.php`<br>• testUserFromTenantACannotAccessTenantB<br>• testInactiveTenantBlocksAccess<br>• testUserRolesAreScopedToTenant                                                                                                                                                         | Feature: 100%                                      | ✅ Completo  |
| **RF-11** | Lockout Progresivo (5→15min, 10→1h, 20→24h)                           | `RedisLockoutRepository.php`<br>`AuthenticateUserUseCase.php` (handleFailedLogin)<br>`MfaPolicy.php` (MFA lockout separado)                               | `LoginMfaFeatureTest.php`<br>• testFailsWithWrongPassword (verifica lockout logic)                                                                                                                                                                                                                                | Feature: 70%<br>(Requiere test específico lockout) | ⚠️ Parcial  |
| **RF-12** | Single Session Policy (configurable por tenant)                       | `AuthenticateUserUseCase.php` (handleSingleSessionPolicy)<br>`Tenant.php` (policy single_session)<br>`RedisSessionRepository.php`                         | `LoginMfaFeatureTest.php`<br>• testSuccessfulLoginWithoutMfa (verifica single session)                                                                                                                                                                                                                            | Feature: 60%<br>(Requiere test específico)         | ⚠️ Parcial  |
| **RF-13** | Device Fingerprinting (trust score, alerta dispositivo nuevo)         | `DeviceFingerprint.php` (VO)<br>`DeviceTrustService.php`<br>`Session.php` (Entity)<br>`AuthenticateUserUseCase.php`                                       | `DeviceTrustService` tests (pendientes)                                                                                                                                                                                                                                                                           | Unit: 40%                                          | ⚠️ Parcial  |
| **RF-14** | Auditoría Inmutable (14 eventos, hash chain SHA-256)                  | `PdoAuditLogRepository.php`<br>`InMemoryAuditLogRepository.php`<br>`DomainEvent.php` (base)<br>14 Event classes en `Domain/Events/`                       | `InMemoryAuditLogRepository` tests<br>• verifyHashChain<br>`InMemoryAuditLogRepository.php` (método verifyHashChain)                                                                                                                                                                                              | Unit: 95%                                          | ✅ Completo  |
| **RF-15** | JIT Provisioning SSO (SAML/OIDC claim mapping)                        | `AuthenticateUserUseCase.php` (extensible)<br>`MfaPolicy.php` (evalúa SSO)<br>`UserRepositoryInterface` (findOrCreate)                                    | Pendiente (Semana 05+ - arquitectura preparada)                                                                                                                                                                                                                                                                   | Arquitectura: 100%<br>Implementación: 20%          | ⏳ Preparado |

## 2. Requisitos No Funcionales (RNF) - Trazabilidad
| RNF        | Descripción                       | Implementación                                                     | Tests / Verificación                                | Estado        |
|------------|-----------------------------------|--------------------------------------------------------------------|-----------------------------------------------------|---------------|
| **RNF-01** | Disponibilidad 99.99%             | K8s HPA + PostgreSQL Patroni HA + Redis Cluster                    | Infra (pendiente)                                   | ⏳ Infra       |
| **RNF-02** | Login p95 ≤ 300ms                 | `AuthenticateUserUseCase` optimizado, índices BD, Redis local      | `k6 load tests` (configurado Semana 05)             | ⏳ Configurado |
| **RNF-03** | Validación token p99 ≤ 50ms       | JWKS cache (TTL 1h), Redis blocklist O(1), gRPC opcional           | `k6` + synthetic monitoring                         | ⏳ Configurado |
| **RNF-04** | Revocación ≤ 100ms                | Redis DEL + SET blocklist (O(1)), async event publish              | `RevokeSessionFeatureTest` (latencia)               | ✅ Verificado  |
| **RNF-05** | Throughput 500-2000 req/s         | Stateless Auth, Redis Cluster, K8s HPA                             | k6 stress test (500 req/s target)                   | ⏳ Configurado |
| **RNF-06** | TLS 1.3 + HSTS                    | NGINX/Envoy Gateway config, cert-manager                           | Infra (cert-manager)                                | ⏳ Infra       |
| **RNF-07** | AES-256/Argon2id                  | `Argon2idHasher` (memory_cost=65536), Vault para secrets           | `PasswordPolicyTest::testHashProducesValidArgon2id` | ✅ Verificado  |
| **RNF-08** | Auditoría append-only 7 años      | `PdoAuditLogRepository` (hash chain), Trigger BEFORE UPDATE/DELETE | `InMemoryAuditLogRepository::verifyHashChain()`     | ✅ Verificado  |
| **RNF-09** | Evidencias 90d WORM               | S3 Object Lock (Compliance), manifest.json firmado cosign          | Pipeline CI/CD (Semana 03 QA)                       | ⏳ Pipeline    |
| **RNF-10** | OWASP ASVS L2 + NIST 800-63B AAL2 | MFA obligatorio, Argon2id, JWT RS256, Rate limiting, Blocklist     | Penetration testing (pendiente Semana 05)           | ⏳ Pendiente   |
| **RNF-11** | Escalabilidad K8s HPA             | Stateless services, Redis Cluster, PG read replicas                | K8s manifests (pendiente)                           | ⏳ Infra       |
| **RNF-12** | OpenTelemetry/Grafana             | OTel SDK en UseCases, OTel Collector sidecar                       | Dashboards Grafana (pendiente)                      | ⏳ Configurado |
| **RNF-13** | RPO=0 / RTO<15min                 | PostgreSQL Patroni (sync replication), ArgoCD rollback             | DR drill (trimestral)                               | ⏳ Infra       |
| **RNF-14** | MFA success ≥95%                  | TOTP (RFC 6238) + WebAuthn, UX optimizada                          | Telemetría frontend + métricas                      | ⏳ Métricas    |
| **RNF-15** | WCAG 2.1 AA                       | Request DTOs validación, AuthResponseTransformer mensajes claros   | axe-core CI (pendiente Semana 08)                   | ⏳ Preparado   |

## 3. Mapeo Código → Tests (Archivos)
### Domain Services (Unit Tests - Alta Cobertura)
| Archivo Código                             | Archivo Test                         | Casos Test | Cobertura |
|--------------------------------------------|--------------------------------------|------------|-----------|
| `PasswordPolicy.php`                       | `PasswordPolicyTest.php`             | 9 casos    | 100%      |
| `MfaPolicy.php`                            | `MfaPolicyTest.php`                  | 8 casos    | 100%      |
| `TokenFactory.php` / `JwtTokenService.php` | `TokenFactoryTest.php`               | 3 casos    | 95%       |
| `RefreshTokenRotator.php`                  | `RefreshTokenRotatorTest.php`        | 4 casos    | 100%      |
| `Argon2idHasher.php`                       | `PasswordPolicyTest.php` (integrado) | 1 caso     | 100%      |
| `DeviceTrustService.php`                   | *Pendiente*                          | 0          | 0%        |
| `MfaPolicy.php` / `MfaRequirement.php`     | `MfaPolicyTest.php`                  | 8 casos    | 100%      |

### Use Cases (Feature Tests - Cobertura Media-Alta)
| Use Case                  | Archivo Test                   | Escenarios Cubiertos | Cobertura |
|---------------------------|--------------------------------|----------------------|-----------|
| `RegisterUserUseCase`     | `RegisterUserFeatureTest.php`  | 5 escenarios         | 100%      |
| `AuthenticateUserUseCase` | `LoginMfaFeatureTest.php`      | 4 escenarios         | 90%       |
| `RefreshTokenUseCase`     | `RefreshTokenFeatureTest.php`  | 3 escenarios         | 90%       |
| `RevokeSessionUseCase`    | `RevokeSessionFeatureTest.php` | 3 escenarios         | 100%      |

### Repositories (InMemory 100%, PDO Pendiente)
| Repository                       | InMemory Test                     | PDO Test (Testcontainers)   |
|----------------------------------|-----------------------------------|-----------------------------|
| `InMemoryUserRepository`         | ✅ Métodos CRUD + memberships      | ⏳ Pendiente Testcontainers  |
| `InMemoryTenantRepository`       | ✅ CRUD                            | ⏳ Pendiente                 |
| `InMemorySessionRepository`      | ✅ CRUD + idle + indexes           | ⏳ Pendiente                 |
| `InMemoryRefreshTokenRepository` | ✅ CRUD + rotation + prefix search | ⏳ Pendiente                 |
| `InMemoryAuditLogRepository`     | ✅ verifyHashChain()               | ✅ verifyHashChain()         |
| `PDOUserRepository`              | N/A                               | ⏳ Testcontainers PostgreSQL |
| `PDOTenantRepository`            | N/A                               | ⏳ Testcontainers PostgreSQL |
| `PDOSessionRepository`           | N/A                               | ⏳ Testcontainers PostgreSQL |
| `PdoAuditLogRepository`          | N/A (usa InMemory para verify)    | ⏳ Testcontainers            |

### Controllers + Middleware (Feature Tests)
| Componente            | Test                                                                                                    | Escenarios            |
|-----------------------|---------------------------------------------------------------------------------------------------------|-----------------------|
| `AuthController`      | `LoginMfaFeatureTest`, `RegisterUserFeatureTest`, `RefreshTokenFeatureTest`, `RevokeSessionFeatureTest` | Happy paths + errores |
| `TenantMiddleware`    | `MultiTenantIsolationFeatureTest`                                                                       | 3 escenarios          |
| `JwtAuthMiddleware`   | `RefreshTokenFeatureTest` (reuse detection), `MultiTenantIsolationFeatureTest`                          | 3 escenarios          |
| `RateLimitMiddleware` | Pendiente                                                                                               | 0                     |

## 4. Gaps Identificados y Plan de Acción
| Gap                                      | Prioridad | Acción                                         | Responsable | Semana |
|------------------------------------------|-----------|------------------------------------------------|-------------|--------|
| `DeviceTrustService` sin tests unitarios | 🟡 Media  | Crear `DeviceTrustServiceTest.php`             | Dev         | 07     |
| Lockout progresivo tests específicos     | 🟡 Media  | Añadir casos en `LoginMfaFeatureTest`          | Dev         | 07     |
| Single Session Policy tests              | 🟡 Media  | Añadir test dedicado                           | Dev         | 07     |
| RateLimitMiddleware tests                | 🟡 Media  | Crear `RateLimitMiddlewareTest.php`            | Dev         | 07     |
| PDO Repositories con Testcontainers      | 🔴 Alta   | Setup Testcontainers PostgreSQL + Redis        | DevOps      | 07     |
| JwtAuthMiddleware tests aislados         | 🟡 Media  | Mocks para blocklist + JWT                     | Dev         | 07     |
| RateLimitMiddleware feature test         | 🟡 Media  | Test 429 response + Retry-After                | Dev         | 07     |
| axe-core WCAG 2.1 AA en CI               | 🟢 Baja   | Configurar axe-core en pipeline                | QA          | 08     |
| k6 Load Tests ejecucion real             | 🔴 Alta   | Ejecutar en staging                            | DevOps      | 05-06  |
| Chaos Engineering (LitmusChaos)          | 🟡 Media  | Experimentos: pod kill, Redis latency, DB pool | DevOps      | 05-06  |
| Penetration Testing Auth                 | 🔴 Alta   | OWASP ASVS L2 assessment                       | Security    | 05-06  |

## 5. Métricas de Cobertura Actual
| Capa                        | Cobertura Objetivo | Cobertura Actual              | Gap |
|-----------------------------|--------------------|-------------------------------|-----|
| **Domain Services**         | ≥ 90%              | ~90%                          | ✅  |
| **Use Cases**               | ≥ 80%              | ~85%                          | ✅  |
| **Repositories (InMemory)** | 100%               | 100%                          | ✅  |
| **Repositories (PDO)**      | ≥ 80%              | 0% (pendiente Testcontainers) | 🔴  |
| **Controllers**             | ≥ 70%              | ~75%                          | ✅  |
| **Middleware**              | ≥ 70%              | ~50%                          | 🟡  |
| **Global**                  | ≥ 80%              | ~72%                          | 🟡  |
