# Semana 03 — Diseño Arquitectónico, Vistas C4/UML y Código Base PHP 8.2+

## Módulo: Gestión de usuarios, tenants y endurecimiento de autenticación
**Flujo principal:** «Alta de usuario por tenant, autenticación segura y revocación de sesión»

---

## Entregables de Documentación (`docs/tenants/semana-03/`)

| Archivo                   | Tipo     | Descripción                                                                                                                                |
|---------------------------|----------|--------------------------------------------------------------------------------------------------------------------------------------------|
| `c4-architecture.puml`    | PlantUML | **C4 Context Diagram (Nivel 1)**: Sistema completo, 6 actores, 7 sistemas externos, fronteras                                              |
| `c4-containers.puml`      | PlantUML | **C4 Container Diagram (Nivel 2)**: 10 contenedores internos (Auth API, Use Cases, Domain, DB, Redis, JWT, MFA, Blocklist, Audit, Email)   |
| `c4-components.puml`      | PlantUML | **C4 Component Diagram (Nivel 3)**: 40+ componentes detallados (Use Cases, Entities, VOs, Domain Services, Events, Repositories, Adapters) |
| `c4-deployment.puml`      | PlantUML | **C4 Deployment Diagram (Nivel 4)**: K8s Prod/Staging, PostgreSQL HA, Redis Cluster, Vault, CI/CD, Observabilidad                          |
| `seq-register-user.puml`  | PlantUML | **Secuencia: Alta de Usuario** (RF-01, RF-02, RF-03)                                                                                       |
| `seq-login-mfa.puml`      | PlantUML | **Secuencia: Login + MFA + Tokens** (RF-04, RF-05, RF-06)                                                                                  |
| `seq-refresh-revoke.puml` | PlantUML | **Secuencia: Refresh + Reuse Detection + Revocación** (RF-07, RF-08, RF-09)                                                                |

---

## Código Fuente Implementado (`app/Modules/Tenants/`)

```
app/Modules/Tenants/
├── Domain/
│   ├── Entities/          # User, Tenant, Session, RefreshToken
│   ├── ValueObjects/      # Email, Password, TenantId, UserId, SessionId, JwtClaims, Role, DeviceFingerprint
│   ├── Services/          # PasswordPolicy, MfaPolicy, TokenFactory, RefreshTokenRotator, DeviceTrustService, Argon2idHasher
│   ├── Events/            # 14 Domain Events (UserCreated, UserLoggedIn, MfaChallenged, SessionRevoked, etc.)
│   └── Repositories/      # 5 Repository Interfaces (Puertos)
├── Application/
│   ├── Commands/          # RegisterUserCommand, AuthenticateCommand, MfaVerificationCommand, RefreshCommand, RevokeCommand
│   ├── Results/           # RegisterUserResult, AuthResult, RefreshResult, RevokeResult
│   └── UseCases/          # RegisterUserUseCase, AuthenticateUserUseCase, RefreshTokenUseCase, RevokeSessionUseCase
├── Infrastructure/
│   ├── Persistence/
│   │   ├── PDO/           # PDOUserRepository, PDOTenantRepository, PDOSessionRepository
│   │   └── Redis/         # RedisRefreshTokenRepository, RedisSessionRepository, RedisBlocklistRepository, RedisLockoutRepository, RedisRateLimitRepository
│   ├── Security/          # JwtTokenService (RS256), TotpVerifier, WebAuthnVerifier, Argon2idHasher
│   ├── Audit/             # PdoAuditLogRepository (hash chain), EventPublisher
│   └── External/          # HaveIBeenPwnedClient, EmailNotificationService, SiemForwarder
└── Presentation/
    ├── Controllers/       # AuthController (delgado, solo HTTP)
    ├── Requests/          # RegisterUserRequest, LoginRequest, RefreshRequest, MfaVerificationRequest, RevokeSessionRequest
    └── Transformers/      # AuthResponseTransformer
```

---

## Pruebas Implementadas (`tests/`)

### Unit Tests (`tests/Unit/Tenants/`)
| Test                          | Cobertura                                                        |
|-------------------------------|------------------------------------------------------------------|
| `PasswordPolicyTest.php`      | Validación longitud, complejidad, historial, HIBP, hash Argon2id |
| `MfaPolicyTest.php`           | Roles que requieren MFA, política tenant, WebAuthn               |
| `TokenFactoryTest.php`        | Creación/validación JWT RS256, claims correctos                  |
| `RefreshTokenRotatorTest.php` | Rotación exitosa, reuse detection, token no encontrado           |

### Feature Tests (`tests/Feature/Tenants/`)
| Test                                  | Escenarios                                                                  |
|---------------------------------------|-----------------------------------------------------------------------------|
| `RegisterUserFeatureTest.php`         | Éxito, tenant inactivo, cupo excedido, email duplicado, password policy     |
| `LoginMfaFeatureTest.php`             | Login sin MFA, login con MFA TOTP, credenciales inválidas, usuario inactivo |
| `RefreshTokenFeatureTest.php`         | Refresh exitoso, token inválido, reuse detection (ataque)                   |
| `RevokeSessionFeatureTest.php`        | Logout propio, force-revoke admin, sesión inexistente                       |
| `MultiTenantIsolationFeatureTest.php` | Cross-tenant bloqueado, tenant inactivo, roles por tenant                   |

---

## Cómo Renderizar Diagramas

```bash
# VS Code + PlantUML extension
# O CLI:
java -jar plantuml.jar docs/tenants/semana-03/c4-architecture.puml
java -jar plantuml.jar docs/tenants/semana-03/c4-containers.puml
java -jar plantuml.jar docs/tenants/semana-03/c4-components.puml
java -jar plantuml.jar docs/tenants/semana-03/c4-deployment.puml
java -jar plantuml.jar docs/tenants/semana-03/seq-register-user.puml
java -jar plantuml.jar docs/tenants/semana-03/seq-login-mfa.puml
java -jar plantuml.jar docs/tenants/semana-03/seq-refresh-revoke.puml
```

---

## Cómo Ejecutar Pruebas

```bash
# Requiere: composer install, phpunit, testcontainers (para integration tests)

# Unit tests (rápidos, sin BD)
./vendor/bin/phpunit tests/Unit/Tenants

# Feature tests (requieren mocks)
./vendor/bin/phpunit tests/Feature/Tenants

# Todos los tests del módulo
./vendor/bin/phpunit tests/Unit/Tenants tests/Feature/Tenants

# Con cobertura
./vendor/bin/phpunit --coverage-html coverage tests/Unit/Tenants
```

---

## Dependencias Requeridas (composer.json)

```json
{
  "require": {
    "php": "^8.2",
    "ramsey/uuid": "^4.7",
    "lcobucci/jwt": "^5.0",
    "otphp/otphp": "^11.0",
    "webauthn/webauthn": "^4.0",
    "guzzlehttp/guzzle": "^7.8",
    "symfony/mailer": "^6.4",
    "enqueue/enqueue": "^0.10",
    "doctrine/dbal": "^3.7"
  },
  "require-dev": {
    "phpunit/phpunit": "^10.5",
    "mockery/mockery": "^1.6"
  }
}
```

---

## Variables de Entorno Requeridas

```env
# Database
CENTRAL_DB_HOST=127.0.0.1
CENTRAL_DB_PORT=5432
CENTRAL_DB_DATABASE=his_central
CENTRAL_DB_USERNAME=postgres
CENTRAL_DB_PASSWORD=secret

HOSPITAL_DB_HOST=127.0.0.1
HOSPITAL_DB_PORT=5432
HOSPITAL_DB_DATABASE=his_hospital

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# JWT
JWT_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----"
JWT_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\n...\n-----END PUBLIC KEY-----"
JWT_ISSUER=his-tenants-auth
JWT_AUDIENCE=his-api
JWT_ACCESS_TTL=900
JWT_REFRESH_TTL=604800
JWT_KEY_ID=key-1

# Security
PASSWORD_MIN_LENGTH=12
PASSWORD_REQUIRE_COMPLEXITY=true
PASSWORD_CHECK_HIBP=true
PASSWORD_HISTORY_SIZE=5

# External APIs
HIBP_API_KEY=
SIEM_ENDPOINT=
MAIL_FROM_EMAIL=noreply@hospital.com
MAIL_FROM_NAME=Sistema Hospitalario
```

---

## Decisiones Arquitectónicas Clave (ADR)

| ADR         | Decisión                         | Justificación                                   |
|-------------|----------------------------------|-------------------------------------------------|
| **ADR-001** | Clean Architecture 4 capas       | SRP, DIP, testabilidad, evolución independiente |
| **ADR-002** | Domain Services para políticas   | Encapsula reglas que cambian por negocio        |
| **ADR-003** | Repository Interfaces en Domain  | DIP, intercambiabilidad PDO/Redis/InMemory      |
| **ADR-004** | Domain Events para auditoría     | Desacopla Use Cases de Infrastructure           |
| **ADR-005** | JWT RS256 + JWKS Rotation        | Claves asimétricas, revocación inmediata        |
| **ADR-006** | MFA Adaptativo TOTP + WebAuthn   | Flexibilidad por rol/tenant, futuro-proof       |
| **ADR-007** | Blocklist Access Tokens en Redis | Revocación O(1), expiración automática          |
| **ADR-008** | Auditoría Inmutable + Hash Chain | Cumplimiento legal, integridad verificable      |
| **ADR-009** | Multi-tenant: RLS + X-Tenant-ID  | Aislamiento a nivel BD, defensa en profundidad  |
| **ADR-010** | PHP 8.2+ Vanilla (sin framework) | Control total, sin vendor lock-in               |

---

## Integración con Arquitectura Existente

| Componente Existente                     | Integración                                               |
|------------------------------------------|-----------------------------------------------------------|
| `central_users` / `hospital_memberships` | Mapeados en `PDOUserRepository` + `PDOTenantRepository`   |
| `cross_hospital_access_audits`           | Patrón replicado en `PdoAuditLogRepository` + `audit_log` |
| `config/database.php`                    | Conexiones PDO reutilizadas                               |
| `config/jwt.php` (HS256)                 | Migración completa a RS256 en `JwtTokenService`           |
| `JwtAuth` + `TenantMiddleware`           | Funcionalidad absorbida en Domain Services                |
| `AuthController` (Laravel)               | Sustituido por Presentation delgada + UseCases            |

