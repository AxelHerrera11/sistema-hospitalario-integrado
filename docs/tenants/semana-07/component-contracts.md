# Semana 07 — Contratos de Componentes (Interfaces I/O)
## 1. Contratos Application Layer (Use Cases)
### 1.1 ValidateCredentialsUseCase
| Aspecto             | Definición                                                                                                                                                                                                                        |
|---------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Interface**       | `ValidateCredentialsUseCaseInterface`                                                                                                                                                                                             |
| **Método**          | `execute(ValidateCredentialsCommand): ValidateCredentialsResult`                                                                                                                                                                  |
| **Input (Command)** | `email: Email`, `rawPassword: string`, `tenantId: TenantId`, `device: DeviceFingerprint`, `ip: string`                                                                                                                            |
| **Output (Result)** | `success: bool`, `user: User|null`, `tenant: Tenant|null`, `failureReason: string|null`                                                                                                                                           |
| **Errores**         | Lanza `DomainException` solo en fallos de infraestructura (BD, Redis)                                                                                                                                                             |
| **Dependencias**    | `UserRepositoryInterface`, `TenantRepositoryInterface`, `Argon2idHasher`                                                                                                                                                          |
| **Reglas Negocio**  | - Buscar usuario por email + tenant (join memberships)<br>- Verificar password Argon2id<br>- Validar tenant.status = 'active'<br>- Validar user.status = 'active'<br>- No registra auditoría (delegado a HandleLoginAuditUseCase) |

### 1.2 EvaluateMfaPolicyUseCase
| Aspecto             | Definición                                                                                                                                                                                                    |
|---------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Interface**       | `EvaluateMfaPolicyUseCaseInterface`                                                                                                                                                                           |
| **Método**          | `execute(EvaluateMfaPolicyCommand): EvaluateMfaPolicyResult`                                                                                                                                                  |
| **Input (Command)** | `user: User`, `tenant: Tenant`                                                                                                                                                                                |
| **Output (Result)** | `required: bool`, `type: string` ('TOTP' \| 'WEBAUTHN')                                                                                                                                                       |
| **Dependencias**    | `MfaPolicy` (Domain Service)                                                                                                                                                                                  |
| **Reglas Negocio**  | - `user.mfa_required` → true<br>- `tenant.policies['mfa_required']` → true<br>- `user.roles` contiene `TENANT_ADMIN` o `AUDITOR` → true<br>- Si `tenant.policies['mfa_webauthn_enabled']` → type = 'WEBAUTHN' |

### 1.3 ExecuteMfaChallengeUseCase
| Aspecto             | Definición                                                                                                                                                                                                                                                                                       |
|---------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Interface**       | `ExecuteMfaChallengeUseCaseInterface`                                                                                                                                                                                                                                                            |
| **Método**          | `execute(ExecuteMfaChallengeCommand): ExecuteMfaChallengeResult`                                                                                                                                                                                                                                 |
| **Input (Command)** | `user: User`, `tenant: Tenant`, `method: string`, `code: string|null`, `challengeId: string|null`, `device: DeviceFingerprint`, `ip: string`                                                                                                                                                     |
| **Output (Result)** | `success: bool`, `challengeId: string|null`, `errorCode: string|null` ('INVALID_CODE', 'MFA_LOCKED', 'CHALLENGE_EXPIRED')                                                                                                                                                                        |
| **Dependencias**    | `TotpVerifier`, `WebAuthnVerifier`, `RedisLockoutRepository`, `RedisRateLimitRepository`                                                                                                                                                                                                         |
| **Reglas Negocio**  | - Si `code === null` → genera challenge (TOTP secret / WebAuthn challenge)<br>- Si `code !== null` → verifica código<br>- TOTP: ventana ±30s (RFC 6238)<br>- WebAuthn: validar assertion contra credential registrada<br>- 3 fallos → bloqueo 30min (RedisLockout)<br>- Rate limit por IP/device |

### 1.4 GenerateTokensUseCase
| Aspecto             | Definición                                                                                                                                                                                                                                                                                                          |
|---------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Interface**       | `GenerateTokensUseCaseInterface`                                                                                                                                                                                                                                                                                    |
| **Método**          | `execute(GenerateTokensCommand): GenerateTokensResult`                                                                                                                                                                                                                                                              |
| **Input (Command)** | `user: User`, `tenant: Tenant`, `device: DeviceFingerprint`, `ip: string`, `mfaUsed: bool`                                                                                                                                                                                                                          |
| **Output (Result)** | `accessToken: AccessToken`, `refreshToken: string`, `expiresIn: int`, `sessionId: SessionId`                                                                                                                                                                                                                        |
| **Dependencias**    | `TokenFactory`, `RefreshTokenRotator`, `DeviceTrustService`, `SessionRepositoryInterface`, `RefreshTokenRepositoryInterface`, `BlocklistService`                                                                                                                                                                    |
| **Reglas Negocio**  | - Access Token: JWT RS256, 15min, claims completos<br>- Refresh Token: opaco 32 bytes, hash Argon2id, TTL 7d<br>- Single Session: si `tenant.policies['single_session']` → revoca sesiones previas<br>- Device Trust: registra/actualiza trust score<br>- Blocklist: revoca access tokens previos si single session |

### 1.5 HandleLoginAuditUseCase
| Aspecto                | Definición                                                                                                                                                                                                                                                       |
|------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Interface**          | `HandleLoginAuditUseCaseInterface`                                                                                                                                                                                                                               |
| **Método**             | `execute(HandleLoginAuditCommand): void`                                                                                                                                                                                                                         |
| **Input (Command)**    | `user: User`, `tenant: Tenant`, `eventName: string`, `payload: array`, `ip: string`, `device: DeviceFingerprint`                                                                                                                                                 |
| **Eventos Soportados** | `LOGIN_SUCCESS`, `LOGIN_FAILED`, `MFA_CHALLENGED`, `MFA_SUCCEEDED`, `MFA_FAILED`, `ACCOUNT_LOCKED`                                                                                                                                                               |
| **Dependencias**       | `AuditLogRepositoryInterface`, `EventPublisher`, `RedisLockoutRepository`                                                                                                                                                                                        |
| **Reglas Negocio**     | - Registra Domain Event en `IAuditRepo` (hash chain SHA-256)<br>- Publica evento en `EventPublisher` (Kafka/Message Broker)<br>- Lockout progresivo: 5 fallos→15min, 10→1h, 20→24h<br>- MFA lockout: 3 fallos→30min<br>- Publica `AccountLocked` event si aplica |

### 1.6 AuthenticateUserOrchestrator (Facade)
| Aspecto             | Definición                                                                                                                                                                                                                                                                   |
|---------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Interface**       | `AuthenticateUserUseCaseInterface` (misma que original)                                                                                                                                                                                                                      |
| **Método**          | `execute(AuthenticateCommand): AuthResult`                                                                                                                                                                                                                                   |
| **Input (Command)** | `email: Email`, `rawPassword: string`, `tenantId: TenantId`, `device: DeviceFingerprint`, `ip: string`                                                                                                                                                                       |
| **Output (Result)** | `AuthResult` (accessToken, refreshToken, expiresIn, user, sessionId, mfaRequired, challengeId, mfaMethod)                                                                                                                                                                    |
| **Flujo Interno**   | 1. `ValidateCredentialsUseCase` → 2. `EvaluateMfaPolicyUseCase` → 3. `ExecuteMfaChallengeUseCase` (si requerido) → 4. `GenerateTokensUseCase` → 5. `HandleLoginAuditUseCase`                                                                                                 |
| **Branching**       | - Credenciales inválidas → Audit LOGIN_FAILED → throw DomainException<br>- MFA requerido + challenge generado → retorna AuthResult con `mfaRequired=true` + `challengeId`<br>- MFA verificado / Sin MFA → GenerateTokens → Audit LOGIN_SUCCESS → retorna AuthResult completo |

## 2. Contratos Domain Services
### 2.1 TokenFactory
| Aspecto                        | Definición                                                                                                               |
|--------------------------------|--------------------------------------------------------------------------------------------------------------------------|
| **Interface**                  | `TokenFactory`                                                                                                           |
| **Métodos**                    | `createAccessToken(User, Tenant, SessionId, DeviceFingerprint): AccessToken`<br>`validateAccessToken(string): JwtClaims` |
| **Input createAccessToken**    | `User`, `Tenant`, `SessionId`, `DeviceFingerprint`                                                                       |
| **Output createAccessToken**   | `AccessToken` (token string + JwtClaims)                                                                                 |
| **Input validateAccessToken**  | `token: string` (JWT)                                                                                                    |
| **Output validateAccessToken** | `JwtClaims` (claims parseados y validados)                                                                               |
| **Excepciones**                | `InvalidArgumentException` si token inválido/expirado/firma inválida                                                     |
| **Implementación**             | `JwtTokenService` (RS256/ES256, JWKS rotation, key management)                                                           |

### 2.2 RefreshTokenRotator
| Aspecto                     | Definición                                                                                                                                                                                                                                                          |
|-----------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Interface**               | `RefreshTokenRotator`                                                                                                                                                                                                                                               |
| **Métodos**                 | `generateAndStore(User, Tenant, DeviceFingerprint, SessionId, accessTokenJti): RefreshTokenResult`<br>`rotate(string refreshToken, User, Tenant, DeviceFingerprint, SessionId): RotationResult`                                                                     |
| **Input generateAndStore**  | `User`, `Tenant`, `DeviceFingerprint`, `SessionId`, `accessTokenJti: string`                                                                                                                                                                                        |
| **Output generateAndStore** | `RefreshTokenResult` (token, expiresAt)                                                                                                                                                                                                                             |
| **Input rotate**            | `refreshToken: string`, `User`, `Tenant`, `DeviceFingerprint`, `SessionId`                                                                                                                                                                                          |
| **Output rotate**           | `RotationResult` (success, accessToken, refreshToken, expiresIn, error, reuseDetected)                                                                                                                                                                              |
| **Reglas Reuse Detection**  | - Extraer user_id/device_id del token (sin verificar)<br>- Buscar hash en Redis<br>- Verificar Argon2id(refreshToken) === hash<br>- Si NO coincide → REUSE DETECTED → revocación total + alerta CRITICAL<br>- Si coincide → DELETE old, generar nuevo par, save new |

### 2.3 MfaPolicy
| Aspecto       | Definición                                                                                 |
|---------------|--------------------------------------------------------------------------------------------|
| **Interface** | `MfaPolicy`                                                                                |
| **Método**    | `evaluate(array $userRoles, array $tenantPolicies, bool $userMfaRequired): MfaRequirement` |
| **Input**     | `userRoles: Role[]`, `tenantPolicies: array`, `userMfaRequired: bool`                      |
| **Output**    | `MfaRequirement` (required: bool, type: 'TOTP'\|'WEBAUTHN')                                |
| **Reglas**    | Ver sección 1.2                                                                            |

### 2.4 PasswordPolicy
| Aspecto             | Definición                                                                                                                                                  |
|---------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Interface**       | `PasswordPolicy`                                                                                                                                            |
| **Métodos**         | `validate(string $rawPassword, array $history): ValidationResult`<br>`hash(string $rawPassword): Password`                                                  |
| **Input validate**  | `rawPassword: string`, `history: string[]` (hashes anteriores)                                                                                              |
| **Output validate** | `ValidationResult` (valid: bool, errors: string[])                                                                                                          |
| **Reglas**          | - Longitud ≥ 12<br>- Complejidad: upper, lower, digit, symbol<br>- Historial: no coincidir últimos 5 hashes<br>- HIBP: check HaveIBeenPwned API (fail-open) |

### 2.5 DeviceTrustService
| Aspecto                   | Definición                                                                                                       |
|---------------------------|------------------------------------------------------------------------------------------------------------------|
| **Interface**             | `DeviceTrustService`                                                                                             |
| **Métodos**               | `registerDevice(UserId, DeviceFingerprint): DeviceTrustResult`<br>`evaluateTrust(DeviceFingerprint): TrustLevel` |
| **Input registerDevice**  | `UserId`, `DeviceFingerprint`                                                                                    |
| **Output registerDevice** | `DeviceTrustResult` (device, isKnown, message)                                                                   |
| **Trust Levels**          | `UNKNOWN` (nuevo), `SUSPICIOUS` (<50), `LOW_TRUST` (50-79), `TRUSTED` (≥80)                                      |

## 3. Contratos Repositories (Interfaces Domain)
### 3.1 UserRepositoryInterface
```php
interface UserRepositoryInterface
{
    public function save(User $user): void;
    public function findById(UserId $id): ?User;
    public function findByEmailAndTenant(Email $email, TenantId $tenantId): ?User;
    public function findByIdAndTenant(UserId $id, TenantId $tenantId): ?User;
    public function existsByEmailAndTenant(Email $email, TenantId $tenantId): bool;
    public function findAllByTenant(TenantId $tenantId, int $limit = 50, int $offset = 0): array;
    public function countByTenant(TenantId $tenantId): int;
    public function delete(UserId $id): void;
}
```
### 3.2 TenantRepositoryInterface
```php
interface TenantRepositoryInterface
{
    public function save(Tenant $tenant): void;
    public function findById(TenantId $id): ?Tenant;
    public function findAll(int $limit = 50, int $offset = 0): array;
    public function count(): int;
}
```
### 3.3 SessionRepositoryInterface
```php
interface SessionRepositoryInterface
{
    public function save(Session $session): void;
    public function findById(SessionId $id): ?Session;
    public function findByUserId(UserId $userId): array;
    public function findByUserIdAndDevice(UserId $userId, string $deviceId): ?Session;
    public function findAllByUserId(UserId $userId): array;
    public function delete(SessionId $id): void;
    public function deleteAllByUserId(UserId $userId): void;
    public function findIdleSessions(int $idleTimeoutSeconds): array;
}
```
### 3.4 RefreshTokenRepositoryInterface
```php
interface RefreshTokenRepositoryInterface
{
    public function save(RefreshToken $refreshToken): void;
    public function findByUserIdAndDevice(UserId $userId, string $deviceId): ?RefreshToken;
    public function findAllByUserId(UserId $userId): array;
    public function delete(RefreshToken $refreshToken): void;
    public function deleteAllByUserId(UserId $userId): void;
    public function findByTokenPrefix(string $tokenPrefix): ?RefreshToken;
}
```
### 3.5 AuditLogRepositoryInterface
```php
interface AuditLogRepositoryInterface
{
    public function log(DomainEvent $event): void;
    public function logRaw(string $eventName, array $payload, ?string $severity = null): void;
    public function findByUserId(string $userId, int $limit = 100, int $offset = 0): array;
    public function findByTenantId(string $tenantId, int $limit = 100, int $offset = 0): array;
    public function findByEventName(string $eventName, int $limit = 100, int $offset = 0): array;
    public function findByDateRange(DateTimeImmutable $from, DateTimeImmutable $to, int $limit = 100, int $offset = 0): array;
}
```
## 4. Contratos Presentation Layer (HTTP API)
### 4.1 Request DTOs
#### RegisterUserRequest
```json
POST /auth/register | /admin/users
Headers: Authorization: Bearer <token>, X-Tenant-ID: <uuid>
Body:
{
  "email": "string (email, max 255)",
  "name": "string (max 255)",
  "password": "string (min 12, max 128)",
  "roles": ["TENANT_ADMIN"|"MEDICO"|"ENFERMERO"|"PACIENTE"|"AUDITOR"],
  "mfa_required": "boolean (opcional, default false)" }
```
#### LoginRequest
```json
POST /auth/login
Headers: X-Tenant-ID: <uuid>, X-Device-ID: <string> (opcional)
Body:
{
  "email": "string (email)",
  "password": "string"}
```
#### MfaVerificationRequest
```json
POST /auth/mfa
Body:
{
  "challenge_id": "uuid",
  "code": "string (6 dígitos TOTP o base64 WebAuthn)",
  "method": "TOTP|WEBAUTHN (default TOTP)"}
```
#### RefreshRequest
```json
POST /auth/refresh
Body:
{"refresh_token": "string (opaco)"}
```
#### RevokeSessionRequest
```json
POST /auth/logout | DELETE /admin/users/{userId}/sessions
Headers: Authorization: Bearer <token>, X-Tenant-ID: <uuid>
Body (opcional):
{
  "session_id": "uuid (para logout propio)",
  "reason": "logout|force_revoke|security|admin_action (default logout)"}
```
### 4.2 Response Transformers
#### AuthSuccessResponse
```json
200 OK
{
  "success": true,
  "data": {
    "access_token": "eyJhbGciOiJSUzI1NiIs...",
    "token_type": "Bearer",
    "expires_in": 900,
    "refresh_token": "opaque_token_string",
    "user": { "id": "uuid", "email": "...", "name": "...", "roles": ["MEDICO"], "tenant_id": "uuid", ... },
    "session_id": "uuid"}}
```
#### MfaChallengeResponse
```json
200 OK
{
  "success": true,
  "mfa_required": true,
  "challenge_id": "uuid",
  "method": "TOTP|WEBAUTHN",
  "message": "Autenticación de doble factor requerida"}
```
#### RefreshResponse
```json
200 OK
{
  "success": true,
  "data": {
    "access_token": "eyJhbGciOiJSUzI1NiIs...",
    "token_type": "Bearer",
    "expires_in": 900,
    "refresh_token": "new_opaque_token"}}
```
#### RevokeResponse
```json
200 OK
{
  "success": true,
  "data": {
    "revoked_sessions": 3,
    "message": "3 sesiones revocadas"}}
```
#### ErrorResponse (Estándar)
```json
4xx/5xx
{
  "success": false,
  "error": {
    "message": "Descripción legible",
    "code": 401,
    "details": { "field": "password", "reason": "password_too_short", "min_length": 12 }}}
```
## 5. Contratos Domain Events (Async)
### 5.1 Event Structure Base
```json
{
  "event_id": "uuid",
  "event_name": "USER_CREATED|LOGIN_SUCCESS|SESSION_REVOKED|...",
  "occurred_at": "2026-10-18T10:30:00.000Z",
  "payload": { ... },
  "severity": "INFO|WARNING|CRITICAL"
}
```
### 5.2 Event Types
| Event Name               | Payload Key Fields                                          | Consumers                           |
|--------------------------|-------------------------------------------------------------|-------------------------------------|
| `USER_CREATED`           | user_id, tenant_id, email, roles, created_by                | Clinical, Governance, Audit         |
| `USER_LOGGED_IN`         | user_id, tenant_id, roles, mfa_used, device, ip, session_id | Governance, Audit, Clinical (cache) |
| `LOGIN_FAILED`           | email, tenant_id, reason, ip                                | Governance, SIEM                    |
| `MFA_CHALLENGED`         | user_id, tenant_id, method, challenge_id                    | Governance                          |
| `MFA_SUCCEEDED`          | user_id, tenant_id, method                                  | Governance                          |
| `MFA_FAILED`             | user_id, tenant_id, method                                  | Governance, SIEM                    |
| `SESSION_REVOKED`        | user_id, tenant_id, reason, revoked_count, requester_id     | Clinical, Governance, Audit         |
| `TOKEN_REFRESHED`        | user_id, tenant_id, old_jti, new_jti, device                | Governance, Audit                   |
| `REFRESH_REUSE_DETECTED` | user_id, tenant_id, device, ip, severity:CRITICAL           | Governance, SIEM, Security Team     |
| `ACCOUNT_LOCKED`         | user_id, tenant_id, lock_type, duration, failed_attempts    | Clinical, Governance                |

## 6. Contratos Infrastructure (Async)
### 6.1 Kafka Topics Configuration
```yaml
topics:
  - name: tenants.auth.user_created
    partitions: 6
    retention: 7d
    schema: UserCreatedEvent (Avro)
  - name: tenants.auth.user_logged_in
    partitions: 12
    retention: 3d
    schema: UserLoggedInEvent (Avro)
  - name: tenants.auth.session_revoked
    partitions: 6
    retention: 7d
    schema: SessionRevokedEvent (Avro)
  - name: tenants.auth.mfa_challenged
    partitions: 3
    retention: 3d
    schema: MfaChallengedEvent (Avro)
  - name: tenants.auth.refresh_reuse_detected
    partitions: 3
    retention: 30d
    schema: RefreshReuseDetectedEvent (Avro)
  - name: tenants.auth.account_locked
    partitions: 3
    retention: 7d
    schema: AccountLockedEvent (Avro)
```
### 6.2 Avro Schema Example (UserCreatedEvent)
```json
{
  "type": "record",
  "name": "UserCreatedEvent",
  "namespace": "tenants.auth.events",
  "fields": [
    {"name": "event_id", "type": "string"},
    {"name": "event_name", "type": "string"},
    {"name": "occurred_at", "type": {"type": "long", "logicalType": "timestamp-millis"}},
    {"name": "payload", "type": {
      "type": "record",
      "name": "UserCreatedPayload",
      "fields": [
        {"name": "user_id", "type": "string"},
        {"name": "tenant_id", "type": "string"},
        {"name": "email", "type": "string"},
        {"name": "roles", "type": {"type": "array", "items": "string"}},
        {"name": "created_by", "type": "string"}]}}]}
```
## 7. Contratos Shared Adapter (Otros Módulos)
### 7.1 CentralUserReadOnlyRepository (Para Expediente, Gobernanza)
```php
// Interface compartida (solo lectura)
interface CentralUserReadOnlyRepositoryInterface
{
    public function findById(UserId $id): ?User;
    public function findByEmailAndTenant(Email $email, TenantId $tenantId): ?User;
    public function findByIdAndTenant(UserId $id, TenantId $tenantId): ?User;
    public function existsByEmailAndTenant(Email $email, TenantId $tenantId): bool;
    public function findAllByTenant(TenantId $tenantId, int $limit, int $offset): array;
    public function countByTenant(TenantId $tenantId): int;}
```
**Implementación:** `CentralUserReadOnlyRepository` usa conexiones `central` (PostgreSQL) con queries READ ONLY.

**Consumidores:**
- **Expediente Médico**: Validar permisos acceso expediente
- **Gobernanza/Auditoría**: Correlacionar eventos con usuario

## 8. Versionado y Compatibilidad
| Contrato                  | Versión Actual | Estrategia Versionado                                                    |
|---------------------------|----------------|--------------------------------------------------------------------------|
| **HTTP API**              | v1             | URL versioning `/v1/...` + headers `Accept: application/vnd.his.v1+json` |
| **Use Cases**             | 1.0            | Interface segregation + nuevos métodos = nueva interfaz                  |
| **Domain Events**         | 1.0            | Avro schema evolution (backward compatible)                              |
| **Kafka Topics**          | 1.0            | Topic per event type + schema registry                                   |
| **Repository Interfaces** | 1.0            | Métodos nuevos = nueva interfaz (ej. UserRepositoryInterfaceV2)          |

### Políticas de Compatibilidad
| Cambio                                      | Compatible | Acción Requerida                         |
|---------------------------------------------|------------|------------------------------------------|
| Agregar campo opcional a Request/Response   | ✅ Sí       | Nada                                     |
| Agregar campo obligatorio a Request         | ❌ No       | Nueva versión API (`/v2/`)               |
| Cambiar tipo de campo                       | ❌ No       | Nueva versión + migración                |
| Agregar evento Domain Event                 | ✅ Sí       | Consumidores ignoran campos desconocidos |
| Cambiar schema Avro (añadir campo opcional) | ✅ Sí       | Schema Registry valida compatibilidad    |
| Eliminar campo                              | ❌ No       | Deprecation period (2 versiones)         |
