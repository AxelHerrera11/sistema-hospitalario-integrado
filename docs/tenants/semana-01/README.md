# Gestión de Usuarios, Tenants y Endurecimiento de Autenticación - Semana 1

## Proceso Modelado
**Alta de usuario por tenant, autenticación segura y revocación de sesión** (Código: **GUT-001**)

---

## Entregables Generados

| Archivo                | Tipo     | Descripción                                                                                                                    |
|------------------------|----------|--------------------------------------------------------------------------------------------------------------------------------|
| `narrativa-alcance.md` | Markdown | Narrativa completa: 4 actores + 3 sistemas, 3 flujos principales, 12 excepciones, 14 reglas de negocio, matriz de trazabilidad |
| `01-casos-de-uso.puml` | PlantUML | 8 actores, 18 UC, relaciones `<<include>>`/`<<extend>>`, 5 paquetes, notas detalladas MFA/Refresh/Blocklock                    |
| `02-actividad.puml`    | PlantUML | 10 swimlanes, 5 flujos paralelos (fork), decisiones multi-tenant, 12 excepciones mapeadas, referencias RN-01 a RN-14           |
| `03-secuencia.puml`    | PlantUML | 11 participantes, 6 grupos secuenciales, JWT RS256, MFA TOTP/WebAuthn, Refresh rotation+reuse, Blocklist Redis, Idle timeout   |
| `README.md`            | Markdown | Este archivo - índice de entregables                                                                                           |

---

## Cómo Renderizar los Diagramas

### Opción 1: VS Code + Extensión PlantUML
1. Instalar extensión "PlantUML" (jebbs.plantuml)
2. Abrir archivo `.puml`
3. `Ctrl+Shift+P` → "PlantUML: Export Current Diagram" o vista previa automática

### Opción 2: Línea de Comandos
```bash
# Requiere Java + Graphviz
java -jar plantuml.jar 01-casos-de-uso.puml
java -jar plantuml.jar 02-actividad.puml
java -jar plantuml.jar 03-secuencia.puml
```

### Opción 3: Online
- https://www.plantuml.com/plantuml/uml/
- https://kroki.io/plantuml/svg/ (codificar en base64)

---

## Matriz de Trazabilidad (Resumen)

| Regla de Negocio                          | Caso de Uso         | Actividad              | Secuencia              |
|-------------------------------------------|---------------------|------------------------|------------------------|
| RN-01: Aislamiento Multi-tenant           | UC-01, UC-02, UC-03 | Act-02, Act-05, Act-08 | Seq-02, Seq-05, Seq-08 |
| RN-02: RBAC por Tenant                    | UC-01, UC-04        | Act-03, Act-06         | Seq-03, Seq-06         |
| RN-03: MFA Adaptativo                     | UC-02               | Act-07, Act-08         | Seq-04, Seq-05         |
| RN-04: JWT RS256 + JWKS Rotation          | UC-02               | Act-09                 | Seq-06                 |
| RN-05: Refresh Rotation + Reuse Detection | UC-02, UC-05        | Act-10, Act-11         | Seq-07                 |
| RN-06: Blocklist Access Token             | UC-03, UC-05        | Act-12, Act-13         | Seq-08, Seq-09         |
| RN-07: Single Session Policy              | UC-02               | Act-14 (alt)           | Seq-10 (alt)           |
| RN-08: Force-Revoke Admin                 | UC-03               | Act-15                 | Seq-11                 |
| RN-09: Password Policy + HIBP             | UC-01, UC-06        | Act-04, Act-16         | Seq-12                 |
| RN-10: Auditoría Inmutable                | UC-04 (include)     | Act-17, Act-18         | Seq-13                 |
| RN-11: Lockout Progressivo                | UC-02               | Act-19, Act-20         | Seq-14                 |
| RN-12: Session Idle Timeout               | UC-05               | Act-21                 | Seq-15                 |
| RN-13: Device Fingerprinting              | UC-02               | Act-22                 | Seq-16                 |
| RN-14: JIT Provisioning SSO               | UC-07 (extend)      | Act-23 (alt)           | Seq-17 (alt)           |

---

## Reglas de Negocio Clave (Extracto)

| ID        | Regla                             | Implementación Técnica                                                         |
|-----------|-----------------------------------|--------------------------------------------------------------------------------|
| **RN-01** | Aislamiento Multi-tenant Estricto | `tenant_id` en todos queries + RLS PostgreSQL + TenantMiddleware               |
| **RN-02** | RBAC por Tenant                   | Roles: `TENANT_ADMIN`, `MEDICO`, `ENFERMERO`, `PACIENTE`, `AUDITOR`            |
| **RN-03** | MFA Adaptativo                    | `TENANT_ADMIN`/`AUDITOR` siempre MFA. TOTP (RFC 6238) + WebAuthn (FIDO2)       |
| **RN-04** | JWT RS256 + JWKS Rotation         | Access 15min, claves rotadas 24h, JWKS endpoint público                        |
| **RN-05** | Refresh Token Rotation            | Rotación cada uso + Reuse Detection → revocación total + alerta CRÍTICA        |
| **RN-06** | Blocklist Access Tokens           | Redis SET `blocklist:{jti}` TTL = exp restante. Validación en middleware       |
| **RN-07** | Single Session Configurable       | Por tenant. Nuevo login revoca anterior (device fingerprint)                   |
| **RN-08** | Force-Revoke por Admin            | `TENANT_ADMIN`/`PLATFORM_ADMIN` revocan todas las sesiones de un usuario       |
| **RN-09** | Password Policy + Breach Check    | Min 12, complejidad, historial 5, HaveIBeenPwned API                           |
| **RN-10** | Auditoría Inmutable Seguridad     | Eventos: LOGIN, MFA, REVOKE, LOCKOUT, CREATE/UPDATE/DELETE. Hash chain SHA-256 |
| **RN-11** | Lockout Progressivo               | 5→15min, 10→1h, 20→24h + notificación admin. Contadores separados login/MFA    |
| **RN-12** | Session Idle Timeout              | 15 min inactividad → job c/5min limpia sesiones + blocklist access             |
| **RN-13** | Device Fingerprinting             | device_id, IP, UA, trust_score. Alertar en dispositivo nuevo (opcional)        |
| **RN-14** | JIT Provisioning SSO              | SAML/OIDC claim mapping → roles. Auto-crear usuario + asignar tenant           |

---

## Excepciones Manejadas

| Código     | Escenario                 | HTTP | Acción Auditoría      | Mitigación                    |
|------------|---------------------------|------|-----------------------|-------------------------------|
| **EXC-01** | Credenciales inválidas    | 401  | LOGIN_FAILED          | Contador + lockout progresivo |
| **EXC-02** | Cuenta bloqueada          | 423  | ACCOUNT_LOCKED        | 15m/1h/24h + notif admin      |
| **EXC-03** | Tenant inactivo           | 403  | TENANT_INACTIVE       | Validación en middleware      |
| **EXC-04** | Tenant sin cupo           | 400  | TENANT_QUOTA_EXCEEDED | Límite por plan               |
| **EXC-05** | MFA inválido              | 401  | MFA_FAILED            | Contador MFA + lockout 30m    |
| **EXC-06** | MFA bloqueado             | 423  | MFA_LOCKED            | 30 min                        |
| **EXC-07** | Access token expirado     | 401  | TOKEN_EXPIRED         | Forzar refresh                |
| **EXC-08** | Refresh inválido/expirado | 401  | REFRESH_INVALID       | Forzar login completo         |
| **EXC-09** | Token en blocklist        | 401  | TOKEN_REVOKED         | Revocación inmediata          |
| **EXC-10** | Cross-tenant attempt      | 403  | CROSS_TENANT_ATTEMPT  | Alerta SIEM                   |
| **EXC-11** | Usuario desactivado       | 403  | ACCOUNT_DISABLED      | Soft delete + auditoría       |
| **EXC-12** | Password expirado         | 400  | PASSWORD_EXPIRED      | Forzar cambio obligatorio     |

---

## Stack Técnico Asumido

- **Auth**: JWT RS256 (access 15min) + Refresh Token opaco (7d, rotación, Redis)
- **MFA**: TOTP (Google Authenticator/Authy) + WebAuthn/FIDO2 (Passkeys)
- **JWKS**: Rotación claves cada 24h, endpoint `/.well-known/jwks.json`
- **Redis**: Cluster para tokens, blocklist, sessions, rate limiting, lockouts
- **DB**: PostgreSQL 15+ con Row Level Security (RLS) por `tenant_id`
- **Rate Limiting**: Sliding window por IP/email (Redis)
- **Auditoría**: Tabla append-only + hash chain SHA-256 (`hash_n = SHA256(hash_{n-1} \| payload \| ts)`)
- **Eventos**: Message broker (Kafka/RabbitMQ) para `SessionRevokedEvent` → microservicios
- **Compliance**: OWASP ASVS L2, NIST 800-63B AAL2, ISO 27001 A.9

---

## Flujos Cubiertos en Diagramas

1. **Creación de Usuario** (Admin) → Validación tenant/cupo → Policy password → Email activación → Auditoría
2. **Login + MFA** (Usuario) → Rate limit → Credenciales → MFA adaptativo → Tokens + Redis → Single session → Auditoría
3. **Refresh Token** (Sistema) → Rotation + Reuse Detection → Nuevos tokens → Auditoría
4. **Revocación Sesión** (Usuario/Admin) → Blocklist access + DEL refresh + DEL session → Evento broker → Auditoría
5. **Validación Token** (Middleware cada request) → JWKS → Claims → Blocklist → RLS tenant → Session activity
6. **Idle Timeout** (Job background) → Scan sesiones >15min → Cleanup + blocklist → Auditoría
