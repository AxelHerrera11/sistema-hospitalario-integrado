# Semana 02 — Aplicación de Principio SRP (Single Responsibility Principle)
**Módulo:** Gestión de usuarios, tenants y endurecimiento de autenticación
**Flujo:** «Alta de usuario por tenant, autenticación segura y revocación de sesión»
**Versión:** 1.0 | **Fecha:** 2026-10-18

---

## 1. Diagnóstico Actual (Estado "Antes" - Código Existente)

### 1.1 AuthController Actual — Violaciones SRP Detectadas

```php
// app/Http/Controllers/Api/V1/AuthController.php (116 líneas)
class AuthController extends Controller
{
    // VIOLACIÓN 1: Mezcla presentación (HTTP) + lógica de dominio + persistencia
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([...]);           // ← Presentación (validación HTTP)
        $tenant = $request->attributes->get('tenant');     // ← Infraestructura (middleware)
        $user = User::query()->create([...]);              // ← Persistencia (Eloquent directo)
        $user->assignRole('Recepcionista');                // ← Dominio (regla de negocio)
        $token = JWTAuth::fromUser($user);                 // ← Infraestructura (JWT lib)
        return response()->json([...]);                    // ← Presentación (respuesta HTTP)
    }

    // VIOLACIÓN 2: Login mezcla autenticación + consulta BD + JWT + respuesta HTTP
    public function login(Request $request): JsonResponse { ... }

    // VIOLACIÓN 3: Refresh token logic en controlador
    public function refresh(Request $request): JsonResponse { ... }

    // VIOLACIÓN 4: Logout + invalidation en controlador
    public function logout(Request $request): JsonResponse { ... }
}
```

### 1.2 Problemas Identificados

| Responsabilidad                                     | Dónde está HOY                     | Por qué viola SRP                         |
|-----------------------------------------------------|------------------------------------|-------------------------------------------|
| **Validación HTTP**                                 | Controlador (`$request->validate`) | Cambia si cambian reglas de entrada API   |
| **Reglas de negocio (password policy, roles, MFA)** | Controlador + Modelo User          | Cambia si cambian políticas de seguridad  |
| **Persistencia (create, query, hash)**              | Controlador + Eloquent Model       | Cambia si cambia esquema BD u ORM         |
| **Generación/validación JWT**                       | Controlador + Facade `JWTAuth`     | Cambia si cambia librería JWT o algoritmo |
| **Almacenamiento Refresh Token (Redis)**            | No implementado aún                | —                                         |
| **Auditoría eventos seguridad**                     | No implementado aún                | —                                         |
| **Respuesta HTTP (JSON, códigos)**                  | Controlador                        | Cambia si cambia contrato API             |
| **Rate limiting / Throttling**                      | No centralizado                    | —                                         |

---

## 2. Diseño "Después" — Separación por Responsabilidades (SRP)

### 2.1 Arquitectura en Capas (Clean Architecture Adaptada)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        PRESENTATION LAYER (HTTP)                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────────────────┐ │
│  │ AuthController  │  │ Request DTOs    │  │ Response Transformers       │ │
│  │ - Solo HTTP:    │  │ - Validate()    │  │ - toJson(), toArray()       │ │
│  │   bind, respond │  │ - Rules()       │  │ - No lógica negocio         │ │
│  └────────┬────────┘  └─────────────────┘  └─────────────────────────────┘ │
└───────────│────────────────────────────────────────────────────────────────┘
            │ DTOs (Value Objects)
┌───────────▼────────────────────────────────────────────────────────────────┐
│                        APPLICATION LAYER (Use Cases)                       │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────────────────┐ │
│  │ RegisterUser    │  │ Authenticate    │  │ RevokeSession               │ │
│  │ UseCase         │  │ UserUseCase     │  │ UseCase                     │ │
│  │ - Orquesta:     │  │ - Orquesta:     │  │ - Orquesta:                 │ │
│  │   tenant check  │  │   credentials   │  │   blocklist + redis DEL     │ │
│  │   policy check  │  │   MFA challenge │  │   event publish             │ │
│  │   user create   │  │   token gen     │  │   audit log                 │ │
│  │   event emit    │  │   audit log     │  │   (NO SQL, NO HTTP)         │ │
│  └────────┬────────┘  └────────┬────────┘  └──────────────┬──────────────┘ │
└───────────│─────────────────────│─────────────────────────│────────────────┘
            │                     │                         │
            ▼                     ▼                         ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                          DOMAIN LAYER (Reglas Puras)                        │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────────┐  │
│  │ Entities     │ │ Value Objects│ │ Domain Events│ │ Domain Services  │  │
│  │ - User       │ │ - Email      │ │ - UserCreated│ │ - PasswordPolicy │  │
│  │ - Tenant     │ │ - Password   │ │ - UserLoggedIn│ │ - MfaPolicy      │  │
│  │ - Session    │ │ - Role       │ │ - SessionRevoked│ │ - TokenFactory  │  │
│  │ - RefreshTok │ │ - JwtClaims  │ │ - MfaChallenged│ │ - RefreshRotator│  │
│  └──────────────┘ └──────────────┘ └──────────────┘ └──────────────────┘  │
│  ┌──────────────────────────────────────────────────────────────────────┐  │
│  │ Repository Interfaces (Contratos - Puertos)                           │  │
│  │ - UserRepositoryInterface    - TenantRepositoryInterface             │  │
│  │ - SessionRepositoryInterface - AuditLogRepositoryInterface           │  │
│  │ - RefreshTokenRepositoryInterface                                     │  │
│  └──────────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────┘
            │                     │                         │
            ▼                     ▼                         ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                      INFRASTRUCTURE LAYER (Adaptadores)                     │
│  ┌──────────────────┐ ┌──────────────────┐ ┌────────────────────────────┐  │
│  │ Persistence      │ │ Security         │ │ Messaging                  │  │
│  │ - PDOUserRepository     │ - JwtTokenService (RS256)          │  │
│  │ - PDOTenantRepository   │ - Argon2idHasher                    │  │
│  │ - RedisSessionRepo      │ - TotpVerifier / WebAuthnVerifier   │  │
│  │ - RedisRefreshTokenRepo │ - RefreshTokenRotator               │  │
│  │ - PdoAuditLogRepo       │ - BlocklistService (Redis)          │  │
│  └──────────────────┘ └──────────────────┘ └────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 2.2 Responsabilidades Asignadas por Clase

| Capa               | Clase                                                      | Responsabilidad Única (SRP)                          | Cambia si...                                 |
|--------------------|------------------------------------------------------------|------------------------------------------------------|----------------------------------------------|
| **Presentation**   | `AuthController`                                           | Binding HTTP → DTO → UseCase → Response              | Contrato API (rutas, códigos, formato JSON)  |
| **Presentation**   | `RegisterUserRequest` / `LoginRequest`                     | Reglas de validación entrada HTTP                    | Campos entrada, reglas validación sintáctica |
| **Presentation**   | `AuthResponseTransformer`                                  | Serialización DTO → JSON                             | Formato respuesta, versionado API            |
| **Application**    | `RegisterUserUseCase`                                      | Orquesta: tenant check + policy + create + event     | Flujo alta usuario (pasos, orden)            |
| **Application**    | `AuthenticateUserUseCase`                                  | Orquesta: credenciales + MFA + tokens + audit        | Flujo login (pasos, MFA opcional)            |
| **Application**    | `RevokeSessionUseCase`                                     | Orquesta: blocklist + redis DEL + event + audit      | Flujo revocación (alcance, eventos)          |
| **Application**    | `RefreshTokenUseCase`                                      | Orquesta: rotation + reuse detection + audit         | Política refresh (rotación, detección)       |
| **Domain**         | `User` (Entity)                                            | Invariantes: email único, roles válidos, status      | Reglas identidad usuario                     |
| **Domain**         | `Tenant` (Entity)                                          | Invariantes: activo, cupo, políticas MFA             | Reglas tenant                                |
| **Domain**         | `PasswordPolicy` (Domain Service)                          | Valida: longitud, complejidad, historial, HIBP       | Política contraseñas                         |
| **Domain**         | `MfaPolicy` (Domain Service)                               | Decide: ¿requiere MFA? ¿qué tipo?                    | Política MFA por rol/tenant                  |
| **Domain**         | `TokenFactory` (Domain Service)                            | Crea: AccessToken (JWT RS256), RefreshToken (opaco)  | Algoritmos, claims, TTLs                     |
| **Domain**         | `RefreshTokenRotator` (Domain Service)                     | Rota: invalida anterior, genera nuevo, detecta reuso | Algoritmo rotación, detección reuso          |
| **Domain**         | `UserCreated` / `UserLoggedIn` / `SessionRevoked` (Events) | Datos inmutables del evento                          | Schema evento auditoría                      |
| **Infrastructure** | `PDOUserRepository`                                        | Implementa `UserRepositoryInterface` con SQL/PDO     | Esquema BD, consultas SQL                    |
| **Infrastructure** | `InMemoryUserRepository`                                   | Implementa misma interfaz para tests                 | — (test double)                              |
| **Infrastructure** | `RedisRefreshTokenRepository`                              | CRUD refresh tokens en Redis (hash + TTL)            | Esquema Redis, TTLs                          |
| **Infrastructure** | `JwtTokenService`                                          | Firma/valida JWT RS256, JWKS rotation                | Librería JWT, claves, algoritmo              |
| **Infrastructure** | `BlocklistService`                                         | Redis SET blocklist:{jti} con TTL                    | Implementación blocklist                     |
| **Infrastructure** | `PdoAuditLogRepository`                                    | Append-only audit_log + hash chain                   | Esquema auditoría, hash algorithm            |

---

## 3. Comparación Antes/Después — Evidencia Verificable

### 3.1 Métricas de Acoplamiento y Cohesión

| Métrica                    | Antes (AuthController monolítico)                | Después (Capas SRP)                  | Mejora                   |
|----------------------------|--------------------------------------------------|--------------------------------------|--------------------------|
| **Líneas por clase**       | 116 (AuthController)                             | < 50 c/u (7+ clases)                 | ✅ -57%                   |
| **Dependencias concretas** | 6 (Request, User, JWTAuth, Hash, Role, Response) | 0 en Domain, 1-2 en App (interfaces) | ✅ Inversión dependencias |
| **Testabilidad**           | Difícil (mock Eloquent, JWTAuth, Request)        | Fácil (dobles para interfaces)       | ✅ Tests unitarios puros  |
| **Ciclomática (promedio)** | ~12 por método                                   | ~3-5 por método                      | ✅ -60%                   |
| **Cohesión (LCOM)**        | Baja (métodos tocan campos distintos)            | Alta (cada clase un propósito)       | ✅                        |
| **Cambio aislado**         | 1 cambio → toca controlador completo             | 1 cambio → 1 clase afectada          | ✅                        |

### 3.2 Ejemplo Concreto: Cambio de Política de Contraseña

**Escenario:** Negocio exige pasar de min 8 a min 12 caracteres + check HaveIBeenPwned.

| Enfoque                | Archivos a Modificar                                                                                            | Riesgo Regresión                           |
|------------------------|-----------------------------------------------------------------------------------------------------------------|--------------------------------------------|
| **Antes (Monolítico)** | `AuthController::register()`, `AuthController::login()` (validación), `User` model (mutator), Test E2E          | 🔴 Alto - toca HTTP, dominio, persistencia |
| **Después (SRP)**      | `PasswordPolicy` (Domain Service) + `RegisterUserRequest` (Presentation) + Tests unitarios `PasswordPolicyTest` | 🟢 Bajo - solo dominio + validación HTTP   |

### 3.3 Ejemplo Concreto: Migración JWT HS256 → RS256

**Escenario:** Seguridad exige claves asimétricas + JWKS rotation.

| Enfoque     | Archivos a Modificar                                                                      | Riesgo Regresión                              |
|-------------|-------------------------------------------------------------------------------------------|-----------------------------------------------|
| **Antes**   | `AuthController` (JWTAuth facade), `config/jwt.php`, Middleware `JwtAuth`                 | 🔴 Alto - disperso en 3+ archivos             |
| **Después** | `JwtTokenService` (Infrastructure) + `TokenFactory` (Domain) + `JwtConfig` (Value Object) | 🟢 Bajo - encapsulado en capa infraestructura |

---

## 4. Diagrama de Dependencias (Dirección Correcta)

```mermaid
graph TD
    subgraph Presentation
        CTRL[AuthController]
        REQ[Request DTOs]
        RES[Response Transformer]
    end

    subgraph Application
        UC1[RegisterUserUseCase]
        UC2[AuthenticateUserUseCase]
        UC3[RevokeSessionUseCase]
        UC4[RefreshTokenUseCase]
    end

    subgraph Domain
        ENT[Entities: User, Tenant, Session]
        VO[Value Objects: Email, Password, JwtClaims]
        DS[Domain Services: PasswordPolicy, MfaPolicy, TokenFactory, RefreshTokenRotator]
        EVT[Domain Events]
        REPO_INT[Repository Interfaces]
    end

    subgraph Infrastructure
        PDO_REPO[PDO Repositories]
        REDIS_REPO[Redis Repositories]
        JWT_SVC[JwtTokenService]
        BLOCKLIST[BlocklistService]
        AUDIT[AuditLogRepository]
    end

    CTRL --> REQ
    CTRL --> UC1
    CTRL --> UC2
    CTRL --> UC3
    CTRL --> UC4
    CTRL --> RES

    UC1 --> ENT
    UC1 --> DS
    UC1 --> REPO_INT
    UC1 --> EVT

    UC2 --> ENT
    UC2 --> DS
    UC2 --> REPO_INT
    UC2 --> EVT

    UC3 --> REPO_INT
    UC3 --> EVT

    UC4 --> DS
    UC4 --> REPO_INT
    UC4 --> EVT

    REPO_INT <|.. PDO_REPO
    REPO_INT <|.. REDIS_REPO
    DS --> JWT_SVC
    DS --> BLOCKLIST
    EVT --> AUDIT

    style CTRL fill:#FFEBEE,stroke:#C62828
    style UC1 fill:#E8F5E9,stroke:#2E7D32
    style UC2 fill:#E8F5E9,stroke:#2E7D32
    style DS fill:#E3F2FD,stroke:#1565C0
    style REPO_INT fill:#FFF3E0,stroke:#E65100
```

> **Regla de oro:** Las flechas **solo apuntan hacia adentro** (Presentation → Application → Domain ← Infrastructure). Domain **nunca** depende de Infrastructure.

---

## 5. Justificación de Decisiones de Diseño

| Decisión                                   | Justificación Arquitectónica                                                                                      |
|--------------------------------------------|-------------------------------------------------------------------------------------------------------------------|
| **Use Cases como clase por flujo**         | Cada flujo (register, login, revoke, refresh) tiene ciclo de vida y reglas distintas. Evita "God UseCase".        |
| **Domain Services para políticas**         | `PasswordPolicy`, `MfaPolicy`, `TokenFactory` encapsulan lógica que cambia por negocio, no por tecnología.        |
| **Repository Interfaces en Domain**        | Inversión de dependencias (DIP). Application y Domain no conocen PDO/Redis. Facilita test doubles.                |
| **Value Objects para primitivos**          | `Email`, `Password`, `JwtClaims` evitan "primitive obsession" y validan en construcción.                          |
| **Domain Events para auditoría**           | Desacopla caso de uso de persistencia de auditoría. Permite múltiples sinks (DB, SIEM, file).                     |
| **Infrastructure adapters por tecnología** | `PDOUserRepository` vs `InMemoryUserRepository` intercambiables. `JwtTokenService` aisla librería tymon/jwt-auth. |

---

## 6. Plan de Migración (Week 03-04)

| Paso | Acción                                                                                            | Entregable            |
|------|---------------------------------------------------------------------------------------------------|-----------------------|
| 1    | Crear estructura carpetas: `app/Modules/Tenants/{Domain,Application,Infrastructure,Presentation}` | Esqueleto módulo      |
| 2    | Definir Value Objects: `Email`, `Password`, `TenantId`, `UserId`, `JwtClaims`, `Role`             | Domain/ValueObjects   |
| 3    | Definir Entities: `User`, `Tenant`, `Session`, `RefreshToken`                                     | Domain/Entities       |
| 4    | Definir Repository Interfaces en Domain                                                           | Domain/Repositories   |
| 5    | Implementar Domain Services: `PasswordPolicy`, `MfaPolicy`, `TokenFactory`, `RefreshTokenRotator` | Domain/Services       |
| 6    | Implementar Application UseCases                                                                  | Application/UseCases  |
| 7    | Implementar Infrastructure: PDO + Redis Repositories, JwtTokenService, BlocklistService           | Infrastructure        |
| 8    | Refactorizar AuthController → delgado, solo HTTP                                                  | Presentation          |
| 9    | Tests unitarios Domain Services + UseCases (con InMemory repos)                                   | tests/Unit/Tenants    |
| 10   | Tests de integración PDO Repositories (Testcontainers PostgreSQL)                                 | tests/Feature/Tenants |

---

## 7. Conclusión

La aplicación estricta de **SRP** transforma el `AuthController` monolítico (116 líneas, 6 responsabilidades) en **12+ clases cohesivas**, cada una con **una razón para cambiar**. Esto permite:

1. **Testabilidad real**: Domain Services y UseCases probables sin BD, sin HTTP, sin JWT lib.
2. **Mantenibilidad**: Cambio de política password = 1 archivo (`PasswordPolicy`), no 3.
3. **Evolución segura**: Migración HS256→RS256, añadir WebAuthn, cambiar Redis por Valkey — tocando solo Infrastructure.
4. **Alineación con arquitectura existente**: Respeta tablas `central_users`/`hospital_memberships`, middleware `TenantMiddleware`, y config multi-db.

