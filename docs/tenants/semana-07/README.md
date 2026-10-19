# Semana 07 — Diseño de Componentes y Refactorización
## Entregables Generados (`docs/tenants/semana-07/`)
| Archivo                  | Tipo     | Descripción                                                                                                                                                                                           |
|--------------------------|----------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `component-diagram.puml` | PlantUML | **Diagrama de Componentes Completo**: Backend (4 capas, 50+ componentes) + Frontend (5 UI components) + External Systems (7)                                                                          |
| `refactor-analysis.md`   | Markdown | **Análisis Refactorización**: Punto mayor acoplamiento (AuthenticateUserUseCase Ce=13), descomposición en 5 Use Cases + Orchestrator, métricas -75% acoplamiento, contratos I/O, plan migración 15.5h |
| `component-contracts.md` | Markdown | **Contratos Completos**: 6 Use Cases (I/O), 5 Domain Services, 5 Repository Interfaces, HTTP API DTOs, Domain Events (11), Kafka Topics (6), Shared Adapter                                           |
| `README.md`              | Markdown | Este índice                                                                                                                                                                                           |
## Resumen Ejecutivo
### 🎯 Punto de Mayor Acoplamiento Identificado
| Componente                  | Acoplamiento Eferente (Ce) | Inestabilidad (I) | Problema                                                                 |
|-----------------------------|----------------------------|-------------------|--------------------------------------------------------------------------|
| **AuthenticateUserUseCase** | **13**                     | **0.87**          | 13 responsabilidades mezcladas (SRP violado)                             |
| JwtAuthMiddleware           | 11                         | 1.00              | Validación JWT + Blocklist + Tenant consistency + Claims + Expiry + Keys |

### 🔧 Refactor Propuesto: Descomposición en 5 Use Cases Especializados
```
AuthenticateUserUseCase (13 responsabilidades)
    │
    ├── ValidateCredentialsUseCase (3 deps)     → Buscar usuario + verificar password + validar tenant
    ├── EvaluateMfaPolicyUseCase (1 dep)        → Evaluar si requiere MFA + tipo (TOTP/WebAuthn)
    ├── ExecuteMfaChallengeUseCase (3 deps)     → Generar/verificar challenge MFA + lockout MFA
    ├── GenerateTokensUseCase (6 deps)          → JWT RS256 + Refresh rotation + Single session + Device trust
    ├── HandleLoginAuditUseCase (3 deps)        → Eventos auditoría + Lockout progresivo + Event publishing
    │
    └── AuthenticateUserOrchestrator (Facade)   → Mantiene interfaz original, coordina 5 Use Cases
```
### 📊 Métricas de Mejora (Antes vs Después)
| Métrica                                 | Antes  | Después    | Mejora                |
|-----------------------------------------|--------|------------|-----------------------|
| **Acoplamiento Eferente Promedio (Ce)** | 13     | **3.2**    | **-75%**              |
| **Inestabilidad Promedio (I)**          | 0.87   | **0.45**   | **-48%**              |
| **Clases violando SRP**                 | 1      | **0**      | **100% cumplimiento** |
| **Mocks por Test Unitario**             | 13     | **1-6**    | **-54% a -92%**       |
| **Tiempo Test Unitario**                | ~500ms | **~100ms** | **-80%**              |
| **Reutilización Código**                | 0%     | **Alta**   | **Nueva capacidad**   |

## 📋 Contratos Definidos (Resumen)
### Use Cases (6 interfaces)
| Use Case                       | Input                                        | Output                               | Dependencias                                                                              |
|--------------------------------|----------------------------------------------|--------------------------------------|-------------------------------------------------------------------------------------------|
| `ValidateCredentialsUseCase`   | email, password, tenantId, device, ip        | user, tenant, success, failureReason | 3 (IUserRepo, ITenantRepo, Argon2idHasher)                                                |
| `EvaluateMfaPolicyUseCase`     | user, tenant                                 | required, type (TOTP/WEBAUTHN)       | 1 (MfaPolicy)                                                                             |
| `ExecuteMfaChallengeUseCase`   | user, tenant, method, code?, challengeId?    | success, challengeId, errorCode      | 3 (TotpVerifier, WebAuthnVerifier, RedisLockout)                                          |
| `GenerateTokensUseCase`        | user, tenant, device, ip, mfaUsed            | accessToken, refreshToken, sessionId | 6 (TokenFactory, RefreshTokenRotator, DeviceTrust, ISessionRepo, IRefreshRepo, Blocklist) |
| `HandleLoginAuditUseCase`      | user, tenant, eventName, payload, ip, device | void (eventos)                       | 3 (IAuditRepo, EventPublisher, RedisLockout)                                              |
| `AuthenticateUserOrchestrator` | AuthenticateCommand                          | AuthResult (compatibilidad total)    | 5 Use Cases                                                                               |

### Domain Services (5 interfaces)
- `TokenFactory` (createAccessToken, validateAccessToken)
- `RefreshTokenRotator` (generateAndStore, rotate + reuse detection)
- `MfaPolicy` (evaluate → MfaRequirement)
- `PasswordPolicy` (validate + hash)
- `DeviceTrustService` (registerDevice + evaluateTrust)

### Repository Interfaces (5 puertos)
- `UserRepositoryInterface` (7 métodos)
- `TenantRepositoryInterface` (4 métodos)
- `SessionRepositoryInterface` (7 métodos)
- `RefreshTokenRepositoryInterface` (5 métodos)
- `AuditLogRepositoryInterface` (5 métodos)

### HTTP API (Request/Response DTOs)
- **Auth**: Register, Login, MFA, Refresh, Logout, Force-Revoke
- **Admin Users**: CRUD + Force-Revoke sessions
- **Admin Tenants**: CRUD (PLATFORM_ADMIN)
- **System**: Health, JWKS

### Domain Events (11 eventos async)
`USER_CREATED`, `USER_LOGGED_IN`, `LOGIN_FAILED`, `MFA_CHALLENGED`, `MFA_SUCCEEDED`, `MFA_FAILED`, `SESSION_REVOKED`, `TOKEN_REFRESHED`, `REFRESH_REUSE_DETECTED`, `ACCOUNT_LOCKED`, `CROSS_TENANT_ATTEMPT`

### Kafka Topics (6)
`tenants.auth.user_created`, `user_logged_in`, `session_revoked`, `mfa_challenged`, `refresh_reuse_detected`, `account_locked`

### Shared Adapter (Otros Módulos)
`CentralUserReadOnlyRepository` - Read-only access a `central_users`/`hospital_memberships` para módulos Expediente y Gobernanza

## 📁 Diagramas y Documentación
### Diagramas PlantUML
```bash
# Diagrama de Componentes (50+ componentes, 4 capas + frontend + external)
java -jar plantuml.jar docs/tenants/semana-07/component-diagram.puml
```

### Documentos Markdown
| Archivo                  | Contenido                                                                                                  |
|--------------------------|------------------------------------------------------------------------------------------------------------|
| `component-diagram.puml` | 50+ componentes, 4 capas backend + frontend + 7 external systems                                           |
| `refactor-analysis.md`   | Análisis completo: métricas, descomposición, contratos I/O, plan 15.5h, compatibilidad                     |
| `component-contracts.md` | 6 Use Cases, 5 Domain Services, 5 Repositories, HTTP API, 11 Domain Events, 6 Kafka Topics, Shared Adapter |

## 🔄 Plan de Migración (Semana 07)
| Paso | Acción                                    | Esfuerzo | Validación                                    |
|------|-------------------------------------------|----------|-----------------------------------------------|
| 1    | Crear ValidateCredentialsUseCase + Test   | 2h       | Unit test 100%                                |
| 2    | Crear EvaluateMfaPolicyUseCase + Test     | 1h       | Unit test 100%                                |
| 3    | Crear ExecuteMfaChallengeUseCase + Test   | 3h       | Unit test 100% (lockout MFA)                  |
| 4    | Crear GenerateTokensUseCase + Test        | 3h       | Unit test 100% (single session, device trust) |
| 5    | Crear HandleLoginAuditUseCase + Test      | 2h       | Unit test 100% (lockout progresivo)           |
| 6    | Crear AuthenticateUserOrchestrator + Test | 2h       | Feature tests 18/18 pasan                     |
| 7    | Actualizar ModuleBootstrap.php            | 0.5h     | DI resuelve                                   |
| 8    | Ejecutar suite completa                   | 0.5h     | 0 breaking changes                            |

**Total: 15.5 horas**

## ✅ Criterios de Aceptación Refactor
| Criterio                      | Estado Objetivo                                             |
|-------------------------------|-------------------------------------------------------------|
| **0 breaking changes**        | ✅ AuthenticateUserOrchestrator implementa interfaz original |
| **Tests Feature pasan**       | ✅ 18/18 escenarios existentes                               |
| **Cobertura Domain Services** | ≥ 90%                                                       |
| **Cobertura Use Cases**       | ≥ 85%                                                       |
| **Acoplamiento Ce promedio**  | ≤ 4                                                         |
| **Inestabilidad I promedio**  | ≤ 0.5                                                       |
| **Tiempo suite unit tests**   | < 30s                                                       |

## 🔗 Integración con Arquitectura Existente
| Componente Existente               | Impacto Refactor                                          |
|------------------------------------|-----------------------------------------------------------|
| `AuthController`                   | **Cero cambios** - usa `AuthenticateUserUseCaseInterface` |
| `ModuleBootstrap.php`              | Registrar 5 nuevos Use Cases + Orchestrator               |
| `AuthenticateUserUseCase` original | Mantener por compatibilidad (deprecated)                  |
| `JwtAuthMiddleware`                | Sin cambios - usa `JwtTokenService`                       |
| `TenantMiddleware`                 | Sin cambios                                               |
| Feature Tests                      | Deben pasar sin modificaciones                            |

## 🔍 Verificación Rápida
```bash
java -jar plantuml.jar docs/tenants/semana-07/component-diagram.puml
ls docs/tenants/semana-07/
grep -c "UseCase" docs/tenants/semana-07/refactor-analysis.md
grep -c "Interface" docs/tenants/semana-07/component-contracts.md
```
