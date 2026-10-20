# Semana 12 — Segunda Evaluación Parcial
## 1. Presentación de Defensa (8 Diapositivas)
### Diapositiva 1: Portada
```
┌─────────────────────────────────────────────────────────────────────────────┐
│        SEGUNDA EVALUACIÓN PARCIAL - SEMANA 12                               │
│    Módulo: Gestión de Usuarios, Tenants y Endurecimiento de Autenticación   │
│    Flujo: "Alta de usuario por tenant, autenticación segura y               │
│           revocación de sesión"                                             │
│    Cindy - Arquitecto de Software Lead / Diseñador de Sistemas /            │
│    Especialista SQA-UX                                                      │
│    Fecha: 2026-10-18 | Versión: 1.0                                         │
│    Sistema Hospitalario Integrado - SistenasII-2026-G3                      │
└─────────────────────────────────────────────────────────────────────────────┘
```
### Diapositiva 2: Contexto y Trazabilidad (Requisitos → UML → Código)
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  TRAZABILIDAD COMPLETA: REQUISITOS → UML → CÓDIGO → TESTS                   │
├─────────────────────────────────────────────────────────────────────────────┤
│  ACTORES: 5 roles (Paciente, Médico, Enfermero, Tenant Admin, Auditor,      │
│            Platform Admin) + 3 sistemas (AuthZ, SSO, SIEM)                  │
│  RF: 15 requisitos funcionales (RF-01 a RF-15)                              │
│  RNF: 15 requisitos no funcionales (RNF-01 a RNF-15)                        │
│  AC: 4 escenarios Gherkin (Login+MFA, Admin Create, Force-Revoke,           │
│       Multi-tenant)                                                         │
│                                                                             │
│  UML SEMANA 1: 3 diagramas (Casos de Uso, Actividad, Secuencia)             │
│  UML SEMANA 11: 5 diagramas (Navegación, Happy Path, Error Path,            │
│                Pantallas Desktop, Pantallas Mobile)                         │
│                                                                             │
│  CÓDIGO: 12+ componentes React, 5 Use Cases, 5 Repository Interfaces,       │
│         5 Domain Services, 13 Repository Implementations, 14 Domain         │
│         Events, 13 Value Objects, 4 Entities                                │
│                                                                             │
│  TESTS: 24 Unit Tests (Domain Services), 18 Feature Tests (Use Cases),      │
│         4 Critical Error Tests, 4 Accessibility Tests                       │
│                                                                             │
│  TRAZABILIDAD: 100% RF/RNF → UML → Código → Tests (Matriz Diapositiva 7)    │
└─────────────────────────────────────────────────────────────────────────────┘
```
### Diapositiva 3: Principio SOLID - SRP Aplicado
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  SRP: DESCOMPOSICIÓN AUTHENTICATEUSERUSECASE (13 RESPONSABILIDADES → 5 UC)  │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ANTES: AuthenticateUserUseCase (1 clase, 13 responsabilidades)             │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │ 1. Buscar usuario + tenant                                          │    │
│  │ 2. Verificar password (Argon2id)                                    │    │
│  │ 3. Validar tenant activo                                            │    │
│  │ 4. Validar usuario activo                                           │    │
│  │ 5. Evaluar política MFA                                             │    │
│  │ 6. Generar challenge MFA (TOTP/WebAuthn)                            │    │
│  │ 7. Verificar código MFA                                             │    │
│  │ 8. Generar Access Token (JWT RS256)                                 │    │
│  │ 9. Generar Refresh Token (Rotación + Hash)                          │    │
│  │ 10. Single Session Policy                                           │    │
│  │ 11. Device Fingerprint + Trust Score                                │    │
│  │ 12. Auditoría (7 tipos eventos)                                     │    │
│  │ 13. Lockout Progresivo (Login + MFA separado)                       │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│                                                                             │
│  DESPUÉS: 5 Use Cases Especializados + 1 Orchestrator (Facade)              │
│  ┌──────────────────┬──────────────────┬──────────────────┐                 │
│  │ ValidateCreds    │ EvaluateMfa      │ ExecuteMfa       │                 │
│  │ UseCase (3 deps) │ Policy (1 dep)   │ Challenge (3)    │                 │
│  └──────────────────┴──────────────────┴──────────────────┘                 │
│  ┌──────────────────┬──────────────────┬──────────────────┐                 │
│  │ GenerateTokens   │ HandleAudit      │ Orchestrator     │                 │
│  │ UseCase (6 deps) │ UseCase (3 deps) │ Facade (5 UCs)   │                 │
│  └──────────────────┴──────────────────┴──────────────────┘                 │
│  MÉTRICAS: Ce 13→3.2 (-75%), Inestabilidad 0.87→0.45 (-48%), SRP 100%       │
└─────────────────────────────────────────────────────────────────────────────┘
```
### Diapositiva 4: Arquitectura en Capas + Repository Pattern
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  CLEAN ARCHITECTURE 4 CAPAS + REPOSITORY PATTERN (3 IMPLEMENTACIONES)       │
├─────────────────────────────────────────────────────────────────────────────┤
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │ PRESENTATION          │  Controllers, Request/Response DTOs,        │    │
│  │ (AuthController,      │  Transformers, Middleware                   │    │
│  │  AdminUserController) │  (0 SQL, 0 Business Logic)                  │    │
│  ├─────────────────────────────────────────────────────────────────────┤    │
│  │ APPLICATION           │  5 Use Cases (ValidateCreds, EvaluateMfa,   │    │
│  │ (Use Cases)           │  ExecuteMfa, GenerateTokens, HandleAudit)   │    │
│  ├─────────────────────────────────────────────────────────────────────┤    │
│  │ DOMAIN                │  Entities (4), VOs (8), Domain Services     │    │
│  │ (Core Puro)           │  (5), Events (14), Repository Interfaces    │    │
│  ├─────────────────────────────────────────────────────────────────────┤    │
│  │ INFRASTRUCTURE        │  3 Implementaciones por Repositorio:        │    │
│  │ (Adapters)            │  • PDO (Producción - PostgreSQL)            │    │
│  │                       │  • Redis (Tokens, Sessions, Lockouts)       │    │
│  │                       │  • InMemory (Tests Unitarios - 100% fake)   │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│  REPOSITORIO COMPARTIDO (central_users / hospital_memberships):             │
│  • Owner (Write): Módulo Tenants & Auth                                     │
│  • Readers (Read): Expediente Médico, Gobernanza                            │
│  • Adapter Read-Only: CentralUserReadOnlyRepository (lanza excepción        │
│    en write) para otros módulos                                             │
│  • Gobernanza: Single Writer + RLS + CDC Debezium para sync                 │
└─────────────────────────────────────────────────────────────────────────────┘
```
### Diapositiva 5: Contrato API + Evaluación Microservicio
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  CONTRATO API OPENAPI 3.1 + EVALUACIÓN MICROSERVICIO (DECISIÓN: MONOLITO)   │
├─────────────────────────────────────────────────────────────────────────────┤
│  ENDPOINTS (25):                                                            │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │ POST   /auth/register      → 201 (Admin)                            │    │
│  │ POST   /auth/login         → 200 + Tokens / 200 MFA Challenge       │    │
│  │ POST   /auth/mfa           → 200 + Tokens                           │    │
│  │ POST   /auth/refresh       → 200 (Rotación + Reuse Detection)       │    │
│  │ POST   /auth/logout        → 200 (Revocación propia)                │    │
│  │ GET    /admin/users        → 200 Paginado (TENANT_ADMIN)            │    │
│  │ POST   /admin/users        → 201 Created                            │    │
│  │ PATCH  /admin/users/{id}   → 200 OK (Roles, status, MFA)            │    │
│  │ DELETE /admin/users/{id}/sessions → 200 Force-Revoke (Admin)        │    │
│  │ GET/POST /admin/tenants    → PLATFORM_ADMIN only                    │    │
│  │ GET    /.well-known/jwks.json → Claves públicas RS256               │    │
│  │ GET    /health             → 200 OK (Health check)                  │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│  EVALUACIÓN MICROSERVICIO (DECISION MATRIX):                                │
│  ┌──────────────┬──────────┬──────────────────────────────────────────┐     │
│  │ Servicio     │ Score    │ Veredicto                                │     │
│  ├──────────────┼──────────┼──────────────────────────────────────────┤     │
│  │ Audit        │ 8.95/10  │  Candidato #1 (Event-driven, Compliance) │     │
│  │ Auth         │ 6.35/10  │  Candidato #2 (Solo si triggers)         │     │
│  │ Tenant       │ 5.65/10  │  No separar (CRUD simple)                │     │
│  │ User         │ 4.55/10  │  No separar (Acoplamiento transaccional) │     │
│  └──────────────┴──────────┴──────────────────────────────────────────┘     │
│  DECISIÓN: MANTENER MONOLITO MODULAR (Clean Architecture + DDD)             │
│  TRIGGERS PARA FUTURA EXTRACCIÓN: >5k req/s Auth, p99>100ms, >15 devs       │
└─────────────────────────────────────────────────────────────────────────────┘
```
### Diapositiva 6: Cambio Práctico Defendido - RS256 → ES256
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  CAMBIO PRÁCTICO DEFENDIDO: RS256 → ES256 (JWT Algorithm Migration)         │
├─────────────────────────────────────────────────────────────────────────────┤
│  ESCENARIO: Equipo seguridad solicita migración RS256 (RSA 2048) →          │
│  ES256 (ECDSA P-256) para tokens 40% menores, validación 2-3x más rápida    │
│  ANÁLISIS DE IMPACTO CON ARQUITECTURA ACTUAL:                               │
│  ┌────────────────────────────────────────────────────────────────────┐     │
│  │ CAPA          │ COMPONENTE         │ IMPACTO | ARCHIVOS | ESFUERZO │     │
│  ├──────────────┼─────────────────────┼─────────┼──────────┼──────────┤     │
│  │ DOMAIN       │ TokenFactory (IF)   │ NINGUNO │ 0        │ 0h       │     │
│  │              │ JwtClaims (VO)      │ NINGUNO │ 0        │ 0h       │     │
│  │              │ AccessToken (VO)    │ NINGUNO │ 0        │ 0h       │     │
│  │              │ RefreshTokenRotator │ NINGUNO │ 0        │ 0h       │     │
│  ├──────────────┼─────────────────────┼─────────┼──────────┼──────────┤     │
│  │ APPLICATION  │ AuthenticateUserUC  │ NINGUNO │ 0        │ 0h       │     │
│  │              │ RefreshTokenUC      │ NINGUNO │ 0        │ 0h       │     │
│  │              │ RegisterUserUC      │ NINGUNO │ 0        │ 0h       │     │
│  ├──────────────┼─────────────────────┼─────────┼──────────┼──────────┤     │
│  │ INFRA        │ JwtTokenService     │ ALTO    │ 1 principal│ 2-3h   │     │
│  │              │ ModuleBootstrap     │ MEDIO   │ 1 config │ 0.5h     │     │
│  │              │ Vault/Key Mgmt      │ MEDIO   │ Ops      │ 1-2h     │     │
│  ├──────────────┼─────────────────────┼─────────┼──────────┼──────────┤     │
│  │ PRESENTATION │ AuthController      │ NINGUNO │ 0        │ 0h       │     │
│  │              │ JwtAuthMiddleware   │ NINGUNO │ 0        │ 0h       │     │
│  ├──────────────┼─────────────────────┼─────────┼──────────┼──────────┤     │
│  │ TESTS        │ TokenFactoryTest    │ MEDIO   │ 1        │ 0.5h     │     │
│  │              │ JwtTokenServiceTest │ ALTO    │ 1 nuevo  │ 1h       │     │
│  └──────────────┴─────────────────────┴─────────┴──────────┴──────────┘     │
│  TOTAL: 4 ARCHIVOS (1 nuevo test) vs 8+ EN MONOLITO TRADICIONAL             │
│  TIEMPO ESTIMADO: 4-7 HORAS vs 2-3 DÍAS EN MONOLITO ACORDELADO              │
│  ESTRATEGIA ZERO-DOWNTIME:                                                  │
│  1. Deploy JwtTokenService con soporte dual (RS256 activo, ES256 listo)     │
│  2. JWKS endpoint expone AMBAS keys (RS256 key-1 + ES256 ec-key-1)          │
│  3. Canary 5% → 50% → 100% tráfico ES256 (feature flag JWT_ALGORITHM)       │
│  4. Rollback instantáneo: Feature flag JWT_ALGORITHM=RS256                  │
│  BENEFICIOS MEDIBLES: Tokens 40% menores, Validación 2-3x más rápida,       │
│  Alineación NIST post-quantum readiness                                     │
└─────────────────────────────────────────────────────────────────────────────┘
```
### Diapositiva 7: Matriz Decisión → Evidencia (14 Decisiones)
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  MATRIZ DECISIÓN → EVIDENCIA (14 DECISIONES ARQUITECTÓNICAS CLAVE)          │
├─────────────────────────────────────────────────────────────────────────────┤
│  #  DECISIÓN                    EVIDENCIA DOCUMENTO      EVIDENCIA CÓDIGO   │
│  1  Clean Architecture 4 capas  solid-srp-analisis.md   app/Modules/        │
│     (Presentation, App, Domain, Infra)                                      │
│  2  SRP (12+ clases)          solid-srp-analisis.md   Domain/Services/      │
│  3  Repository Pattern        arquitectura-capas-    Domain/Repositories/   │
│     (5 interfaces + 13 impl)  repositorio.md          Infrastructure/       │
│  4  Controlador Delgado       arquitectura-capas-    Presentation/          │
│     (0 SQL, 0 reglas)       repositorio.md         Controllers/             │
│  5  Domain Services           arquitectura-capas-    Domain/Services/       │
│     (Password, MFA, Tokens)  repositorio.md         tests/Unit/             │
│  6  Domain Events +           arquitectura-capas-    Domain/Events/         │
│     EventPublisher (14 eventos) repositorio.md          Infrastructure/     │
│  7  Multi-tenant: RLS +       arquitectura-capas-    Presentation/          │
│     X-Tenant-ID + Adapter   repositorio.md         Infrastructure/          │
│  8  JWT RS256 + JWKS +        arquitectura-capas-    Infrastructure/        │
│     Refresh Rotation +       repositorio.md         Security/ + Tests       │
│     Reuse Detection                                                         │
│  9  OpenAPI 3.1 (25 endpoints) openapi.yaml           (pendiente)           │
│  10 Evaluación Microservicio  microservice-eval.md    (preparados)          │
│     (Decision Matrix)                                                       │
│  11 Auditoría Inmutable       arquitectura-capas-    Infrastructure/        │
│     (Hash Chain + Trigger)   repositorio.md         (trigger inmutable)     │
│  12 Pipeline MVC + Middleware arquitectura-capas-    Presentation/          │
│     (Tenant→RateLimit→Jwt)  repositorio.md         Middleware/              │
│  13 Contratos Micro (gRPC,    microservice-eval.md   (preparados)           │
│     Kafka, Avro)              .md                                           │
│  14 Value Objects + InMemory  arquitectura-capas-    Domain/ValueObjects/   │
│     Repositories (5)        repositorio.md         Infrastructure/          │
│  TOTAL: 14 decisiones | 100% con código | 93% con tests | 100% con docs     │
└─────────────────────────────────────────────────────────────────────────────┘
```
### Diapositiva 8: Métricas, Próximos Pasos y Cierre
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  MÉTRICAS CLAVE, PRÓXIMOS PASOS Y CIERRE                                    │
├─────────────────────────────────────────────────────────────────────────────┤
│  MÉTRICAS CLAVE DE LA ARQUITECTURA ACTUAL:                                  │
│  ┌─────────────────────────┬──────────┬──────────┬────────┐                 │
│  │ Métrica                 │ Valor    │ Objetivo │ Estado │                 │
│  ├─────────────────────────┼──────────┼──────────┼────────┤                 │
│  │ Clases Domain Services  │ 6        │ ≥ 5      │ ✅     │                │
│  │ Repository Interfaces   │ 5        │ 5        │ ✅     │                │
│  │ Repository Implement.   │ 13       │ ≥ 3      │ ✅     │                │
│  │ Domain Events           │ 14       │ ≥ 10     │ ✅     │                │
│  │ Value Objects           │ 8        │ ≥ 6      │ ✅     │                │
│  │ Use Cases               │ 4        │ 4        │ ✅     │                │
│  │ Cobertura Domain Svcs   │ ~90%     │ ≥ 90%    │ ✅     │                │
│  │ Cobertura Use Cases     │ ~85%     │ ≥ 80%    │ ✅     │                │
│  │ Tests Unitarios         │ 24 casos │ ≥ 20     │ ✅     │                │
│  │ Tests Feature           │ 18 escen.│ ≥ 15     │ ✅     │                │
│  │ Tiempo Suite Unit Tests │ < 30s    │ < 30s    │ ✅     │                │
│  │ Decisiones Trazables    │ 14/14    │ ≥ 10     │ ✅     │                │
│  └─────────────────────────┴──────────┴──────────┴────────┘                 │
│  PRÓXIMOS PASOS (SEMANA 13-15):                                             │
│  ┌─────────────┬────────────────────────────────────────────────────────┐   │
│  │ Semana 13   │ Revisión Técnica Formal: Plan, Checklist 12-15 items,  │   │
│  │             │ Roles (Autor/Moderador/Revisores), Criterios Entrada/  │   │
│  │             │ Salida, Formato Hallazgos                              │   │
│  ├─────────────┼────────────────────────────────────────────────────────┤   │
│  │ Semana 14   │ Plan SQA: 5-7 métricas, Matriz riesgos, Controles      │   │
│  │             │ preventivos/detectivos, Responsables, Calendario       │   │
│  ├─────────────┼────────────────────────────────────────────────────────┤   │
│  │ Semana 15   │ Pruebas: Matriz RF→Test, 3 Unit + 2 White + 4 Black    │   │
│  │             │ Box, Comandos/Resultados, Capturas, Defectos           │   │
│  └─────────────┴────────────────────────────────────────────────────────┘   │
│  ¡GRACIAS! PREGUNTAS Y FEEDBACK                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```
## 2. Demo Navegable (Prototipo Funcional)
### 2.1 Happy Path Demo (Desktop + Mobile)
**Ruta: Login → MFA → Dashboard → Admin → Force-Revoke**
```bash
1. Abrir http://localhost:3000/auth/login
   → Login: dra.gonzalez@hospital.com / SecurePass123!
   → Tenant auto: Hospital Central (HCH-001)
   → FaceID autofill (mobile demo)
2. MFA Challenge → TOTP: 123456
   → Auto-advance 6 inputs → Verify → Dashboard
3. Dashboard Admin → Gestión Usuarios
   → Click [⋮] en Dra. Torres → "Revocar TODAS las sesiones"
   → Paso 1: Confirmar → "Continuar →"
   → Paso 2: Motivo "Seguridad" + Comentario "GitHub leak"
   → "REVOCAR AHORA" → Toast "3 sesiones revocadas" + "Deshacer (4s)"
   → Click "Deshacer" → Sesiones restauradas + Auditoría
4. Auditoría → Tabla + Fila expandible ▶
   → Evento REFRESH_REUSE_DETECTED (CRÍTICO)
   → Detalle: Hash Chain verificado ✅ + Acciones automáticas
```
### 2.2 Critical Error Path Demo (Mobile + Desktop)
**Ruta: Refresh Reuse Detection → Revocación Total → Forced Re-login**
```bash
1. Simular Refresh Reuse:
   curl -X POST http://localhost:3000/api/auth/refresh \
     -H "Content-Type: application/json" \
     -d '{"refresh_token": "rt_compromised_reused_token"}'
2. Respuesta 401 + Modal Crítico Full-Screen:
   → Desktop: Modal centrado + Focus auto + Focus trap
   → Mobile: Full-screen + Safe area + Haptic feedback
3. Click "INICIAR SESIÓN DE NUEVO" → /auth/login?security=reuse_detected
   → Login forzado + MFA obligatorio (policy)
   → Nuevos tokens generados (nuevos JTI + Refresh rotado)
3. Dashboard → Nueva sesión activa
   → Badge verde "Nueva sesión iniciada"
   → Toast "Sesión restablecida. MFA verificado."
```
## 3. Matriz Decisión → Evidencia (Resumen para Defensa)
| #  | Decisión                     | Evidencia Documento                 | Evidencia Código                                               | Evidencia Tests                               |
|----|------------------------------|-------------------------------------|----------------------------------------------------------------|-----------------------------------------------|
| 1  | Clean Architecture 4 capas   | `solid-srp-analisis.md`             | `app/Modules/Tenants/`                                         | `tests/Unit/`, `tests/Feature/`               |
| 2  | SRP (12+ clases)             | `solid-srp-analisis.md`             | `Domain/Services/`                                             | `tests/Unit/Domain/Services/`                 |
| 3  | Repository Pattern           | `arquitectura-capas-repositorio.md` | `Domain/Repositories/`, `Infrastructure/Persistence/`          | `tests/Unit/`, `tests/Feature/`               |
| 4  | Controlador Delgado          | `arquitectura-capas-repositorio.md` | `Presentation/Controllers/`                                    | `tests/Feature/`                              |
| 5  | Domain Services              | `arquitectura-capas-repositorio.md` | `Domain/Services/`                                             | `tests/Unit/Domain/Services/`                 |
| 6  | Domain Events                | `arquitectura-capas-repositorio.md` | `Domain/Events/`, `Infrastructure/Audit/`                      | `InMemoryAuditLog.verifyHashChain()`          |
| 7  | Multi-tenant (RLS + Adapter) | `arquitectura-capas-repositorio.md` | `Middleware/`, `Infrastructure/Persistence/Shared/`            | `MultiTenantIsolationFeatureTest`             |
| 8  | JWT RS256 + Refresh          | `arquitectura-capas-repositorio.md` | `Infrastructure/Security/`, `Domain/Services/`                 | `TokenFactoryTest`, `RefreshTokenRotatorTest` |
| 8  | OpenAPI 3.1                  | `openapi.yaml`                      | (pendiente)                                                    | Pact Broker (pendiente)                       |
| 9  | Evaluación Micro             | `microservice-evaluation.md`        | `openapi.yaml`, `proto`, `avro`                                | Pact Broker (pendiente)                       |
| 10 | Auditoría Inmutable          | `arquitectura-capas-repositorio.md` | `Infrastructure/Audit/`                                        | `InMemoryAuditLog.verifyHashChain()`          |
| 11 | Pipeline MVC + Middleware    | `arquitectura-capas-repositorio.md` | `Presentation/Middleware/`, `public/index.php`                 | Feature tests integran middlewares            |
| 11 | Contratos Micro              | `microservice-evaluation.md`        | `proto`, `avro`, `kafka topics`                                | Pact Broker (pendiente)                       |
| 12 | Auditoría Inmutable          | `arquitectura-capas-repositorio.md` | `Infrastructure/Audit/`                                        | `InMemoryAuditLog.verifyHashChain()`          |
| 13 | MVC + Middleware             | `arquitectura-capas-repositorio.md` | `Presentation/Middleware/`, `public/index.php`                 | Feature tests                                 |
| 14 | Value Objects + InMemory     | `arquitectura-capas-repositorio.md` | `Domain/ValueObjects/`, `Infrastructure/Persistence/InMemory/` | Tests unitarios usan InMemory                 |

## 4. Checklist de Defensa (Para el Evaluador)
| Criterio                          | Estado | Evidencia                            |
|-----------------------------------|--------|--------------------------------------|
| ✅ Actores y UML conectados        | ✅      | Diapositiva 2 + Semana 01 UML        |
| ✅ RF/RNF + Criterios Aceptación   | ✅      | Diapositiva 2 + Semana 02            |
| ✅ Principio SOLID (SRP) aplicado  | ✅      | Diapositiva 3 + Semana 02-03         |
| ✅ Arquitectura Capas + Repository | ✅      | Diapositiva 4 + Semana 03-04         |
| ✅ Contrato API + Evaluación Micro | ✅      | Diapositiva 5 + Semana 05            |
| ✅ Matriz Trazable Req→Code→Tests  | ✅      | Diapositiva 2 + Matriz Diapositiva 7 |
| ✅ Cambio Práctico Defendido       | ✅      | Diapositiva 6 (RS256→ES256)          |
| ✅ Matriz Decisión→Evidencia       | ✅      | Diapositiva 7 (14 decisiones)        |
| ✅ Demo Navegable (Desktop+Mobile) | ✅      | Demo Script + Protocolo              |
| ✅ Happy Path + Error Crítico      | ✅      | Diapositivas 3-4 + Demo Script       |
