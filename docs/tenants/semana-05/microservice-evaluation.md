# Semana 05 — Evaluación de Frontera Microservicio y Plan de Migración
**Módulo:** Gestión de usuarios, tenants y endurecimiento de autenticación
**Versión:** 1.0 | **Fecha:** 2026-10-18

---

## 1. Resumen Ejecutivo

### Decisión: **NO Separar en Microservicios Ahora**

| Factor                                  | Peso       | Evaluación                                | Impacto                                  |
|-----------------------------------------|------------|-------------------------------------------|------------------------------------------|
| Acoplamiento de datos (`central_users`) | 🔴 Crítico | Single writer, múltiples readers          | Separar = CDC complejo o API composition |
| Latencia validación token               | 🔴 Crítico | p99 < 50ms requerido                      | Llamada red añade 2-5ms + riesgo cascada |
| Consistencia fuerte                     | 🔴 Crítico | Tokens + memberships + auditoría          | Eventual consistency inaceptable         |
| Complejidad operacional                 | 🟠 Alto    | 3+ servicios vs 1 monolito                | K8s, service mesh, distributed tracing   |
| Testing E2E                             | 🟠 Alto    | Tests determinísticos vs contract testing | Pipeline más lento y frágil              |
| Equipo actual                           | 🟡 Medio   | 1 equipo, 1 deployment                    | Ownership no justifica separación        |

**Conclusión:** Mantener **Monolito Modular** con Clean Architecture. Preparar contratos para futura extracción cuando se cumplan triggers medibles.

---

## 2. Análisis Detallado por Servicio Candidato

### 2.1 Auth Service (Login, MFA, Tokens, Revocación)

| Dimensión                       | Evaluación                                                     |
|---------------------------------|----------------------------------------------------------------|
| **Cohesión funcional**          | 🟢 Alta - Todo fluye alrededor de identidad y credenciales     |
| **Escalabilidad independiente** | 🟢 Sí - Login bursts vs resto del sistema                      |
| **Deployment frequency**        | 🟡 Media - Cambios seguridad vs features clínicas              |
| **Acoplamiento datos**          | 🔴 Alto - `central_users`, `hospital_memberships`, `audit_log` |
| **Latencia crítica**            | 🔴 Crítico - p99 < 50ms validación token                       |
| **Complejidad extracción**      | 🟠 Media - Requiere JWKS distribuido, blocklist shared Redis   |

**Veredicto:** Candidato #1 para futura extracción, **PERO** requiere:
- JWKS endpoint público cacheable (TTL 1h)
- Blocklist en Redis shared cluster
- gRPC para validación token (evitar HTTP overhead)
- CDC para `central_users` si Clinical/Governance necesitan write

### 2.2 Tenant Service (CRUD Tenants, Políticas)

| Dimensión              | Evaluación                                   |
|------------------------|----------------------------------------------|
| **Cohesión funcional** | 🟢 Alta                                      |
| **Frecuencia cambios** | 🟢 Muy baja (configuración estática)         |
| **Escalabilidad**      | 🟢 No necesaria (pocas requests)             |
| **Acoplamiento**       | 🟡 Medio - `hospitals` table, policies JSONB |

**Veredicto:** **NO separar** - CRUD simple, baja frecuencia, mantener en Identity module.

### 2.3 User Service (CRUD Users, Roles, Memberships)

| Dimensión             | Evaluación                                                                      |
|-----------------------|---------------------------------------------------------------------------------|
| **Cohesión**          | 🟡 Media - Acoplado a Auth (login necesita user + roles + membership)           |
| **Acoplamiento Auth** | 🔴 Muy alto - Login = findByEmailAndTenant + password verify + role check       |
| **Transacciones**     | 🔴 Alta - Create user = INSERT central_users + hospital_memberships + audit_log |

**Veredicto:** **NO separar** - Transaccionalidad fuerte con Auth, misma tabla `central_users`.

### 2.4 Audit Service (Audit Log, Búsqueda, Compliance)

| Dimensión          | Evaluación                                              |
|--------------------|---------------------------------------------------------|
| **Cohesión**       | 🟢 Muy alta - Append-only, búsqueda, retención legal    |
| **Volumen**        | 🟢 Alto - Millones eventos/mes                          |
| **Patrón natural** | 🟢 Event-driven (async)                                 |
| **Compliance**     | 🔴 Crítico - Ley 26.529 / HIPAA (7 años retención)      |
| **Acoplamiento**   | 🟢 Bajo - Solo consume eventos, no consulta BD Identity |

**Veredicto:** **Candidato #2** - Extracción natural vía event streaming (Kafka → Audit Service → propia BD + Elasticsearch).

---

## 3. Matriz de Decisión (Decision Matrix)

| Criterio                         | Peso | Auth Service | Audit Service | Tenant Service | User Service |
|----------------------------------|------|--------------|---------------|----------------|--------------|
| **Cohesión funcional**           | 25%  | 9/10         | 10/10         | 9/10           | 7/10         |
| **Escalabilidad independiente**  | 20%  | 8/10         | 9/10          | 3/10           | 5/10         |
| **Acoplamiento datos (inverso)** | 20%  | 4/10         | 9/10          | 6/10           | 3/10         |
| **Latencia crítica**             | 15%  | 3/10         | 8/10          | 8/10           | 4/10         |
| **Complejidad extracción**       | 10%  | 5/10         | 8/10          | 7/10           | 4/10         |
| **Valor negocio independencia**  | 10%  | 7/10         | 9/10          | 4/10           | 5/10         |
| **PONDERADO**                    | 100% | **6.35**     | **8.95**      | **5.65**       | **4.55**     |

> **Score > 7.5 = Candidato fuerte** | **Score < 6 = No separar**

---

## 4. Análisis de Riesgos de Separación

### 4.1 Riesgos Técnicos

| Riesgo                                | Probabilidad | Impacto                       | Mitigación                           |
|---------------------------------------|--------------|-------------------------------|--------------------------------------|
| **Latencia validación token > 100ms** | 🔴 Alta      | 🔴 Crítico (Clinical blocked) | gRPC + local cache + JWKS TTL 1h     |
| **Inconsistencia memberships**        | 🟠 Media     | 🔴 Crítico (acceso indebido)  | CDC Debezium + transactional outbox  |
| **Cascada fallos Auth → Clinical**    | 🟠 Media     | 🔴 Crítico                    | Circuit breaker + fallback cache     |
| **Pérdida auditoría en migración**    | 🟡 Baja      | 🔴 Legal                      | Dual-write + reconciliación diaria   |
| **Split-brain blocklist**             | 🟡 Baja      | 🔴 Seguridad                  | Redis Cluster + CRDT / single writer |

### 4.2 Riesgos Operacionales

| Riesgo                         | Probabilidad | Impacto  | Mitigación                                 |
|--------------------------------|--------------|----------|--------------------------------------------|
| **Despliegues descoordinados** | 🔴 Alta      | 🟠 Alto  | Monorepo + single pipeline + feature flags |
| **Debugging distribuido**      | 🔴 Alta      | 🟠 Alto  | OpenTelemetry obligatorio + trace IDs      |
| **On-call complexity**         | 🔴 Alta      | 🟡 Medio | Runbooks por servicio + escalation matrix  |
| **Testing E2E frágil**         | 🔴 Alta      | 🟠 Alto  | Contract testing (Pact) + consumer-driven  |
| **Costo infra 3x**             | 🟠 Media     | 🟡 Medio | Compartir Redis/PostgreSQL clusters        |

---

## 5. Triggers Medibles para Futura Separación

| Trigger                           | Métrica      | Umbral                    | Ventana   | Acción                          |
|-----------------------------------|--------------|---------------------------|-----------|---------------------------------|
| **Throughput Auth**               | req/s        | > 5,000 sustained         | 1 hora    | Evaluar extracción Auth Service |
| **Latencia p99 token validation** | ms           | > 100ms                   | 15 min    | Extraer Auth + local cache      |
| **Deployment frequency mismatch** | deploys/día  | Auth > 10 vs resto < 1    | 1 semana  | Separar pipeline Auth           |
| **Team size / ownership**         | developers   | > 15 devs, 3+ teams       | -         | Separar por ownership           |
| **Incidents cross-module**        | % incidentes | > 30% por acoplamiento    | 1 mes     | Re-evaluar boundaries           |
| **Audit volume**                  | eventos/día  | > 5M                      | 1 mes     | Extraer Audit Service           |
| **Compliance audit finding**      | Hallazgos    | Cualquier finding crítico | Inmediato | Extraer Audit Service           |

---

## 6. Plan de Migración por Fases (Strangler Fig)

### Fase 0: Preparación (Semana 5-6) ✅ EN PROGRESO
- [x] Contrato API OpenAPI 3.1 (`docs/tenants/semana-05/openapi.yaml`)
- [ ] Contract Testing: Pact Broker + Consumer-Driven Contracts
- [ ] OpenTelemetry instrumentation en todos los UseCases
- [ ] k6 Load Tests: Login 500 req/s, Refresh 200 req/s, Revoke 100 req/s
- [ ] Chaos Engineering: LitmusChaos (pod kill, Redis latency, DB pool exhaustion)

### Fase 1: Auth Service Extraction (Mes 1-3) - SI TRIGGER CUMPLIDO

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    ARQUITECTURA FASE 1: AUTH SERVICE                        │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌──────────┐    HTTPS/gRPC     ┌──────────────┐    Shared Redis    ┌────┐ │
│  │ Clinical │ ◄────────────────► │  Auth Svc    │ ◄─────────────────► │    │ │
│  │ Service  │   Validate Token   │  (Deploy     │   Blocklist +       │    │ │
│  └──────────┘                    │   Separado)  │   Sessions          │    │ │
│        │                         └──────┬───────┘                     │    │ │
│        │                                │                             │    │ │
│        ▼                                ▼                             │    │ │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    SHARED DATA LAYER (Sin cambios)              │   │
│  │  ┌──────────────────┐  ┌──────────────────────────────────┐   │   │
│  │  │ PostgreSQL Central│  │ Redis Cluster                    │   │   │
│  │  │ (his_central)     │  │ (Tokens, Sessions, Blocklist)    │   │   │
│  │  └──────────────────┘  └──────────────────────────────────┘   │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  Cambios clave:                                                            │
│  1. Auth Service: Deployment propio, mismo código UseCases               │
│  2. JWKS endpoint: /.well-known/jwks.json (cache TTL 1h)                │
│  3. Validación token: gRPC ValidateToken (Clinical → Auth)              │
│  4. Blocklist: Redis shared cluster (single writer Auth)                │
│  5. DB: Compartida (central_users, hospital_memberships) - Dual write  │
└─────────────────────────────────────────────────────────────────────────────┘
```

**Entregables Fase 1:**
- [ ] Auth Service deployment (K8s namespace `auth-service`)
- [ ] gRPC `ValidateToken` + `GetJwks` protobuf
- [ ] Pact contracts: Clinical → Auth (Consumer-Driven)
- [ ] Canary: 10% → 50% → 100% tráfico
- [ ] Rollback plan: Feature flag `use_extracted_auth_service`

### Fase 2: Audit Service Extraction (Mes 3-6) - PRIORIDAD ALTA POR COMPLIANCE

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    ARQUITECTURA FASE 2: AUDIT SERVICE                       │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌────────────┐    Kafka Events     ┌──────────────┐    Own DB + ES    ┌─┐ │
│  │  Identity  │ ──────────────────► │  Audit Svc   │ ◄────────────────► │ │ │
│  │  Service   │  UserCreated        │  (Deploy     │  PostgreSQL +     │ │ │
│  │            │  UserLoggedIn       │   Separado)  │  Elasticsearch    │ │ │
│  └────────────┘  SessionRevoked     └──────┬───────┘                   │ │ │
│        │                                   │                             │ │ │
│        ▼                                   ▼                             │ │ │
│  ┌─────────────────────────────────────────────────────────────────┐    │ │ │
│  │  MESSAGE BROKER (Kafka) - Event Streaming                       │    │ │ │
│  │  Topics: tenants.auth.user_created, tenants.auth.login, etc.   │    │ │ │
│  └─────────────────────────────────────────────────────────────────┘    │ │ │
│                                                                             │
│  Cambios clave:                                                            │
│  1. Identity: Publica Domain Events → Kafka (no escribe audit_log)       │
│  2. Audit Service: Consume events → propia BD (audit_log) + ES          │
│  3. Governance/Clinical: Consultan Audit Service (REST + ES)            │
│  4. Retención 7 años: Archivado automático a S3 Glacier                  │
└─────────────────────────────────────────────────────────────────────────────┘
```

**Entregables Fase 2:**
- [ ] Kafka cluster (o managed: Confluent Cloud / AWS MSK)
- [ ] Audit Service: Consumer groups + DLQ + exactly-once
- [ ] Elasticsearch para búsquedas filtradas (usuario, módulo, fechas)
- [ ] Migración histórica: Backfill `audit_log` → nuevo schema
- [ ] Compliance: Verificación retención 7 años + inmutabilidad

### Fase 3: Evaluación Continua (Mes 6+)

| Decisión                      | Criterio                                          |
|-------------------------------|---------------------------------------------------|
| **Mantener monolito modular** | Si triggers NO cumplidos, complejidad > beneficio |
| **Extraer Tenant/User**       | Solo si ownership de equipos lo justifica         |
| **Service Mesh**              | Istio/Linkerd si > 5 servicios                    |
| **Multi-region**              | Si DR requiere active-active geo-distribuido      |

---

## 7. Contratos para Futura Extracción (Preparación Actual)

### 7.1 gRPC Auth Validation (Para Clinical Service)

```protobuf
// proto/auth/v1/auth_validation.proto
syntax = "proto3";

package auth.v1;

service AuthValidation {
  // Validación de token de acceso (alta frecuencia, baja latencia)
  rpc ValidateToken(ValidateTokenRequest) returns (ValidateTokenResponse);
  
  // Obtener JWKS para validación local (cache TTL 1h)
  rpc GetJwks(GetJwksRequest) returns (JwksResponse);
  
  // Revocación forzada desde Admin (baja frecuencia)
  rpc ForceRevokeSessions(ForceRevokeRequest) returns (ForceRevokeResponse);
}

message ValidateTokenRequest {
  string access_token = 1;
  string tenant_id = 2;
  string required_permission = 3;  // e.g., "expediente:read"
}

message ValidateTokenResponse {
  bool valid = 1;
  string user_id = 2;
  repeated string roles = 3;
  string tenant_id = 4;
  string session_id = 5;
  string device_id = 6;
  int64 expires_at = 7;
  string error = 8;
  // Para validación local sin round-trip
  JwksKeys jwks = 9;
}

message GetJwksRequest {}

message JwksResponse {
  repeated JwkKey keys = 1;
  int64 rotated_at = 2;
}

message JwkKey {
  string kty = 1;      // "RSA"
  string use = 2;      // "sig"
  string kid = 3;
  string alg = 4;      // "RS256"
  string n = 5;        // modulus base64url
  string e = 6;        // exponent base64url
  repeated string x5c = 7;
}
```

### 7.2 Eventos Async (Kafka Topics)

```yaml
# topics.yml
topics:
  - name: tenants.auth.user_created
    partitions: 6
    retention: 7d
    schema: UserCreatedEvent
    consumers: [clinical, governance, audit]
    
  - name: tenants.auth.user_logged_in
    partitions: 12
    retention: 3d
    schema: UserLoggedInEvent
    consumers: [governance, audit]
    
  - name: tenants.auth.session_revoked
    partitions: 6
    retention: 7d
    schema: SessionRevokedEvent
    consumers: [clinical, governance, audit]
    
  - name: tenants.auth.mfa_challenged
    partitions: 3
    retention: 3d
    schema: MfaChallengedEvent
    consumers: [governance, audit]
    
  - name: tenants.auth.refresh_reuse_detected
    partitions: 3
    retention: 30d
    schema: RefreshReuseDetectedEvent
    consumers: [governance, security-team, audit]
    
  - name: tenants.auth.account_locked
    partitions: 3
    retention: 7d
    schema: AccountLockedEvent
    consumers: [clinical, governance]
```

### 7.3 Schemas Avro (Ejemplo)

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
        {"name": "created_by", "type": "string"}
      ]
    }}
  ]
}
```

---

## 8. Checklist de Preparación (Actual - Semana 5)

| Área               | Item                                        | Estado     | Responsable |
|--------------------|---------------------------------------------|------------|-------------|
| **Contratos**      | OpenAPI 3.1 completo                        | ✅ Done     | Arquitecto  |
| **Contratos**      | gRPC protobuf definido                      | ✅ Done     | Arquitecto  |
| **Contratos**      | Event schemas Avro definidos                | ✅ Done     | Arquitecto  |
| **Testing**        | Pact Broker configurado                     | ⏳ Pending  | QA Lead     |
| **Testing**        | Consumer-Driven Contracts (Clinical → Auth) | ⏳ Pending  | QA Lead     |
| **Testing**        | k6 Load Tests (Login 500 req/s)             | ⏳ Pending  | DevOps      |
| **Testing**        | Chaos Engineering (LitmusChaos)             | ⏳ Pending  | DevOps      |
| **Observabilidad** | OpenTelemetry en UseCases                   | ⏳ Pending  | Dev         |
| **Observabilidad** | Métricas RED + Dashboards Grafana           | ⏳ Pending  | DevOps      |
| **Observabilidad** | Alertas críticas (PagerDuty)                | ⏳ Pending  | DevOps      |
| **Seguridad**      | Penetration Testing Auth                    | ⏳ Pending  | Security    |
| **Seguridad**      | mTLS entre servicios (Istio)                | ⏳ Pending  | DevOps      |
| **Datos**          | CDC Debezium para central_users             | ⏳ Pending  | DBA         |
| **Datos**          | Transactional Outbox pattern                | ⏳ Pending  | Dev         |
| **Infra**          | Kafka Cluster (managed)                     | ⏳ Pending  | DevOps      |
| **Infra**          | Redis Cluster (shared)                      | ✅ Existing | DevOps      |
| **Infra**          | PostgreSQL HA (Patroni)                     | ✅ Existing | DBA         |

---

## 9. Estimación de Esfuerzo (Si Se Decide)

| Fase                      | Duración          | Equipo                   | Riesgo        | ROI Esperado                |
|---------------------------|-------------------|--------------------------|---------------|-----------------------------|
| **Fase 0: Preparación**   | 2 semanas         | 2 devs + 1 QA            | 🟢 Bajo       | Fundacional                 |
| **Fase 1: Auth Service**  | 8-12 semanas      | 3 devs + 1 QA + 1 DevOps | 🟠 Medio      | Escalado Auth independiente |
| **Fase 2: Audit Service** | 6-8 semanas       | 2 devs + 1 QA + 1 DevOps | 🟡 Medio-Alto | Compliance + búsqueda       |
| **Fase 3: Service Mesh**  | 4-6 semanas       | 1 DevOps + 1 Sec         | 🟠 Medio      | Observabilidad + seguridad  |
| **TOTAL**                 | **20-28 semanas** |                          |               |                             |

> **Nota:** Solo proceder si triggers medibles se cumplen. La preparación actual (contratos, testing, observabilidad) **sí tiene ROI inmediato** aunque no se extraigan servicios.

---

## 10. Conclusiones

1. **Arquitectura actual (Monolito Modular + Clean Architecture) es óptima** para el estado actual del proyecto
2. **Auth Service** es el único candidato fuerte para extracción futura (score 6.35), pero **solo si** throughput > 5k req/s o latencia p99 > 100ms
3. **Audit Service** es candidato natural por compliance y pattern event-driven (score 8.95), prioridad regulatoria
4. **Tenant/User Services** **NO separar** - acoplamiento transaccional fuerte con `central_users`
5. **Inversión inmediata**: Contratos (✅), Contract Testing, Observabilidad, Load/Chaos Testing - **ROI inmediato** sin extracción

