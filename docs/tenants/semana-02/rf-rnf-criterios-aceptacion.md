# Semana 02 — Requisitos Funcionales, No Funcionales y Criterios de Aceptación
**Módulo:** Gestión de usuarios, tenants y endurecimiento de autenticación
**Flujo principal:** «Alta de usuario por tenant, autenticación segura y revocación de sesión»
**Versión:** 1.0 | **Fecha:** 2026-10-18

---

## 1. Requisitos Funcionales (RF)

| ID        | Requisito                                             | Descripción                                                                                                                                                                                                          | Prioridad  | Trazabilidad               |
|-----------|-------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------|----------------------------|
| **RF-01** | **Alta de usuario por tenant**                        | Un administrador de tenant (TENANT_ADMIN) puede crear usuarios dentro de su tenant. El sistema valida que el tenant esté activo, tenga cupo disponible y que el email sea único dentro del tenant.                   | 🔴 Crítica | UC-01, UC-08               |
| **RF-02** | **Validación de política de contraseña**              | Al crear usuario, el sistema exige: mínimo 12 caracteres, mayúscula, minúscula, número, símbolo, sin coincidir con las últimas 5 contraseñas, y verificación contra HaveIBeenPwned (API externa).                    | 🔴 Crítica | UC-01, UC-17               |
| **RF-03** | **Asignación de roles por tenant**                    | Los roles disponibles por tenant son: `TENANT_ADMIN`, `MEDICO`, `ENFERMERO`, `PACIENTE`, `AUDITOR`. Solo roles permitidos por la configuración del tenant pueden asignarse.                                          | 🔴 Crítica | UC-04                      |
| **RF-04** | **Autenticación con credenciales**                    | Usuario ingresa email + contraseña. Sistema valida contra hash Argon2id, verifica que el usuario esté activo (`status = 'active'`) y pertenezca al tenant indicado en `X-Tenant-ID`.                                 | 🔴 Crítica | UC-02                      |
| **RF-05** | **MFA adaptativo (TOTP/WebAuthn)**                    | Si el rol lo requiere (`TENANT_ADMIN`, `AUDITOR` siempre) o la política del tenant lo exige, se desafía MFA: TOTP (RFC 6238) o WebAuthn/FIDO2 (passkeys). Máximo 3 intentos fallidos → bloqueo 30 min.               | 🔴 Crítica | UC-02, UC-09               |
| **RF-06** | **Generación de tokens JWT RS256**                    | Access Token: 15 min, firmado RS256, claims: `sub`, `roles[]`, `tenant_id`, `session_id`, `jti`, `device_id`. Refresh Token: opaco, 7 días, rotación en cada uso, hash Argon2id en Redis.                            | 🔴 Crítica | UC-02, UC-10               |
| **RF-07** | **Rotación de Refresh Token + Reuse Detection**       | Cada uso del refresh token invalida el anterior y genera par nuevo. Si se detecta reuso (mismo refresh presentado 2 veces) → revocación total de sesiones del usuario + alerta CRÍTICA.                              | 🔴 Crítica | UC-05, UC-11               |
| **RF-08** | **Blocklist de Access Tokens (revocación inmediata)** | Access tokens revocados (logout, force-revoke, expiración) se añaden a Redis `blocklist:{jti}` con TTL = tiempo restante. Middleware valida en cada request.                                                         | 🔴 Crítica | UC-03, UC-12               |
| **RF-09** | **Revocación de sesión (logout / force-revoke)**      | Usuario: revoca su propia sesión (access → blocklist, refresh → DEL). Admin (TENANT_ADMIN/PLATFORM_ADMIN): revoca todas las sesiones de un usuario. Publica evento `SessionRevokedEvent` para microservicios.        | 🔴 Crítica | UC-03                      |
| **RF-10** | **Aislamiento multi-tenant estricto (RLS)**           | Todo query incluye `tenant_id`. PostgreSQL Row Level Security enforza `current_setting('app.tenant_id')`. Cross-tenant attempt → 403 + auditoría + alerta SIEM.                                                      | 🔴 Crítica | UC-01, UC-02, UC-03, UC-08 |
| **RF-11** | **Lockout progresivo por credenciales**               | 5 fallos → 15 min, 10 fallos → 1 h, 20 fallos → 24 h + notificación admin. Contadores separados: login vs MFA.                                                                                                       | 🟠 Alta    | UC-02, UC-13               |
| **RF-12** | **Single Session Policy (configurable)**              | Por tenant: `single_session_per_user`. Si habilitado, nuevo login revoca sesión previa del mismo usuario (device fingerprint para diferenciar).                                                                      | 🟠 Alta    | UC-02, UC-16               |
| **RF-13** | **Device Fingerprinting**                             | Registra `device_id`, IP, User-Agent, trust_score. Alerta opcional en dispositivo nuevo no confiable.                                                                                                                | 🟡 Media   | UC-02, UC-14               |
| **RF-14** | **Auditoría inmutable de eventos de seguridad**       | Eventos: LOGIN_SUCCESS, LOGIN_FAILED, MFA_CHALLENGE, MFA_SUCCESS, MFA_FAILED, ACCOUNT_LOCKED, TOKEN_REFRESHED, REFRESH_REUSE_ATTACK, REVOKE_SESSION, SESSION_IDLE_TIMEOUT, CROSS_TENANT_ATTEMPT. Hash chain SHA-256. | 🔴 Crítica | UC-15                      |
| **RF-15** | **JIT Provisioning (SSO SAML/OIDC)**                  | Primer login SSO: auto-crea usuario si no existe, mapea claims a roles, asigna tenant por domain/claim. MFA según política de rol.                                                                                   | 🟡 Media   | UC-07                      |

---

## 2. Requisitos No Funcionales (RNF)

| ID         | Requisito                           | Métrica / Criterio                                                                                                                                   | Verificación                                         |
|------------|-------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------|------------------------------------------------------|
| **RNF-01** | **Disponibilidad Auth**             | 99.99% (excluye mantenimiento programado). SLA: < 5 min downtime/mes.                                                                                | Monitoreo uptime + alertas PagerDuty                 |
| **RNF-02** | **Latencia Login (p95)**            | ≤ 300 ms (credenciales + MFA + token generation).                                                                                                    | k6 load test, APM (New Relic/Datadog)                |
| **RNF-03** | **Latencia Validación Token (p99)** | ≤ 50 ms (JWKS cache + Redis blocklist check).                                                                                                        | Synthetic monitoring cada 30s                        |
| **RNF-04** | **Latencia Revocación**             | ≤ 100 ms (blocklist write + Redis DEL + event publish).                                                                                              | Integration test + chaos test                        |
| **RNF-05** | **Throughput Login**                | ≥ 500 req/s sostenido, ≥ 2000 req/s pico (burst).                                                                                                    | k6 stress test                                       |
| **RNF-06** | **Cifrado en tránsito**             | TLS 1.3 obligatorio, HSTS, certificate pinning en móvil.                                                                                             | SSL Labs A+, test automático CI                      |
| **RNF-07** | **Cifrado en reposo**               | AES-256-GCM para secrets, Argon2id para passwords, TLS para Redis.                                                                                   | Auditoría infra + config review                      |
| **RNF-08** | **Inmutabilidad Auditoría**         | Tabla `audit_log` append-only, trigger `BEFORE UPDATE/DELETE` → EXCEPTION, hash chain SHA-256.                                                       | Test de mutación + pg_audit                          |
| **RNF-09** | **Retención Evidencias**            | Logs seguridad: 7 años (Ley 26.529 / HIPAA). Evidencias pipeline: 90 días WORM.                                                                      | Política S3 Object Lock + audit                      |
| **RNF-10** | **Compliance Seguridad**            | OWASP ASVS Level 2, NIST 800-63B AAL2, ISO 27001 A.9.                                                                                                | Auto-evaluación trimestral + auditoría externa anual |
| **RNF-11** | **Escalabilidad Horizontal**        | Stateless auth service, Redis Cluster, read replicas PostgreSQL. Kubernetes HPA basado en CPU/memoria/cola.                                          | Load test + scale test                               |
| **RNF-12** | **Observabilidad**                  | Métricas: login_success_rate, mfa_challenge_rate, lockout_rate, token_revocation_rate, p95_latency. Logs estructurados JSON. Tracing: OpenTelemetry. | Dashboards Grafana + alertas                         |
| **RNF-13** | **Recuperación ante Desastre**      | RPO: 0 (replicación síncrona PostgreSQL), RTO: < 15 min (failover automático Patroni).                                                               | DR drill trimestral                                  |
| **RNF-14** | **Usabilidad MFA**                  | Tasa de éxito MFA ≥ 95% en primer intento. Tiempo medio MFA ≤ 15s.                                                                                   | Telemetría frontend + encuestas                      |
| **RNF-15** | **Accesibilidad (WCAG 2.1 AA)**     | Contraste ≥ 4.5:1, foco visible, navegación teclado, labels ARIA, mensajes error claros.                                                             | axe-core CI + test manual                            |

---

## 3. Criterios de Aceptación (Gherkin)

### RF-01: Alta de usuario por tenant
```gherkin
Feature: Alta de usuario por tenant
  Background:
    Given un tenant "Hospital Central" con id "tenant-001" y status "active"
    And el tenant tiene cupo de 100 usuarios y actualmente 50 registrados
    And un admin con rol "TENANT_ADMIN" y token válido para "tenant-001"

  Scenario: Crear usuario exitosamente
    When el admin POST /admin/users con {email: "nuevo@hospital.com", name: "Dr. Nuevo", roles: ["MEDICO"]}
    Then respuesta 201 Created
    And el usuario se crea con tenant_id = "tenant-001"
    And password temporal generado y enviado por email
    And evento USER_CREATED registrado en auditoría con hash_chain

  Scenario: Fallo - tenant inactivo
    Given el tenant "tenant-001" cambia status a "suspended"
    When el admin POST /admin/users con datos válidos
    Then respuesta 403 Forbidden
    And mensaje "Tenant inactivo"
    And evento TENANT_INACTIVE en auditoría

  Scenario: Fallo - cupo excedido
    Given el tenant tiene 100/100 usuarios
    When el admin POST /admin/users con datos válidos
    Then respuesta 400 Bad Request
    And mensaje "Cupo de usuarios excedido"
    And evento TENANT_QUOTA_EXCEEDED en auditoría

  Scenario: Fallo - email duplicado en tenant
    Given existe usuario "existente@hospital.com" en "tenant-001"
    When el admin POST /admin/users con email "existente@hospital.com"
    Then respuesta 409 Conflict
    And mensaje "Email ya registrado en este tenant"
```

### RF-04/05/06: Login + MFA + Tokens
```gherkin
Feature: Autenticación segura con MFA y tokens JWT
  Background:
    Given usuario "medico@hospital.com" en tenant "tenant-001"
    And password hash Argon2id válido
    And rol "MEDICO" (MFA no requerido por defecto)
    And tenant policy MFA = false

  Scenario: Login exitoso sin MFA (rol MEDICO)
    When POST /auth/login con {email: "medico@hospital.com", password: "CorrectPass123!"}
    Then respuesta 200 OK
    And access_token JWT RS256 con claims {sub, roles:["MEDICO"], tenant_id:"tenant-001", session_id, jti, exp: +15min}
    And refresh_token opaco guardado en Redis con hash Argon2id (TTL 7d)
    And evento LOGIN_SUCCESS en auditoría

  Scenario: Login exitoso CON MFA (rol TENANT_ADMIN)
    Given usuario "admin@hospital.com" con rol "TENANT_ADMIN" (MFA requerido)
    And TOTP configurado (secret en DB)
    When POST /auth/login con credenciales válidas
    Then respuesta 200 MFA_CHALLENGE {method: "TOTP", challenge_id}
    When POST /auth/mfa con {code: "123456", challenge_id}
    And código TOTP válido (ventana ±30s)
    Then respuesta 200 OK con access_token + refresh_token
    And evento MFA_SUCCESS en auditoría

  Scenario: Fallo - MFA inválido (3 intentos → bloqueo 30min)
    Given usuario con MFA requerido
    When POST /auth/mfa con código inválido (3 veces consecutivas)
    Then respuesta 401 MFA Invalid (intentos 1-2)
    And respuesta 423 MFA Locked (intento 3)
    And evento MFA_LOCKED en auditoría
    And bloqueo expira en 30 minutos
```

### RF-07/08/09: Refresh Rotation + Blocklist + Revocación
```gherkin
Feature: Refresh Token Rotation, Blocklist y Revocación de Sesión
  Background:
    Given usuario autenticado con access_token (jti: "jti-abc") y refresh_token en Redis

  Scenario: Refresh exitoso con rotación
    When POST /auth/refresh con refresh_token válido
    Then respuesta 200 OK con NUEVO access_token (jti: "jti-def") y NUEVO refresh_token
    And refresh_token anterior ELIMINADO de Redis
    And evento TOKEN_REFRESHED en auditoría

  Scenario: Reuse Detection → Revocación total + Alerta CRÍTICA
    Given refresh_token "rt-xyz" usado una vez (rotado a "rt-new")
    When atacante reusa "rt-xyz" en POST /auth/refresh
    Then respuesta 401 Token Compromised
    And TODAS las sesiones del usuario REVOCADAS (blocklist access + DEL refresh + DEL session)
    And evento REFRESH_REUSE_ATTACK (CRITICAL) en auditoría
    And alerta enviada a #sec-alerts + Jira auto-ticket

  Scenario: Logout propio → Blocklist access + DEL refresh
    When POST /auth/logout con access_token válido
    Then respuesta 200 OK
    And JTI del access_token en Redis blocklist (TTL = exp restante)
    And refresh_token ELIMINADO de Redis
    And evento REVOKE_SESSION {reason: "logout"} en auditoría

  Scenario: Force-revoke por Admin → Revoca todas las sesiones
    Given admin TENANT_ADMIN autenticado
    When DELETE /admin/users/{user_id}/sessions
    Then respuesta 200 OK {revoked_sessions: N}
    And TODOS los JTI del usuario en blocklist
    And TODOS los refresh_tokens DEL
    And evento REVOKE_SESSION {reason: "force_revoke", requester_id: admin_id} en auditoría
    And SessionRevokedEvent publicado en message broker
```

### RF-10: Aislamiento Multi-tenant (RLS)
```gherkin
Feature: Aislamiento estricto multi-tenant
  Background:
    Given tenant "tenant-A" con usuario "user-A"
    And tenant "tenant-B" con usuario "user-B"

  Scenario: Cross-tenant attempt bloqueado
    Given token válido de "user-A" (tenant-A)
    When GET /api/users con header X-Tenant-ID: "tenant-B"
    Then respuesta 403 Forbidden
    And mensaje "Acceso cross-tenant denegado"
    And evento CROSS_TENANT_ATTEMPT en auditoría (WARN)
    And alerta SIEM generada

  Scenario: RLS enforza tenant_id en queries
    Given usuario "user-A" autenticado en tenant-A
    When consulta SELECT * FROM users
    Then solo retorna usuarios WHERE tenant_id = 'tenant-A'
    And EXPLAIN muestra "Row Level Security" activo
```

---

## 4. Matriz de Trazabilidad RF/RNF ↔ Diagramas UML (Week 01)

| Requisito                                | Caso de Uso (UC)           | Actividad (Act)                | Secuencia (Seq)                |
|------------------------------------------|----------------------------|--------------------------------|--------------------------------|
| RF-01, RF-02, RF-03                      | UC-01                      | Act-03, Act-04                 | Seq-01                         |
| RF-04, RF-05, RF-06, RF-11, RF-12, RF-13 | UC-02                      | Act-05, Act-06, Act-07, Act-08 | Seq-02, Seq-03, Seq-04, Seq-05 |
| RF-07                                    | UC-05                      | Act-10, Act-11                 | Seq-06                         |
| RF-08, RF-09                             | UC-03                      | Act-12, Act-13, Act-14, Act-15 | Seq-07, Seq-08                 |
| RF-10                                    | UC-01, UC-02, UC-03, UC-08 | Act-02, Act-05, Act-08         | Seq-01, Seq-02, Seq-05, Seq-09 |
| RF-14                                    | UC-15 (include)            | Act-17, Act-18                 | Seq-09, Seq-13                 |
| RF-15                                    | UC-07 (extend)             | Act-23 (alt)                   | Seq-17 (alt)                   |
| RNF-01 a RNF-15                          | —                          | —                              | — (Validación por pruebas NFR) |

---

## 5. Referencias a Código Existente (Integración)

| Componente                                         | Ubicación                                                                             | Notas                                                     |
|----------------------------------------------------|---------------------------------------------------------------------------------------|-----------------------------------------------------------|
| **BD Central (users, memberships)**                | `database/migrations/central/2026_08_11_000100_create_identity_and_memberships.php`   | `central_users`, `hospital_memberships` con RLS implícito |
| **BD Consent/Acceso**                              | `database/migrations/central/2026_08_11_000300_create_consent_and_access_control.php` | Auditoría inmutable, break-glass, triggers                |
| **Config DB Multi-tenant**                         | `config/database.php`                                                                 | Conexiones: `central`, `hospital` (PostgreSQL)            |
| **JWT Config (RS256/HS256)**                       | `config/jwt.php`                                                                      | `algo: HS256` actual → **migrar a RS256**                 |
| **Auth Guard API**                                 | `config/auth.php`                                                                     | Guard `api` con driver `jwt`                              |
| **Middleware JWT + Tenant**                        | `app/Http/Middleware/JwtAuth.php`, `TenantMiddleware.php`                             | Validación token + `X-Tenant-ID`                          |
| **AuthController (register/login/refresh/logout)** | `app/Http/Controllers/Api/V1/AuthController.php`                                      | Implementación actual Laravel + Eloquent                  |
| **Permisos/Roles**                                 | `config/permission.php`                                                               | Spatie Permission, team_id para multi-tenant              |

> **Nota de integración:** El diseño Week 03+ deberá respetar las tablas `central_users` y `hospital_memberships` existentes, migrando JWT a RS256 y añadow blocklist Redis, refresh rotation, MFA, y auditoría hash-chain.
