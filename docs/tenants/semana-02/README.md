# Semana 02 — Proceso y Modelo de Diseño; Principios SOLID

## Módulo: Gestión de usuarios, tenants y endurecimiento de autenticación
**Flujo principal:** «Alta de usuario por tenant, autenticación segura y revocación de sesión»

---

## Entregables de la Semana 02

| Archivo                          | Tipo     | Descripción                                                                                                                                                                                                           |
|----------------------------------|----------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `rf-rnf-criterios-aceptacion.md` | Markdown | **RF/RNF + Criterios de Aceptación**: 15 RF, 15 RNF, 4 escenarios Gherkin completos, matriz trazabilidad UML Week 01                                                                                                  |
| `solid-srp-analisis.md`          | Markdown | **Análisis SRP**: Diagnóstico código actual (AuthController), diseño Clean Architecture por capas, 12+ clases con responsabilidad única, métricas comparativas, 2 ejemplos cambio concreto, plan migración Week 03-04 |

---

## Resumen Ejecutivo

### Requisitos Funcionales Clave (15)
- **RF-01 a RF-03**: Alta usuario por tenant con validaciones (tenant activo, cupo, email único, password policy, roles permitidos)
- **RF-04 a RF-06**: Login + MFA adaptativo (TOTP/WebAuthn) + JWT RS256 (15min) + Refresh Token opaco (7d, rotación)
- **RF-07**: Refresh Rotation + Reuse Detection → revocación total + alerta CRÍTICA
- **RF-08**: Blocklist Access Tokens (Redis) para revocación inmediata
- **RF-09**: Logout propio + Force-revoke admin + Evento `SessionRevokedEvent`
- **RF-10**: Aislamiento multi-tenant estricto (RLS PostgreSQL + middleware `X-Tenant-ID`)
- **RF-11 a RF-13**: Lockout progresivo, Single Session configurable, Device Fingerprinting
- **RF-14**: Auditoría inmutable (hash chain SHA-256) para 14 tipos de eventos seguridad
- **RF-15**: JIT Provisioning SSO (SAML/OIDC)

### Requisitos No Funcionales Clave (15)
- **RNF-01 a RNF-05**: Disponibilidad 99.99%, latencia login p95 ≤ 300ms, validación token p99 ≤ 50ms, revocación ≤ 100ms, throughput 500-2000 req/s
- **RNF-06 a RNF-09**: TLS 1.3, AES-256/Argon2id, auditoría append-only 7 años, evidencias 90d WORM
- **RNF-10**: OWASP ASVS L2, NIST 800-63B AAL2, ISO 27001 A.9
- **RNF-11 a RNF-15**: Escalabilidad K8s HPA, Observabilidad OpenTelemetry/Grafana, DR RPO=0/RTO<15min, MFA success ≥95%, WCAG 2.1 AA

### Criterios de Aceptación (Gherkin)
4 escenarios completos cubriendo:
1. **Alta usuario**: éxito, tenant inactivo, cupo excedido, email duplicado
2. **Login + MFA**: sin MFA (MEDICO), con MFA (TENANT_ADMIN), fallo 3 intentos → bloqueo 30min
3. **Refresh + Blocklist + Revocación**: rotación exitosa, reuse detection (ataque), logout, force-revoke admin
4. **Multi-tenant**: cross-tenant attempt bloqueado, RLS en queries

---

## Análisis SRP (Single Responsibility Principle)

### Estado Actual (Violaciones Detectadas)
El `AuthController` actual (**116 líneas**) mezcla **6 responsabilidades**:
1. Validación HTTP (`$request->validate`)
2. Reglas negocio (password policy, asignación roles, MFA)
3. Persistencia (Eloquent `User::create()`, queries)
4. Generación/validación JWT (`JWTAuth` facade)
5. Almacenamiento Redis (no implementado aún)
6. Respuesta HTTP (JSON, códigos de estado)

### Diseño Objetivo (Clean Architecture - 4 Capas)

| Capa               | Responsabilidad                                          | Clases Principales                                                                                                                                                                                                     |
|--------------------|----------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Presentation**   | Solo HTTP: binding, validación sintáctica, serialización | `AuthController`, `RegisterUserRequest`, `LoginRequest`, `AuthResponseTransformer`                                                                                                                                     |
| **Application**    | Orquesta casos de uso (use cases)                        | `RegisterUserUseCase`, `AuthenticateUserUseCase`, `RevokeSessionUseCase`, `RefreshTokenUseCase`                                                                                                                        |
| **Domain**         | Reglas puras, invariantes, contratos                     | Entities (`User`, `Tenant`, `Session`), Value Objects (`Email`, `Password`, `JwtClaims`), Domain Services (`PasswordPolicy`, `MfaPolicy`, `TokenFactory`, `RefreshTokenRotator`), Repository Interfaces, Domain Events |
| **Infrastructure** | Adaptadores tecnológicos                                 | `PDOUserRepository`, `RedisRefreshTokenRepository`, `JwtTokenService` (RS256), `BlocklistService`, `PdoAuditLogRepository`, `InMemoryUserRepository` (test double)                                                     |

### Métricas de Mejora (Verificables)

| Métrica                              | Antes                                 | Después                        | Mejora                       |
|--------------------------------------|---------------------------------------|--------------------------------|------------------------------|
| Líneas/clase promedio                | 116 (1 clase)                         | < 50 (12+ clases)              | -57%                         |
| Dependencias concretas en Domain     | 6                                     | 0                              | Inversión dependencias (DIP) |
| Complejidad ciclomática promedio     | ~12                                   | ~3-5                           | -60%                         |
| Testabilidad (unitarios puros)       | Difícil (mock Eloquent, JWT, Request) | Fácil (dobles para interfaces) | ✅                            |
| Cambio aislado (ej: password policy) | 3+ archivos                           | 1 archivo (`PasswordPolicy`)   | ✅                            |

### Ejemplos de Cambio Concreto

| Cambio                             | Antes (Archivos)                                         | Después (Archivos)                                               |
|------------------------------------|----------------------------------------------------------|------------------------------------------------------------------|
| Password policy: min 8 → 12 + HIBP | `AuthController`, `User` model, tests E2E                | `PasswordPolicy` (Domain) + `RegisterUserRequest` (Presentation) |
| JWT HS256 → RS256 + JWKS           | `AuthController`, `config/jwt.php`, `JwtAuth` middleware | `JwtTokenService` (Infra) + `TokenFactory` (Domain)              |

---

## Integración con Arquitectura Existente

| Componente Existente                                 | Ubicación                                           | Acción Requerida                                      |
|------------------------------------------------------|-----------------------------------------------------|-------------------------------------------------------|
| Tablas `central_users`, `hospital_memberships`       | `database/migrations/central/2026_08_11_000100_...` | **Respetar** - base del diseño Domain                 |
| Auditoría inmutable (`cross_hospital_access_audits`) | `database/migrations/central/2026_08_11_000300_...` | **Extender** patrón para `audit_log` seguridad        |
| Conexiones DB: `central`, `hospital` (PostgreSQL)    | `config/database.php`                               | **Usar** en PDO Repositories                          |
| JWT Config (actual HS256)                            | `config/jwt.php`                                    | **Migrar** a RS256 + JWKS en `JwtTokenService`        |
| Middleware `JwtAuth` + `TenantMiddleware`            | `app/Http/Middleware/`                              | **Refactorizar** para usar Domain Services            |
| `AuthController` (Laravel + Eloquent)                | `app/Http/Controllers/Api/V1/AuthController.php`    | **Sustituir** por Presentation delgada + UseCases     |
| Spatie Permission (roles/permissions)                | `config/permission.php`                             | **Integrar** via `Role` Value Object + Domain Service |

---

## Próximos Pasos (Week 03-04)

### Week 03: Diseño Arquitectónico + Código Base PHP 8.2+ Vanilla
- [ ] Crear estructura `app/Modules/Tenants/{Domain,Application,Infrastructure,Presentation}`
- [ ] Implementar Value Objects, Entities, Repository Interfaces, Domain Services
- [ ] Implementar Use Cases (Application)
- [ ] Implementar PDO + InMemory Repositories, JwtTokenService (RS256), BlocklistService
- [ ] Tests unitarios: Domain Services + UseCases (con InMemory)
- [ ] Test happy path, regla dominio, error persistencia

### Week 04: Arquitectura en Capas + Patrón Repository
- [ ] MVC entry point (router simple)
- [ ] Repository Interfaces + Adaptadores PDO e InMemory completos
- [ ] Controlador sin SQL ni reglas de negocio
- [ ] Análisis integración repositorio datos compartido (central_users)

---

## Referencias Rápidas

- **Diagramas UML Week 01**: `docs/cindy/week-01/gestion-usuarios-tenants/` (Casos de uso, Actividad, Secuencia)
- **Código Auth Actual**: `app/Http/Controllers/Api/V1/AuthController.php`
- **Middleware Tenant/JWT**: `app/Http/Middleware/{TenantMiddleware.php,JwtAuth.php}`
- **Config JWT**: `config/jwt.php`
- **Config Auth**: `config/auth.php`
- **Migraciones Central**: `database/migrations/central/`
- **Tests (futuro)**: `tests/Unit/Tenants/`, `tests/Feature/Tenants/`

