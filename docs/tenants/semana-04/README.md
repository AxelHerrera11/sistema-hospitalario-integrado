# Semana 04 — Arquitectura en Capas y Patrón Repository

## Módulo: Gestión de usuarios, tenants y endurecimiento de autenticación
**Flujo:** «Alta de usuario por tenant, autenticación segura y revocación de sesión»
**Versión:** 1.0 | **Fecha:** 2026-10-18

---

## Entregables de Documentación (`docs/tenants/semana-04/`)

| Archivo                             | Tipo     | Descripción                                                                                                                                                                                                                                                         |
|-------------------------------------|----------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `arquitectura-capas-repositorio.md` | Markdown | **Documento principal**: Visión capas MVC+Clean, responsabilidades, 5 Repository Interfaces, 3 adaptadores (PDO, Redis, InMemory), controlador delgado, entry point MVC, análisis repositorio compartido (`central_users`), objetos reutilizables, métricas calidad |

---

## Código Fuente Implementado (`app/Modules/Tenants/`)

### Nuevos Archivos Semana 04

```
app/Modules/Tenants/
├── Infrastructure/
│   └── Persistence/
│       ├── InMemory/                    # ✅ NUEVO - Adaptadores para tests
│       │   ├── InMemoryUserRepository.php
│       │   ├── InMemoryTenantRepository.php
│       │   ├── InMemorySessionRepository.php
│       │   ├── InMemoryRefreshTokenRepository.php
│       │   └── InMemoryAuditLogRepository.php
│       └── Shared/                      # ✅ NUEVO - Repositorio compartido read-only
│           └── CentralUserReadOnlyRepository.php
├── Presentation/
│   ├── Middleware/                      # ✅ NUEVO - Pipeline HTTP
│   │   ├── TenantMiddleware.php         # Valida X-Tenant-ID header
│   │   ├── JwtAuthMiddleware.php        # Valida JWT RS256 + blocklist
│   │   └── RateLimitMiddleware.php      # Rate limiting por email/IP
│   ├── Router.php                       # ✅ NUEVO - FastRoute + MiddlewareChain
│   └── Controllers/
│       ├── AdminUserController.php      # ✅ NUEVO - Admin user management
│       └── AdminTenantController.php    # ✅ NUEVO - Admin tenant management
├── Application/
│   ├── Commands/
│   │   └── RegisterTenantCommand.php    # ✅ NUEVO
│   ├── Results/
│   │   └── RegisterTenantResult.php     # ✅ NUEVO
│   └── UseCases/
│       └── RegisterTenantUseCase.php    # ✅ NUEVO
└── ModuleBootstrap.php                  # ✅ ACTUALIZADO - DI completo
```

### Entry Point HTTP
```
public/
└── index.php                            # ✅ NUEVO - Entry point MVC con DI, Router, Middlewares
```

---

## Resumen de Implementación

### 1. Arquitectura en Capas (MVC + Clean Architecture)

| Capa               | Responsabilidad                                      | Archivos Clave                                                                                        |
|--------------------|------------------------------------------------------|-------------------------------------------------------------------------------------------------------|
| **Presentation**   | HTTP: routing, binding, serialización, middlewares   | `Router.php`, `AuthController.php`, `Admin*Controller.php`, `Middleware/*`                            |
| **Application**    | Orquesta Use Cases, coordina Domain Services + Repos | `RegisterUserUseCase.php`, `AuthenticateUserUseCase.php`, `RegisterTenantUseCase.php`                 |
| **Domain**         | Reglas puras, invariantes, contratos (interfaces)    | `Entities/`, `ValueObjects/`, `Services/`, `Events/`, `Repositories/*.php`                            |
| **Infrastructure** | Adaptadores tecnológicos                             | `Persistence/PDO/`, `Persistence/Redis/`, `Persistence/InMemory/`, `Security/`, `Audit/`, `External/` |

### 2. Patrón Repository - 3 Implementaciones

| Interfaz (Domain)                 | PDO (Producción)        | Redis (Producción)            | InMemory (Tests)                 |
|-----------------------------------|-------------------------|-------------------------------|----------------------------------|
| `UserRepositoryInterface`         | `PDOUserRepository`     | —                             | `InMemoryUserRepository`         |
| `TenantRepositoryInterface`       | `PDOTenantRepository`   | —                             | `InMemoryTenantRepository`       |
| `SessionRepositoryInterface`      | `PDOSessionRepository`  | `RedisSessionRepository`      | `InMemorySessionRepository`      |
| `RefreshTokenRepositoryInterface` | —                       | `RedisRefreshTokenRepository` | `InMemoryRefreshTokenRepository` |
| `AuditLogRepositoryInterface`     | `PdoAuditLogRepository` | —                             | `InMemoryAuditLogRepository`     |

### 3. Controlador Delgado - Verificación de Cumplimiento

```php
// AuthController.php - SOLO HTTP
public function register(Request $request, Response $response): Response
{
    $data = RegisterUserRequest::fromRequest($request);  // Binding HTTP → DTO
    $command = new RegisterUserCommand(...);             // DTO → Command
    $result = $this->registerUseCase->execute($command); // Use Case ejecuta lógica
    return $this->transformer->transformRegister($response, $result, 201); // Result → HTTP
}
```

**✅ Verificado:** Cero SQL, cero reglas de negocio, cero crypto, cero Redis en controlador.

### 4. Pipeline MVC - Middlewares

| Middleware            | Orden | Función                                                                         |
|-----------------------|-------|---------------------------------------------------------------------------------|
| `TenantMiddleware`    | 1     | Valida `X-Tenant-ID` header (UUID), inyecta en request                          |
| `RateLimitMiddleware` | 2     | Limita intentos login/register/mfa por email+IP (20/15min)                      |
| `JwtAuthMiddleware`   | 3     | Valida JWT RS256, verifica blocklist, inyecta claims, valida tenant consistency |

### 5. Repositorio Compartido - `central_users` / `hospital_memberships`

#### Esquema Existente (Migración Central)
```sql
central_users: user_id, subject, email, display_name, status, metadata, ...
hospital_memberships: membership_id, user_id, hospital_id, role_code, status, valid_from, valid_to, ...
```

#### Estrategia de Integración Multi-Módulo

| Módulo                   | Acceso                          | Patrón                                      |
|--------------------------|---------------------------------|---------------------------------------------|
| **Tenants & Auth**       | Owner (CRUD completo)           | `PDOUserRepository` + `PDOTenantRepository` |
| **Expediente Médico**    | Read-only (validar permisos)    | `CentralUserReadOnlyRepository`             |
| **Gobernanza/Auditoría** | Read-only (correlación eventos) | `CentralUserReadOnlyRepository`             |

#### Adapter Read-Only para Otros Módulos
```php
// Infrastructure/Persistence/Shared/CentralUserReadOnlyRepository.php
final class CentralUserReadOnlyRepository implements UserRepositoryInterface
{
    public function save(User $user): void
    {
        throw new \BadMethodCallException('Solo lectura - use PDOUserRepository del módulo Tenants');
    }
    // ... solo métodos de lectura implementados
}
```

#### Reglas de Gobernanza
- **Single Writer**: Solo módulo Tenants & Auth escribe en tablas centrales
- **RLS Enforcement**: PostgreSQL Row Level Security por `hospital_id`
- **Schema Changes**: Requieren RFC + migración coordinada en `database/migrations/central/`
- **Audit Trail**: Todo acceso desde otros módulos se audita en `cross_hospital_access_audits`

### 6. Objetos Reutilizables Identificados

| Objeto                                                                                           | Ubicación                              | Reutilizable Por            |
|--------------------------------------------------------------------------------------------------|----------------------------------------|-----------------------------|
| Value Objects (Email, Password, TenantId, UserId, SessionId, JwtClaims, Role, DeviceFingerprint) | `Domain/ValueObjects/`                 | Todos los módulos           |
| Domain Events (UserCreated, UserLoggedIn, SessionRevoked, ...)                                   | `Domain/Events/`                       | Expediente, Gobernanza, QA  |
| Repository Interfaces                                                                            | `Domain/Repositories/`                 | Expediente, Gobernanza      |
| Domain Services (PasswordPolicy, MfaPolicy, TokenFactory, RefreshTokenRotator)                   | `Domain/Services/`                     | Expediente, Gobernanza      |
| InMemory Repositories                                                                            | `Infrastructure/Persistence/InMemory/` | Tests de todos los módulos  |
| Argon2idHasher                                                                                   | `Domain/Services/`                     | Todos los módulos           |
| JwtTokenService (RS256)                                                                          | `Infrastructure/Security/`             | API Gateway, microservicios |
| BlocklistService                                                                                 | `Infrastructure/Persistence/Redis/`    | API Gateway, microservicios |
| EventPublisher                                                                                   | `Infrastructure/Audit/`                | Todos los módulos           |

---

## Cómo Ejecutar

### Desarrollo Local
```bash
# 1. Configurar variables de entorno
cp .env.example .env
# Editar .env con credenciales DB, Redis, JWT keys

# 2. Instalar dependencias
composer install

# 3. Ejecutar migraciones (BD central + hospital)
php artisan migrate --path=database/migrations/central
php artisan migrate --path=database/migrations/hospital

# 4. Iniciar servidor PHP built-in (desarrollo)
php -S localhost:8080 -t public

# 5. Probar endpoints
curl -X POST http://localhost:8080/auth/login \
  -H "X-Tenant-ID: 550e8400-e29b-41d4-a716-446655440000" \
  -H "Content-Type: application/json" \
  -d '{"email":"test@hospital.com","password":"SecurePass123!"}'
```

### Tests
```bash
# Tests unitarios (InMemory - rápidos, sin BD)
./vendor/bin/phpunit tests/Unit/Tenants

# Tests de integración (requieren Testcontainers PostgreSQL + Redis)
./vendor/bin/phpunit tests/Feature/Tenants

# Con cobertura
./vendor/bin/phpunit --coverage-html coverage tests/Unit/Tenants
```

### Renderizar Diagramas
```bash
# Los diagramas C4/UML están en docs/tenants/semana-03/
java -jar plantuml.jar docs/tenants/semana-03/c4-architecture.puml
java -jar plantuml.jar docs/tenants/semana-03/c4-containers.puml
# ... etc
```

---

## Métricas de Calidad Verificables

| Métrica                        | Objetivo       | Verificación                                                                              |
|--------------------------------|----------------|-------------------------------------------------------------------------------------------|
| **Ciclos dependencias**        | 0              | `deptrac analyze`                                                                         |
| **Domain → Infrastructure**    | 0 dependencias | Static analysis                                                                           |
| **Controlador con SQL/Reglas** | 0              | Code review + `grep -r "PDO\|prepare\|JWTAuth\|Hash::" app/Modules/Tenants/Presentation/` |
| **Cobertura Domain Services**  | ≥ 90%          | `phpunit --coverage tests/Unit/Tenants/Domain/Services`                                   |
| **Cobertura Use Cases**        | ≥ 80%          | `phpunit --coverage tests/Feature/Tenants`                                                |
| **Tests InMemory pasan**       | 100%           | `phpunit tests/Unit/Tenants`                                                              |
| **Tiempo unit tests**          | < 30s          | CI pipeline                                                                               |

