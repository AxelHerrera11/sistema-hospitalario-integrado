# Semana 05 — Diseño Cliente-Servidor, API REST y Evaluación Microservicio
## Módulo: Gestión de usuarios, tenants y endurecimiento de autenticación
**Flujo:** «Alta de usuario por tenant, autenticación segura y revocación de sesión»
**Versión:** 1.0 | **Fecha:** 2026-10-18

---

## Entregables de Documentación (`docs/tenants/semana-05/`)
| Archivo                          | Tipo        | Descripción                                                                                                                                                                |
|----------------------------------|-------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `openapi.yaml`                   | OpenAPI 3.1 | **Contrato API completo**: 25 endpoints, 40+ schemas, security schemes (Bearer + Tenant Header), ejemplos                                                                  |
| `client-server-architecture.md`  | Markdown    | **Arquitectura Cliente-Servidor**: Diagramas, flujos auth, patrones error, evaluación bounded contexts, matriz acoplamiento, 4 candidatos microservicio, triggers medibles |
| `microservice-evaluation.md`     | Markdown    | **Evaluación Microservicio**: Decision matrix (scores), análisis riesgos, plan migración 3 fases (Strangler Fig), contratos gRPC/Kafka/Avro, checklist preparación         |
| `seq-client-server-auth.puml`    | PlantUML    | **Secuencia Cliente-Servidor**: Login→MFA→Tokens→Request→Refresh→Revocación (6 participantes)                                                                              |
| `seq-microservice-patterns.puml` | PlantUML    | **Patrones Microservicios**: Sync gRPC, Async Kafka, CDC Debezium, Saga, JWKS Rotation, Cache Invalidation                                                                 |
| `README.md`                      | Markdown    | Este índice                                                                                                                                                                |

## Resumen Ejecutivo
### Contrato API (OpenAPI 3.1) - `openapi.yaml`
**Endpoints Principales (25):**

| Grupo              | Endpoints                                                                                              | Autenticación                                   |
|--------------------|--------------------------------------------------------------------------------------------------------|-------------------------------------------------|
| **Authentication** | `/auth/register`, `/auth/login`, `/auth/mfa`, `/auth/refresh`, `/auth/logout`                          | Tenant Header + Bearer (excepto login/register) |
| **Admin Users**    | `GET/POST /admin/users`, `GET/PATCH/DELETE /admin/users/{id}`, `DELETE /admin/users/{userId}/sessions` | Bearer + Tenant (TENANT_ADMIN)                  |
| **Admin Tenants**  | `GET/POST /admin/tenants`, `GET/PATCH/DELETE /admin/tenants/{id}`                                      | Bearer (PLATFORM_ADMIN)                         |
| **System**         | `/health`, `/.well-known/jwks.json`                                                                    | Pública                                         |

**Seguridad:**
- **BearerAuth**: JWT RS256 (15 min), JWKS rotation 24h
- **TenantHeader**: `X-Tenant-ID` (UUID, obligatorio en TODAS las requests)
- **Rate Limiting**: 20 req/15min por email+IP en `/auth/*`

### Evaluación Microservicio - Decisión: **MANTENER MONOLITO MODULAR**
| Servicio Candidato | Score Ponderado | Veredicto                                                             |
|--------------------|-----------------|-----------------------------------------------------------------------|
| **Audit Service**  | 8.95/10         | 🟢 **Candidato #1** - Extracción natural (event-driven, compliance)   |
| **Auth Service**   | 6.35/10         | 🟡 **Candidato #2** - Solo si triggers: >5k req/s o p99 >100ms        |
| **Tenant Service** | 5.65/10         | 🔴 No separar - CRUD simple, baja frecuencia                          |
| **User Service**   | 4.55/10         | 🔴 No separar - Acoplamiento transaccional fuerte con `central_users` |

**Triggers Medibles para Futura Separación:**
- Throughput Auth > 5,000 req/s sostenido
- Latencia p99 validación token > 100ms
- Deployment frequency mismatch (Auth >10/día vs resto <1/semana)
- Equipo >15 devs con ownership separado
- Incidentes cross-module >30%

## Patrones de Comunicación (Si Separación Futura)
### 1. **Síncrono (gRPC) - Validación Token**
```
Clinical Service → gRPC ValidateToken → Auth Service
- Latencia objetivo: < 5ms p99
- Circuit Breaker + Retry exponencial
- Cache local validación (TTL 30s)
```

### 2. **Asíncrono (Kafka) - Domain Events**
```
Auth Service → Kafka (tenants.auth.*) → Consumers
- UserLoggedIn, SessionRevoked, MfaChallenged, RefreshReuseDetected
- Exactly-once semantics, retention 3-30d
- Consumers: Clinical, Governance, Audit
```

### 3. **CDC (Debezium) - Cambios BD**
```
PostgreSQL WAL → Debezium → Kafka → Consumers
- hospital_memberships changes → Clinical/Governance/Auth cache invalidation
- Schema Registry (Avro) + exactly-once
```

### 4. **Shared Infrastructure**
- **Redis Cluster**: Blocklist, Sessions, Rate Limits (shared, single writer Auth)
- **PostgreSQL**: Shared tables (`central_users`) + Own tables per service
- **JWKS Rotation**: Redis Pub/Sub para cache invalidation (TTL 1h)

---

## Diagramas de Secuencia
### `seq-client-server-auth.puml` - Flujo Completo Autenticación
6 participantes: User, Gateway, AuthSvc, Redis, DB, Broker, ClinicalSvc
- Login → MFA Challenge → Token Generation
- Request autenticada (Clinical Service con RLS)
- Refresh Rotation + Reuse Detection (ataque)
- Logout propio + Force-Revoke Admin

### `seq-microservice-patterns.puml` - Patrones Microservicios
8 participantes: Clinical, Gateway, Auth, Redis, DB, Kafka, Audit, Governance
1. **Sync gRPC**: ValidateToken (Clinical → Auth)
2. **Async Kafka**: UserLoggedIn event → 3 consumers paralelos
3. **Distributed Revocation**: Force-Revoke → Sync blocklist + Async events
4. **JWKS Rotation**: 24h rotation + Redis Pub/Sub cache invalidation
5. **CDC Debezium**: Membership changes → Cache invalidation
6. **Saga Pattern**: Cross-module user creation (patrón futuro)

---

## Plan de Migración (Si Triggers Cumplidos)
### Fase 0: Preparación (Semana 5-6) ✅ EN PROGRESO
- [x] OpenAPI 3.1 Spec (`openapi.yaml`)
- [x] gRPC Protobuf (`auth_validation.proto`)
- [x] Kafka Topics + Avro Schemas
- [ ] Pact Broker + Consumer-Driven Contracts
- [ ] k6 Load Tests (Login 500 req/s)
- [ ] Chaos Engineering (LitmusChaos)
- [ ] OpenTelemetry Instrumentation

### Fase 1: Auth Service Extraction (Mes 1-3) - SI TRIGGER
- Deploy separado, mismo código UseCases
- gRPC ValidateToken + JWKS endpoint
- Canary 10%→50%→100%
- Shared Redis (blocklist) + Shared PostgreSQL

### Fase 2: Audit Service Extraction (Mes 3-6) - PRIORIDAD COMPLIANCE
- Kafka Events → Audit Service (own DB + Elasticsearch)
- Retención 7 años (Ley 26.529/HIPAA)
- Backfill histórico + dual-write transición

### Fase 3: Service Mesh + Multi-region (Mes 6+)
- Istio/Linkerd mTLS
- Active-active geo-distribuido
- Chaos Engineering continuo

---

## Integración con Arquitectura Existente
| Componente                               | Estado Actual            | Futuro (Separación)               |
|------------------------------------------|--------------------------|-----------------------------------|
| `central_users` / `hospital_memberships` | Shared (Owner: Identity) | CDC Debezium → Consumers          |
| `audit_log`                              | Owner: Identity          | Propiedad → Audit Service         |
| `JWT RS256 + JWKS`                       | Auth Service             | JWKS público + gRPC ValidateToken |
| `Redis Blocklist/Sessions`               | Shared (Auth writer)     | Shared Redis Cluster              |
| `Pact Contracts`                         | ⏳ Pending                | Consumer-Driven (Clinical→Auth)   |

---

## Cómo Validar
### Renderizar Diagramas
```bash
# Secuencia Cliente-Servidor
java -jar plantuml.jar docs/tenants/semana-05/seq-client-server-auth.puml

# Patrones Microservicios
java -jar plantuml.jar docs/tenants/semana-05/seq-microservice-patterns.puml
```

### Validar OpenAPI
```bash
# Swagger Editor: https://editor.swagger.io/ (import openapi.yaml)
# O Redoc: npx redoc-cli bundle openapi.yaml
```

### Contract Testing (Pact)
```bash
# Consumer (Clinical) define contract
# Provider (Auth) verifica en CI
# Pact Broker: https://pact.broker.example.com
```
