# Semana 06 — Primera Evaluación Parcial
## 📋 Estructura de la Presentación (6-8 Diapositivas)
### Diapositiva 1: Portada
```
┌─────────────────────────────────────────────────────────────────────────────┐
│         PRIMERA EVALUACIÓN PARCIAL - SEMANA 06                              │
│                                                                             │
│    Módulo: Gestión de Usuarios, Tenants y Endurecimiento de Autenticación   │
│    Flujo: "Alta de usuario por tenant, autenticación segura y               │
│           revocación de sesión"                                             │
│                                                                             │
│    Arquitecto: Cindy | Rol: Software Lead / System Designer / SQA-UX        │
│    Fecha: 2026-10-18 | Versión: 1.0                                         │
│                                                                             │
│    Sistema Hospitalario Integrado - SistenasII-2026-G3                      │
└─────────────────────────────────────────────────────────────────────────────┘
```
### Diapositiva 2: Contexto y Actores (Trazabilidad Requisitos → UML)
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  CONTEXTO DEL SISTEMA - ACTORES Y FRONTERAS                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│  ACTORES PRIMARIOS:                                                         │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────────────────┐  │
│  │ 👨‍⚕️ Admin Tenant │  │ 🏢 Platform Admin│  │ 👤 Usuario Final          │  │
│  │ (TENANT_ADMIN)  │  │ (PLATFORM_ADMIN) │  │ (MEDICO/ENFERMERO/PACIENTE)│  │
│  └─────────────────┘  └─────────────────┘  └─────────────────────────────┘  │
│  SISTEMAS EXTERNOS:                                                         │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────────┐    │
│  │ SSO Provider │ │ HaveIBeenPwned│ │ SIEM/SOC     │ │ Email Service   │    │
│  │ (SAML/OIDC)  │ │ (Password)   │ │ (Alertas)    │ │ (Notificaciones) │    │
│  └──────────────┘ └──────────────┘ └──────────────┘ └──────────────────┘    │
│  FLUJO PRINCIPAL: "Alta usuario → Login+MFA → Tokens → Revocación"          │
└─────────────────────────────────────────────────────────────────────────────┘
```
**Trazabilidad a UML (Semana 01):**
- Casos de Uso: 18 UC mapeados a 4 actores + 3 sistemas
- Actividades: 5 swimlanes paralelos (fork/join)
- Secuencias: 6 grupos (Creación, Login+MFA, Refresh, Revocación, Validación, Idle)
### Diapositiva 3: Requisitos Funcionales y No Funcionales (RF/RNF)
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  REQUISITOS - RESUMEN EJECUTIVO                                             │
├─────────────────────────────────────────────────────────────────────────────┤
│  RF CRÍTICOS (15):                                                          │
│  ✅ RF-01  Alta usuario por tenant (validación cupo, email único)           │
│  ✅ RF-02  Password Policy (12 chars, complejidad, historial 5, HIBP)       │
│  ✅ RF-03  Roles por tenant (TENANT_ADMIN, MEDICO, ENFERMERO, PACIENTE)     │
│  ✅ RF-04  Autenticación credenciales + tenant validation                   │
│  ✅ RF-05  MFA Adaptativo (TOTP/WebAuthn) - TENANT_ADMIN/AUDITOR siempre    │
│  ✅ RF-06  JWT RS256 (15min) + Refresh Opaco (7d, rotación)                 │
│  ✅ RF-07  Refresh Rotation + Reuse Detection → Revocación total + alerta   │
│  ✅ RF-08  Blocklist Access Tokens (Redis O(1))                             │
│  ✅ RF-09  Revocación: Logout propio + Force-revoke admin + Evento          │
│  ✅ RF-10  Aislamiento Multi-tenant (RLS PostgreSQL + X-Tenant-ID)          │
│  ✅ RF-11  Lockout progresivo (5→15min, 10→1h, 20→24h)                      │
│  ✅ RF-12  Single Session Policy (configurable por tenant)                  │
│  ✅ RF-13  Device Fingerprinting (trust score, alerta dispositivo nuevo)    │
│  ✅ RF-14  Auditoría Inmutable (14 eventos, hash chain SHA-256)             │
│  ✅ RF-15  JIT Provisioning SSO (SAML/OIDC claim mapping)                   │
│                                                                             │
│  RNF CRÍTICOS (15):                                                         │
│  ✅ RNF-01 Disponibilidad 99.99% | RNF-02 Login p95 ≤ 300ms                 │
│  ✅ RNF-03 Validación token p99 ≤ 50ms | RNF-04 Revocación ≤ 100ms          │
│  ✅ RNF-05 Throughput 500-2000 req/s | RNF-06 TLS 1.3 + HSTS                │
│  ✅ RNF-07 AES-256/Argon2id | RNF-08 Auditoría append-only 7 años           │
│  ✅ RNF-09 Evidencias 90d WORM | RNF-10 OWASP ASVS L2 + NIST 800-63B AAL2   │
│  ✅ RNF-11 K8s HPA | RNF-12 OpenTelemetry/Grafana | RNF-13 RPO=0/RTO<15min  │
│  ✅ RNF-14 MFA success ≥95% | RNF-15 WCAG 2.1 AA                            │
└─────────────────────────────────────────────────────────────────────────────┘
```
**Criterios de Aceptación (Gherkin - 4 escenarios completos):**
1. **Alta Usuario**: Éxito, Tenant inactivo, Cupo excedido, Email duplicado
2. **Login+MFA**: Sin MFA (MEDICO), Con MFA (TENANT_ADMIN), 3 fallos → bloqueo 30min
3. **Refresh+Blocklist+Revocación**: Rotación, Reuse Detection (ataque), Logout, Force-revoke
4. **Multi-tenant**: Cross-tenant bloqueado, RLS en queries
### Diapositiva 4: Principio SOLID - SRP Aplicado (Antes/Después)
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  PRINCIPIO SRP - SINGLE RESPONSIBILITY PRINCIPLE                            │
├─────────────────────────────────────────────────────────────────────────────┤
│  ❌ ANTES: AuthController MONOLÍTICO (116 líneas, 6 responsabilidades)     │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │ class AuthController {                                              │    │
│  │   register()  → HTTP + Validación + Eloquent + Roles + JWT + Response│   │
│  │   login()     → HTTP + Credenciales + BD + MFA + JWT + Response     │    │
│  │   refresh()   → HTTP + Redis + Rotación + JWT + Response            │    │
│  │   logout()    → HTTP + Blocklist + Redis + Response                 │    │
│  │ }                                                                   │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│  Problemas: Acoplamiento alto, difícil testear, cambio = tocar todo         │
│                                                                             │
│  ✅ DESPUÉS: CLEAN ARCHITECTURE - 4 CAPAS, 12+ CLASES COHESIVAS            │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │ PRESENTATION        │ AuthController (solo HTTP)                    │    │
│  │                     │ RegisterUserRequest, LoginRequest, ...        │    │
│  │                     │ AuthResponseTransformer                       │    │
│  ├─────────────────────────────────────────────────────────────────────┤    │
│  │ APPLICATION         │ RegisterUserUseCase, AuthenticateUserUseCase  │    │
│  │                     │ RefreshTokenUseCase, RevokeSessionUseCase     │    │
│  ├─────────────────────────────────────────────────────────────────────┤    │
│  │ DOMAIN              │ Entities: User, Tenant, Session, RefreshToken │    │
│  │                     │ VOs: Email, Password, JwtClaims, Role...      │    │
│  │                     │ Services: PasswordPolicy, MfaPolicy,          │    │
│  │                     │           TokenFactory, RefreshTokenRotator   │    │
│  │                     │ Events: UserCreated, UserLoggedIn, ...        │    │
│  │                     │ Repositories: Interfaces (Puertos)            │    │
│  ├─────────────────────────────────────────────────────────────────────┤    │
│  │ INFRASTRUCTURE      │ PDO: User, Tenant, Session                    │    │
│  │                     │ Redis: RefreshToken, Session, Blocklist...    │    │
│  │                     │ Security: JwtTokenService(RS256), Totp,       │    │
│  │                     │           WebAuthn, Argon2idHasher            │    │
│  │                     │ InMemory: 5 Repos para tests unitarios        │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│  MÉTRICAS DE MEJORA VERIFICABLES:                                           │
│  ┌─────────────────────┬──────────┬──────────┬────────┐                     │
│  │ Métrica             │ Antes    │ Después  │ Mejora │                     │
│  ├─────────────────────┼──────────┼──────────┼────────┤                     │
│  │ Líneas/clase prom.  │ 116      │ < 50     │ -57%   │                     │
│  │ Dependencias conc.  │ 6        │ 0 (Domain)│ -100% │                     │
│  │ Complejidad ciclom. │ ~12      │ ~3-5     │ -60%   │                     │
│  │ Testabilidad        │ Difícil  │ Fácil    │ ✅    │                     │
│  │ Cambio aislado      │ 3+ archivos│ 1 archivo│ ✅  │                     │
│  └─────────────────────┴──────────┴──────────┴────────┘                     │
└─────────────────────────────────────────────────────────────────────────────┘
```
**Evidencia:** `docs/tenants/semana-02/solid-srp-analisis.md` + Código en `app/Modules/Tenants/`
### Diapositiva 5: Arquitectura en Capas y Patrón Repository
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  ARQUITECTURA EN CAPAS + PATRÓN REPOSITORY                                  │
├─────────────────────────────────────────────────────────────────────────────┤
│  DEPENDENCIAS (Dirección Correcta - Hacia Adentro):                         │
│                                                                             │
│  Presentation ──► Application ──► Domain ◄─── Infrastructure                │
│       │                │            ▲              │                        │
│       ▼                ▼            │              ▼                        │
│  HTTP/DTOS        Use Cases    Interfaces      Adapters                     │
│  Controllers    Orchestration  (Ports)       PDO / Redis                    │
│  Transformers     DTOs         Domain Svcs    InMemory (Tests)              │
│                                                                             │
│  REPOSITORY INTERFACES (Domain - 5 Puertos):                                │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │ UserRepositoryInterface      → findByEmailAndTenant, save, count    │    │
│  │ TenantRepositoryInterface    → findById, save, quota check          │    │
│  │ SessionRepositoryInterface   → findByUserId, findIdle, deleteAll    │    │
│  │ RefreshTokenRepositoryInterface→ findByUserIdAndDevice, rotate      │    │
│  │ AuditLogRepositoryInterface  → log(DomainEvent), queries            │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│  3 IMPLEMENTACIONES (Adaptadores):                                          │
│  ┌──────────────┬──────────────┬────────────────────────────────────────┐   │
│  │ PDO (Prod)   │ Redis (Prod) │ InMemory (Tests)                       │   │
│  ├──────────────┼──────────────┼────────────────────────────────────────┤   │
│  │ PostgreSQL   │ Redis Cluster│ Arrays en memoria                      │   │
│  │ central_users│ refresh:{uid}:{dev}│ 5 repos completos                │   │
│  │ hospital_mem.│ session:{sid}│ Rápidos, determinísticos               │   │
│  │ audit_log    │ blocklist:{jti}│ Sin dependencias externas            │   │
│  └──────────────┴──────────────┴────────────────────────────────────────┘   │
│  REPOSITORIO COMPARTIDO (central_users / hospital_memberships):             │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │ Owner (Write): Módulo Tenants & Auth                                │    │
│  │ Readers (Read): Expediente Médico, Gobernanza                       │    │
│  │ Adapter: CentralUserReadOnlyRepository (lanza excepción en write)   │    │
│  │ Gobernanza: Single Writer + RLS + CDC Debezium para sync            │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│  EVIDENCIA: `docs/tenants/semana-04/arquitectura-capas-repositorio.md`      │
│  CÓDIGO: `app/Modules/Tenants/{Domain,Application,Infrastructure,Presentation}`│
└─────────────────────────────────────────────────────────────────────────────┘
```
### Diapositiva 6: Contrato API y Evaluación Microservicio
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  CONTRATO API REST + EVALUACIÓN MICROSERVICIO                               │
├─────────────────────────────────────────────────────────────────────────────┤
│  CONTRATO OPENAPI 3.1 (Semana 05):                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │ 25 Endpoints | 40+ Schemas | Security: Bearer + Tenant Header       │    │
│  ├─────────────────────────────────────────────────────────────────────┤    │
│  │ POST   /auth/register      → 201 Created (Admin)                    │    │
│  │ POST   /auth/login         → 200 OK + Tokens / 200 MFA Challenge    │    │
│  │ POST   /auth/mfa           → 200 OK + Tokens                        │    │
│  │ POST   /auth/refresh       → 200 OK (Rotación + Reuse Detection)    │    │
│  │ POST   /auth/logout        → 200 OK (Revocación propia)             │    │
│  │ GET    /admin/users        → 200 Paginado (TENANT_ADMIN)            │    │
│  │ POST   /admin/users        → 201 Created                            │    │
│  │ PATCH  /admin/users/{id}   → 200 OK (Roles, status, MFA)            │    │
│  │ DELETE /admin/users/{id}/sessions → 200 Force-Revoke (Admin)        │    │
│  │ GET/POST /admin/tenants    → PLATFORM_ADMIN only                    │    │
│  │ GET    /.well-known/jwks.json → Claves públicas RS256               │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│  SEGURIDAD: BearerAuth (JWT RS256) + TenantHeader (X-Tenant-ID obligatorio) │
│  RATE LIMIT: 20 req/15min por email+IP en /auth/*                           │
│                                                                             │
│  ────────────────────────────────────────────────────────────────────────   │
│                                                                             │
│  EVALUACIÓN MICROSERVICIO - DECISIÓN: MANTENER MONOLITO MODULAR             │
│  ┌──────────────┬────────────┬──────────────────────────────────────────┐   │
│  │ Servicio     │ Score      │ Veredicto                                │   │
│  ├──────────────┼────────────┼──────────────────────────────────────────┤   │
│  │ Audit        │ 8.95/10    │ 🟢 Candidato #1 (Event-driven, Compliance)│  │
│  │ Auth         │ 6.35/10    │ 🟡 Candidato #2 (Solo si triggers)       │   │
│  │ Tenant       │ 5.65/10    │ 🔴 No separar (CRUD simple)              │   │
│  │ User         │ 4.55/10    │ 🔴 No separar (Acoplamiento transaccional)│  │
│  └──────────────┴────────────┴──────────────────────────────────────────┘   │
│  TRIGGERS MEDIBLES PARA FUTURA EXTRACCIÓN:                                  │
│  □ Throughput Auth > 5,000 req/s sostenido                                  │
│  □ Latencia p99 token validation > 100ms                                    │
│  □ Deployment frequency mismatch (Auth >10/día vs resto <1/semana)          │
│  □ Equipo > 15 devs con ownership separado                                  │
│  □ Incidentes cross-module > 30%                                            │
│                                                                             │
│  CONTRATOS PREPARADOS (ROI INMEDIATO SIN EXTRACCIÓN):                       │
│  ✅ OpenAPI 3.1 | ✅ gRPC ValidateToken | ✅ Kafka Events (Avro)           │
│  ✅ CDC Debezium | ✅ JWKS Rotation | ✅ Pact Contracts (pendiente)        │
└─────────────────────────────────────────────────────────────────────────────┘
```
**Evidencia:** `docs/tenants/semana-05/{openapi.yaml, client-server-architecture.md, microservice-evaluation.md}`
### Diapositiva 7: Diagrama Trazable (Requisitos → Código → Tests)
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  MATRIZ TRAZABLE: REQUISITOS → CÓDIGO → TESTS                               │
├─────────────────────────────────────────────────────────────────────────────┤
│  ┌─────────┬──────────────────────┬──────────────────────┬───────────────┐  │
│  │ RF/RNF  │ Código Fuente        │ Tests                │ Evidencia     │  │
│  ├─────────┼──────────────────────┼──────────────────────┼───────────────┤  │
│  │ RF-01   │ RegisterUserUseCase  │ RegisterUserFeature  │ Semana 03-04  │  │
│  │ Alta    │ PDOUserRepository    │ Test (5 escenarios)  │ Feature Tests │  │
│  ├─────────┼──────────────────────┼──────────────────────┼───────────────┤  │
│  │ RF-02   │ PasswordPolicy       │ PasswordPolicyTest   │ Semana 03     │  │
│  │ Passwd  │ (Domain Service)     │ (Unit - 8 casos)     │ Unit Tests    │  │
│  ├─────────┼──────────────────────┼──────────────────────┼───────────────┤  │
│  │ RF-04   │ AuthenticateUser     │ LoginMfaFeatureTest  │ Semana 03-04  │  │
│  │ Login   │ UseCase + TotpVerifier│ (4 escenarios)      │ Feature Tests │  │
│  ├─────────┼──────────────────────┼──────────────────────┼───────────────┤  │
│  │ RF-05   │ MfaPolicy +          │ MfaPolicyTest        │ Semana 03     │  │
│  │ MFA     │ TotpVerifier         │ (7 casos roles/pol)  │ Unit Tests    │  │
│  ├─────────┼──────────────────────┼──────────────────────┼───────────────┤  │
│  │ RF-06   │ JwtTokenService      │ TokenFactoryTest     │ Semana 03     │  │
│  │ JWT     │ (RS256 + JWKS)       │ (Unit - 3 casos)     │ Unit Tests    │  │
│  ├─────────┼──────────────────────┼──────────────────────┼───────────────┤  │
│  │ RF-07   │ RefreshTokenRotator  │ RefreshTokenRotatorT │ Semana 03     │  │
│  │ Refresh │ + Reuse Detection    │ est (4 casos)        │ Unit Tests    │  │
│  ├─────────┼──────────────────────┼──────────────────────┼───────────────┤  │
│  │ RF-08   │ RedisBlocklistRepo   │ RefreshTokenFeatureT │ Semana 04     │  │
│  │ Blocklst│ + JwtAuthMiddleware  │ est (Reuse Detection)│ Feature Tests │  │
│  ├─────────┼──────────────────────┼──────────────────────┼───────────────┤  │
│  │ RF-09   │ RevokeSessionUseCase │ RevokeSessionFeature │ Semana 04     │  │
│  │ Revoke  │ + BlocklistService   │ Test (3 escenarios)  │ Feature Tests │  │
│  ├─────────┼──────────────────────┼──────────────────────┼───────────────┤  │
│  │ RF-10   │ TenantMiddleware     │ MultiTenantIsolation │ Semana 04     │  │
│  │ Multi-  │ + RLS PostgreSQL     │ FeatureTest (3 casos)│ Feature Tests │  │
│  ├─────────┼──────────────────────┼──────────────────────┼───────────────┤  │
│  │ RF-14   │ PdoAuditLogRepo      │ InMemoryAuditLogRepo │ Semana 03-04  │  │
│  │ Audit   │ (Hash Chain SHA-256) │ Test (verifyHashChain)│ Unit Tests   │  │
│  ├─────────┼──────────────────────┼──────────────────────┼───────────────┤  │
│  │ RNF-02  │ k6 Load Tests        │ LoginMfaFeatureTest  │ Semana 05     │  │
│  │ Login   │ (configurado)        │ + k6 scripts         │ (pendiente)   │  │
│  ├─────────┼──────────────────────┼──────────────────────┼───────────────┤  │
│  │ RNF-08  │ PdoAuditLogRepo      │ InMemoryAuditLogRepo │ Semana 03     │  │
│  │ Inmutab │ (Trigger + Hash)     │ Test verifyHashChain │ Unit Tests    │  │
│  ├─────────┼──────────────────────┼──────────────────────┼───────────────┤  │
│  │ RNF-15  │ AuthResponseTransf.  │ (pendiente axe-core) │ Semana 08+    │  │
│  │ WCAG    │ + Request DTOs       │                      │               │  │
│  └─────────┴──────────────────────┴──────────────────────┴───────────────┘  │
│  COBERTURA ACTUAL:                                                          │
│  • Domain Services: ~90% (PasswordPolicy, MfaPolicy, TokenFactory,          │
│    RefreshTokenRotator, DeviceTrustService, Argon2idHasher)                 │
│  • Use Cases: ~80% (RegisterUser, AuthenticateUser, RefreshToken,           │
│    RevokeSession - con InMemory repos)                                      │
│  • Repositories: ~70% (InMemory 100%, PDO pendiente Testcontainers)         │
│  • Controllers: ~60% (Feature tests cubren happy path + errores)            │
│                                                                             │
│  EVIDENCIA: `tests/Unit/Tenants/` + `tests/Feature/Tenants/`                │
└─────────────────────────────────────────────────────────────────────────────┘
```
### Diapositiva 8: Cambio Práctico Defendido + Matriz Decisión→Evidencia
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  CAMBIO PRÁCTICO DEFENDIDO: "¿Qué pasa si cambiamos RS256 → ES256?"         │
├─────────────────────────────────────────────────────────────────────────────┤
│  ESCENARIO: Equipo de seguridad solicita migración de RS256 (RSA) a         │
│  ES256 (ECDSA P-256) para claves más pequeñas, firma más rápida,            │
│  y mejor rendimiento en dispositivos móviles/embebidos.                     │
│                                                                             │
│  ANÁLISIS DE IMPACTO CON ARQUITECTURA ACTUAL:                               │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │ CAPAS AFECTADAS:                                                    │    │
│  ├─────────────────────────────────────────────────────────────────────┤    │
│  │ DOMAIN (0 cambios)                                                  │    │
│  │   • TokenFactory (Interface) - Sin cambios                          │    │
│  │   • JwtClaims (Value Object) - Sin cambios                          │    │
│  │   • AccessToken (Value Object) - Sin cambios                        │    │
│  │   • RefreshTokenRotator - Sin cambios (solo usa TokenFactory)       │    │
│  ├─────────────────────────────────────────────────────────────────────┤    │
│  │ INFRASTRUCTURE (1 archivo - JwtTokenService)                        │    │
│  │   • JwtTokenService: Cambiar Sha256 → ES256 signer                  │    │
│  │   • Key Management: Generar claves EC P-256 en Vault                │    │
│  │   • JWKS Endpoint: Actualizar para claves EC (kty=EC, crv=P-256)    │    │
│  ├─────────────────────────────────────────────────────────────────────┤    │
│  │ APPLICATION (0 cambios)                                             │    │
│  │   • AuthenticateUserUseCase: Usa TokenFactory interface             │    │
│  │   • RefreshTokenUseCase: Usa TokenFactory interface                 │    │
│  ├─────────────────────────────────────────────────────────────────────┤    │
│  │ PRESENTATION (0 cambios)                                            │    │
│  │   • AuthController: Solo usa UseCases                               │    │
│  │   • AuthResponseTransformer: Solo serializa AccessToken             │    │
│  ├─────────────────────────────────────────────────────────────────────┤    │
│  │ TESTS (2 archivos)                                                  │    │
│  │   • TokenFactoryTest: Actualizar expectations (EC keys)             │    │
│  │   • JwtTokenServiceTest: Nuevo test para ES256                      │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│  ARCHIVOS A MODIFICAR: **3 archivos** (vs 8+ en arquitectura monolítica)    │
│  1. `Infrastructure/Security/JwtTokenService.php` (cambio algoritmo)        │
│  2. `ModuleBootstrap.php` (configuración claves EC)                         │
│  3. `tests/Unit/Tenants/Domain/Services/TokenFactoryTest.php` (expects)     │
│  4. `tests/Unit/Tenants/Infrastructure/Security/JwtTokenServiceTest.php`    │
│     (nuevo archivo)                                                         │
│                                                                             │
│  TIEMPO ESTIMADO: **2-4 horas** (vs 2-3 días en monolito acoplado)          │
│                                                                             │
│  RIESGOS MITIGADOS POR ARQUITECTURA:                                        │
│  ✅ Zero-downtime: Rotación de claves (JWKS soporta múltiples `kid`)       │
│  ✅ Rollback inmediato: Feature flag `jwt_algorithm` en config             │
│  ✅ Tests de regresión: Ejecutar suite completa en < 30s                   │
│  ✅ Validación: JWKS endpoint expone ambas claves durante transición       │
└─────────────────────────────────────────────────────────────────────────────┘
```
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  MATRIZ DECISIÓN → EVIDENCIA                                                │
├─────────────────────────────────────────────────────────────────────────────┤
│  ┌─────────────────────────┬────────────────────┬────────────────────────┐  │
│  │ Decisión Arquitectónica │ Artefacto Evidencia│ Ubicación              │  │
│  ├─────────────────────────┼────────────────────┼────────────────────────┤  │
│  │ Clean Architecture 4    │ solid-srp-analisis │ semana-02/             │  │
│  │ capas (Presentation,    │ .md + Código       │ app/Modules/Tenants/   │  │
│  │ Application, Domain,    │                    │                        │  │
│  │ Infrastructure)         │                    │                        │  │
│  ├─────────────────────────┼────────────────────┼────────────────────────┤  │
│  │ Principio SRP aplicado  │ solid-srp-analisis │ semana-02/             │  │
│  │ (12+ clases cohesivas)  │ .md + Métricas     │ app/Modules/Tenants/   │  │
│  ├─────────────────────────┼────────────────────┼────────────────────────┤  │
│  │ Repository Pattern      │ arquitectura-capas-│ semana-04/             │  │
│  │ (5 interfaces + 3       │ repositorio.md     │ app/Modules/Tenants/   │  │
│  │ implementaciones)       │ + InMemory Repos   │ Infrastructure/        │  │
│  ├─────────────────────────┼────────────────────┼────────────────────────┤  │
│  │ Controlador Delgado     │ AuthController.php │ Presentation/          │  │
│  │ (Solo HTTP → DTO → UC)  │ (116→<50 líneas)   │ Controllers/           │  │
│  ├─────────────────────────┼────────────────────┼────────────────────────┤  │
│  │ Domain Services para    │ PasswordPolicy,    │ Domain/Services/       │  │
│  │ políticas (Password,    │ MfaPolicy,         │ + Tests Unitarios      │  │
│  │ MFA, Tokens, Refresh)   │ TokenFactory,      │ (90% cobertura)        │  │
│  │                         │ RefreshTokenRotator│                        │  │
│  ├─────────────────────────┼────────────────────┼────────────────────────┤  │
│  │ Domain Events para      │ 14 Eventos +       │ Domain/Events/         │  │
│  │ Auditoría Desacoplada   │ EventPublisher     │ Infrastructure/Audit/  │  │
│  ├─────────────────────────┼────────────────────┼────────────────────────┤  │
│  │ Multi-tenant: RLS +     │ TenantMiddleware,  │ Presentation/Middleware│  │
│  │ X-Tenant-ID + Adapter   │ CentralUserReadOnly│ + Infrastructure/      │  │
│  │ Read-Only para módulos  │ Repository         │ Persistence/Shared/    │  │
│  ├─────────────────────────┼────────────────────┼────────────────────────┤  │
│  │ JWT RS256 + JWKS +      │ JwtTokenService    │ Infrastructure/Security│  │
│  │ Refresh Rotation +      │ + RefreshTokenRot. │ + Tests (Reuse Detect) │  │
│  │ Reuse Detection         │                    │                        │  │
│  ├─────────────────────────┼────────────────────┼────────────────────────┤  │
│  │ Contrato API OpenAPI 3.1│ openapi.yaml       │ semana-05/             │  │
│  │ (25 endpoints, 40 schemas)                   │                        │  │
│  ├─────────────────────────┼────────────────────┼────────────────────────┤  │
│  │ Evaluación Microservicio│ microservice-eval. │ semana-05/             │  │
│  │ (Decision Matrix)       │ uation.md          │                        │  │
│  ├─────────────────────────┼────────────────────┼────────────────────────┤  │
│  │ Auditoría Inmutable     │ PdoAuditLogRepo    │ Infrastructure/Audit/  │  │
│  │ (Hash Chain SHA-256 +   │ + Trigger BEFORE   │ + InMemory (verify)    │  │
│  │ Trigger Inmutabilidad)  │ UPDATE/DELETE      │                        │  │
│  ├─────────────────────────┼────────────────────┼────────────────────────┤  │
│  │ Pipeline MVC + Middleware│ Router.php +      │ Presentation/          │  │
│  │ (Tenant → RateLimit →   │ Middleware/ +      │ + public/index.php     │  │
│  │ JwtAuth)                │ public/index.php   │                        │  │
│  ├─────────────────────────┼────────────────────┼────────────────────────┤  │
│  │ Contratos Microservicio │ gRPC proto,        │ semana-05/             │  │
│  │ (gRPC, Kafka, Avro)     │ Kafka topics,      │ (preparados, no usados)│  │
│  │                         │ Avro schemas       │                        │  │
│  └─────────────────────────┴────────────────────┴────────────────────────┘  │
│  TOTAL: 14 decisiones arquitectónicas clave, cada una con artefacto         │
│  trazable (documento + código + tests) en el repositorio.                   │
└─────────────────────────────────────────────────────────────────────────────┘
```
