# Semana 14 — Plan SQA y Confiabilidad
## 1. Resumen Ejecutivo
### Objetivo del Plan SQA
Establecer un marco integral de **Aseguramiento de la Calidad del Software (SQA)** para el módulo **"Gestión de usuarios, tenants y endurecimiento de autenticación"**, garantizando:
- **Confiabilidad** del flujo crítico: Alta usuario → Login+MFA → Revocación sesión
- **Seguridad** ante amenazas (OWASP ASVS L2, NIST 800-63B AAL2)
- **Disponibilidad** y **Performance** bajo carga
- **Cumplimiento normativo** (Ley 26.529, HIPAA, GDPR)
- **Trazabilidad** completa: Requisitos → Código → Tests → Evidencia

### Alcance del Plan SQA
| Área               | Alcance                                                        | Métricas Clave                                             |
|--------------------|----------------------------------------------------------------|------------------------------------------------------------|
| **Funcionalidad**  | Login, MFA, Refresh, Revocación, Admin, Auditoría              | Tasa éxito ≥ 99.9%, Tiempo < SLA                           |
| **Seguridad**      | JWT RS256, MFA TOTP/WebAuthn, Refresh Rotation, Blocklist, RLS | 0 vulnerabilidades Críticas/High, 0 falsos positivos reuse |
| **Performance**    | Login p95 < 300ms, Token val p99 < 50ms, Refresh p95 < 100ms   | Percentiles bajo carga                                     |
| **Disponibilidad** | 99.99% (excl. mantenimiento), RPO=0, RTO < 15min               | SLA cumplido 12 meses                                      |
| **Seguridad**      | 0 vulnerabilidades Críticas/High en producción                 | Penetration testing trimestral                             |
| **Accesibilidad**  | WCAG 2.1 AA (0 violaciones axe-core)                           | axe-core 0 violations AA                                   |
| **Observabilidad** | 100% requests traced, alertas < 5min                           | MTTR < 15min, MTBF > 720h                                  |

## 2. Métricas de Calidad (5-7 KPIs)
| #       | Métrica (KPI)                            | Descripción                               | Objetivo               | Fuente                           | Frecuencia               | Responsable   | Umbral Alerta                  |
|---------|------------------------------------------|-------------------------------------------|------------------------|----------------------------------|--------------------------|---------------|--------------------------------|
| **M1**  | **Tasa Éxito Login**                     | % logins exitosos / intentos totales      | ≥ 99.5%                | Auth Service Logs                | Tiempo real (dashboard)  | Auth Team     | < 99% → Alerta P1              |
| **M2**  | **Tiempo Login p95**                     | Percentil 95 latencia login (ms)          | < 300ms                | APM (New Relic/Datadog)          | Continuo                 | Platform Team | > 500ms → Alerta P2            |
| **M3**  | **Tasa Éxito MFA (1er intento)**         | % MFA válidos en 1er intento / desafíos   | ≥ 95%                  | Auth Service Metrics             | Tiempo real              | Auth Team     | < 90% → Alerta P2              |
| **M4**  | **Latencia Validación Token p99**        | Percentil 99 validación JWT (ms)          | < 50ms                 | API Gateway Metrics              | Continuo                 | Platform Team | > 100ms → Alerta P2            |
| **M5**  | **Tasa Revocación Exitosa**              | % force-revoke exitosos / intentos        | 100%                   | Admin Service Logs               | Por evento               | Admin Team    | < 100% → Alerta P1             |
| **M6**  | **Detección Reuse Refresh**              | Tiempo detección → revocación total (ms)  | < 500ms                | Auth Service Metrics             | Por evento               | Security Team | > 1s → Alerta P1               |
| **M7**  | **Disponibilidad Servicio Auth**         | % uptime servicio autenticación           | 99.99%                 | Uptime Monitor (Pingdom/Datadog) | Mensual                  | Platform Team | < 99.95% → Alerta P1           |
| **M8**  | **Vulnerabilidades Críticas/High**       | Cantidad vuln Crit/High en producción     | 0                      | Trivy/CodeQL/Snyk + PenTest      | Por release / Trimestral | Security Team | > 0 → Alerta P0                |
| **M8**  | **Cobertura Tests Domain/Use Cases**     | % cobertura código Domain/Use Cases       | Domain ≥ 95%, UC ≥ 90% | CI/CD Coverage Report            | Por PR / Diario          | QA Team       | Domain < 90% → Bloquear merge  |
| **M10** | **Violaciones Accesibilidad (axe-core)** | # violaciones WCAG 2.1 AA en CI           | 0                      | CI/CD axe-core report            | Por PR                   | Frontend Team | > 0 → Bloquear merge           |
| **M11** | **MTTR (Mean Time To Recovery)**         | Tiempo medio recuperación incidentes Auth | < 15 min               | Incident Tracker (PagerDuty)     | Por incidente            | SRE Team      | > 30min → Postmortem           |
| **M12** | **MTBF (Mean Time Between Failures)**    | Tiempo medio entre fallos Auth            | > 720h (30 días)       | Incident Tracker                 | Mensual                  | SRE Team      | < 168h → Revisión arquitectura |

## 3. Matriz de Riesgos (Top 10)
| ID      | Riesgo                                                  | Probabilidad | Impacto | Nivel          | Controles Preventivos                                                                                                       | Controles Detectivos                                                   | Responsable        | Acción si Ocurre                                                             |
|---------|---------------------------------------------------------|--------------|---------|----------------|-----------------------------------------------------------------------------------------------------------------------------|------------------------------------------------------------------------|--------------------|------------------------------------------------------------------------------|
| **R01** | **Compromiso Refresh Token** (theft, replay)            | Media        | Crítico | 🔴 **Crítico** | Refresh Rotation + Reuse Detection + Blocklist + Short TTL (15min access)                                                   | SIEM Alert + Jira Auto-ticket + Email/Slack/PagerDuty                  | Security Team      | Revocación total automática + Forced Re-login + Jira + SIEM + Postmortem     |
| **R02** | **Credential Stuffing / Brute Force**                   | Alta         | Alto    | 🔴 **Crítico** | Rate Limiting (Redis sliding window), Lockout progresivo (5/10/20), MFA obligatorio admins, CAPTCHA opcional                | SIEM Alert (failed login spike), Account Lockout alerts                | Auth Team          | Account Lockout automático + Alertas + Investigación forense                 |
| **R03** | **Compromiso Credenciales Admin** (Phishing, keylogger) | Media        | Crítico | 🔴 **Crítico** | MFA obligatorio admins (TOTP/WebAuthn), Force-Revoke admin, Session monitoring, Device Trust                                | SIEM Alert (geo/anomalous login), Admin alert email                    | Security Team      | Force-Revoke inmediato + MFA reset + Investigación + Comunicación            |
| **R04** | **Fuga Datos Sensibles (PII/PHI)**                      | Baja         | Crítico | 🟠 **Alto**    | Cifrado AES-256 reposo + TLS 1.3 tránsito, RLS PostgreSQL, Tokenization, DLP                                                | DLP Alerts, Audit Log Monitoring, DLP Scans                            | Privacy/Compliance | Contención + Notificación regulador (72h) + Forense + Notificación afectados |
| **R05** | **Ataque DDoS / DoS en Auth**                           | Media        | Alto    | 🟠 **Alto**    | Rate Limiting (Redis sliding window), WAF (Cloudflare/AWS Shield), Auto-scaling K8s, Circuit Breaker                        | Latency/Error rate alerts, Auto-scaling events                         | Platform Team      | WAF Block + Rate Limit agresivo + Scale up + Página mantenimiento            |
| **R05** | **Falla Redis Cluster (Tokens/Sessions/Blocklist)**     | Baja         | Crítico | 🟠 **Alto**    | Redis Cluster HA (3 masters + replicas), Persistence AOF, Multi-AZ, Fallback local cache                                    | Redis Health Checks, Replication Lag Alerts, Failover Time < 30s       | Platform Team      | Failover automático + Cache local fallback + Degraded mode (read-only)       |
| **R06** | **Falla PostgreSQL (Central Users / Audit)**            | Baja         | Crítico | 🟠 **Alto**    | Patroni HA (Sync Replication), Multi-AZ, Automated Failover < 30s, Read Replicas                                            | PG Health Checks, Replication Lag < 1s, Failover Alerts                | Platform Team      | Failover automático < 30s + Read Replicas para reads                         |
| **R07** | **Fuga Refresh Token (Log/URL/Referer)**                | Media        | Alto    | 🟠 **Alto**    | Tokens solo en HttpOnly Secure SameSite=Strict Cookies / Auth Header, No en URL/Logs, Short TTL                             | Log Scanning (SIEM), Token Leak Detection (GitGuardian/TruffleHog)     | Security Team      | Rotación inmediata claves + Revocación masiva + Rotación JWKS                |
| **R08** | **Bypass MFA (Social Engineering / SIM Swap)**          | Baja         | Alto    | 🟡 **Medio**   | WebAuthn/FIDO2 (Passkeys) preferido, TOTP + Device Trust, Recovery Codes, Admin approval para reset MFA                     | MFA Bypass Attempts Alert, Unusual Device/Geo Alerts                   | Auth Team          | Account Lock + Manual Verification + MFA Reset + Audit                       |
| **R09** | **Insider Threat (Admin Malicioso / Comprometido)**     | Baja         | Crítico | 🟠 **Alto**    | Force-Revoke Admin + Dual Approval (Dual Admin), Audit Trail Inmutable, SIEM Monitoring, Just-in-Time Access                | SIEM UEBA (User Behavior Analytics), Privilege Escalation Alerts       | Security + HR      | Suspensión inmediata + Force-Revoke + Investigación + Legal                  |
| **R10** | **Falla Dependencia Externa (HIBP, Email, SIEM, Jira)** | Media        | Medio   | 🟡 **Medio**   | Circuit Breaker (Resilience4j/Hystrix), Fallback Local (HIBP cache 24h), Queue + Retry (Exponential Backoff), Degraded Mode | Circuit Breaker State Alerts, Queue Depth Alerts, Fallback Mode Active | Platform Team      | Degraded Mode Activado + Alertas + Fallback Manual                           |

## 4. Controles Preventivos y Detectivos
### 4.1 Controles Preventivos (Por Capa)
| Capa               | Control                            | Descripción                                                                    | Implementación                       |
|--------------------|------------------------------------|--------------------------------------------------------------------------------|--------------------------------------|
| **Red**            | WAF + DDoS Protection              | Cloudflare / AWS Shield + Rate Limiting Global                                 | Cloudflare Pro / AWS Shield Advanced |
| **Red**            | Network Segmentation               | VPC Private Subnets, Security Groups, NACLs                                    | AWS VPC / Security Groups            |
| **Aplicación**     | Secure Coding                      | SAST (SonarQube/CodeQL), DAST (OWASP ZAP), Dependency Scanning (Trivy/Snyk)    | CI/CD Pipeline                       |
| **Aplicación**     | Input Validation                   | Zod Schemas + Zod Validation Middleware                                        | Zod + Middleware                     |
| **Aplicación**     | Output Encoding                    | Auto-escaping React/JSX, DOMPurify para HTML                                   | React + DOMPurify                    |
| **Aplicación**     | CSP / Security Headers             | Helmet.js / Next.js Headers, CSP Strict, HSTS, X-Frame-Options                 | Next.js / Helmet                     |
| **Datos**          | Cifrado Reposo                     | AES-256-GCM (AWS KMS / HashiCorp Vault)                                        | AWS KMS / Vault                      |
| **Datos**          | Cifrado Tránsito                   | TLS 1.3 Only, Certificate Pinning Mobile                                       | TLS 1.3, Cert Manager                |
| **Datos**          | Tokenization / Pseudonimización    | PII/PHI Tokenization (Vault Transit)                                           | Vault Transit Engine                 |
| **Identidad**      | MFA Adaptativo                     | TOTP (RFC 6238) + WebAuthn/FIDO2 + Device Trust                                | OTPHP + WebAuthn Library             |
| **Identidad**      | JWT RS256 + JWKS Rotation          | Lcobucci JWT, 24h Rotation, JWKS Endpoint                                      | Lcobucci JWT                         |
| **Identidad**      | Refresh Rotation + Reuse Detection | Rotación cada uso, Argon2id Hash, Reuse Detection → Revocación Total           | Redis + Argon2id                     |
| **Identidad**      | Blocklist JWT (Redis)              | JTI en Redis SET + TTL = exp-now, Middleware Validation                        | Redis Cluster                        |
| **Datos**          | RLS PostgreSQL                     | Row Level Security por tenant_id, TenantMiddleware                             | PostgreSQL RLS + Middleware          |
| **Datos**          | Auditoría Inmutable                | Append-only, Trigger BEFORE UPDATE/DELETE, Hash Chain SHA-256                  | PostgreSQL Triggers + Hash Chain     |
| **Infra**          | secrets Management                 | HashiCorp Vault / AWS Secrets Manager / AWS KMS                                | Vault / AWS KMS                      |
| **Infra**          | Image Security                     | Trivy Scan (CI), SBOM (Syft), Cosign Signing (SLSA L2)                         | Trivy + Syft + Cosign                |
| **Infra**          | Kubernetes Hardening               | Non-root, ReadOnlyRootFS, Drop Capabilities, NetworkPolicy, PSP/OPA Gatekeeper | K8s + OPA Gatekeeper                 |
| **Infra**          | Secrets Rotation                   | Vault Dynamic Secrets / AWS Secrets Manager Rotation                           | Vault / AWS Secrets Manager          |
| **Red**            | mTLS Service Mesh                  | Istio / Linkerd (STRICT mode)                                                  | Istio                                |
| **Observabilidad** | Distributed Tracing                | OpenTelemetry + Jaeger/Tempo                                                   | OTel SDK + Collector                 |
| **Observabilidad** | Metrics + Alerting                 | Prometheus + Grafana + Alertmanager                                            | Prometheus Stack                     |
| **Observabilidad** | Logs Centralizados                 | Loki + Promtail / Elasticsearch + Fluent Bit                                   | Loki / ELK                           |
| **Observabilidad** | Audit Trail Inmutable              | PostgreSQL Triggers + Hash Chain SHA-256                                       | PostgreSQL Triggers                  |

### 4.2 Controles Detectivos (Monitoreo y Alertas)
| Métrica / Evento                           | Herramienta                | Regla de Alerta                    | Severidad  | Canal                                               | Respuesta Esperada              |
|--------------------------------------------|----------------------------|------------------------------------|------------|-----------------------------------------------------|---------------------------------|
| **Failed Login Spike** (> 50/min/tenant)   | SIEM (Splunk/Elastic)      | Rate > threshold 5min              | 🔴 Crítica | PagerDuty + Slack #security-alerts                  | Lockout auto + Investigation    |
| **MFA Failure Spike** (> 20%/5min)         | SIEM / Prometheus          | Rate > 20% / 5min                  | 🟠 Alta    | Slack #security-alerts                              | MFA Lockout + Investigation     |
| **Refresh Reuse Detected**                 | Auth Service + SIEM        | Event = REFRESH_REUSE_DETECTED     | 🔴 Crítica | PagerDuty + Slack #security-alerts + Email Security | Immediate Revocation + Forensic |
| **Refresh Token Reuse Rate** > 0           | Prometheus / Grafana       | Counter > 0                        | 🔴 Crítica | PagerDuty                                           | Immediate Investigation         |
| **Token Validation p99 > 100ms**           | Grafana / Datadog          | p99 > 100ms for 5min               | 🟠 Alta    | Slack #platform-alerts                              | Scale / Optimize                |
| **Login p95 > 500ms**                      | Grafana / Datadog          | p95 > 500ms for 5min               | 🟠 Alta    | Slack #platform-alerts                              | Scale / Optimize                |
| **Redis Memory > 85%**                     | Prometheus / Grafana       | Used Memory > 85%                  | 🟠 Alta    | Slack #infra-alerts                                 | Scale / Evict                   |
| **PostgreSQL Replication Lag > 1s**        | Patroni / Patroni API      | Lag > 1s for 1min                  | 🟠 Alta    | PagerDuty + Slack #infra-alerts                     | Failover / Investigate          |
| **PostgreSQL Replication Lag > 10s**       | Patroni / Prometheus       | Lag > 10s                          | 🔴 Crítica | PagerDuty                                           | Emergency Failover              |
| **Circuit Breaker Open (HIBP/Email/SIEM)** | Resilience4j / Hystrix     | Circuit Breaker State = OPEN       | 🟠 Alta    | Slack #platform-alerts                              | Degraded Mode + Alert           |
| **Queue Depth (Pending Actions) > 1000**   | Redis / Prometheus         | Queue Size > 1000 for 5min         | 🟡 Media   | Slack #platform-alerts                              | Scale Workers / Investigate     |
| **Failed Deployment Rate > 10%**           | GitHub Actions / ArgoCD    | Failure Rate > 10% last 10 deploys | 🟠 Alta    | Slack #devops-alerts                                | Rollback / Fix Pipeline         |
| **SSL Cert Expiry < 30 days**              | Cert Manager / Custom      | Expiry < 30d                       | 🟡 Media   | Slack #infra-alerts                                 | Renew Cert                      |
| **SSL Cert Expiry < 7 days**               | Cert Manager / Custom      | Expiry < 7d                        | 🔴 Crítica | PagerDuty                                           | Emergency Renew                 |
| **Disk Usage > 85% (Any Node)**            | Prometheus / Node Exporter | Disk > 85%                         | 🟠 Alta    | Slack #infra-alerts                                 | Cleanup / Expand                |
| **CPU Saturation > 90% (5min)**            | Prometheus / cAdvisor      | CPU > 90% 5min                     | 🟠 Alta    | Slack #infra-alerts                                 | Scale / Optimize                |

## 5. Plan de Pruebas de Confiabilidad (Reliability Testing)
| Tipo Prueba                             | Herramienta                         | Frecuencia            | Alcance                                                                  | Criterio Éxito                                   |
|-----------------------------------------|-------------------------------------|-----------------------|--------------------------------------------------------------------------|--------------------------------------------------|
| **Load Testing**                        | k6                                  | Por Release + Semanal | Login, MFA, Refresh, Revoke, Audit                                       | p95 < SLA, Error Rate < 0.1%                     |
| **Stress Testing**                      | k6                                  | Mensual               | Ramp up to 2x expected peak                                              | Graceful Degradation, No Data Loss               |
| **Spike Testing**                       | k6                                  | Mensual               | Sudden 10x traffic spike                                                 | Auto-scaling < 2min, No Errors                   |
| **Soak Testing**                        | k6                                  | Mensual (4h)          | Sustained 1.5x peak                                                      | No Memory Leaks, Stable Latency                  |
| **Chaos Engineering**                   | LitmusChaos / Gremlin               | Quincenal             | Pod Kill, Network Partition, Redis/DB Failover, Latency Injection        | RTO < 15min, RPO=0, No Data Loss                 |
| **Failover Testing**                    | Patroni / K8s                       | Mensual               | PG Failover, Redis Failover, K8s Node Drain                              | RTO < 30s, RPO=0                                 |
| **Chaos: Redis Failure**                | LitmusChaos                         | Quincenal             | Redis Master Kill, Network Partition                                     | Auto Failover < 30s, No Data Loss                |
| **Chaos: DB Failover**                  | LitmusChaos / Patroni               | Quincenal             | PG Primary Kill                                                          | Failover < 30s, No Data Loss                     |
| **Chaos: Network Partition**            | LitmusChaos                         | Mensual               | Network Partition Auth Service ↔ Redis/DB                                | Circuit Breaker Works, Degraded Mode             |
| **Penetration Testing**                 | External Vendor / Internal Red Team | Trimestral            | OWASP Top 10, AuthZ/AuthN, Business Logic                                | 0 Critical/High, < 5 Medium                      |
| **Dependency Confusion / Supply Chain** | Trivy / Snyk / Syft / Cosign        | Por PR + Semanal      | SBOM, Signature Verification, License Check                              | 0 Critical/High, SLSA L2                         |
| **Penetration Testing (Auth Focus)**    | Internal Red Team                   | Trimestral            | AuthZ Bypass, Token Theft, Session Hijack, MFA Bypass                    | 0 Critical/High                                  |
| **Red Team Exercise**                   | External Vendor                     | Anual                 | Full Kill Chain: Recon → Initial Access → Priv Esc → Persistence → Exfil | Detection < 1hr, Containment < 4hr               |
| **Disaster Recovery Drill**             | Manual + Automated                  | Semestral             | RTO < 4h, RPO = 0, Full Restore from Backup                              | Successful Restore < 4h, Data Integrity Verified |
| **Backup Restore Test**                 | Automated + Manual                  | Mensual               | Point-in-Time Recovery (PITR), Full Restore                              | Restore < 2h, Data Integrity Verified            |

## 6. Gestión de Incidentes y Postmortems
### 6.1 Proceso de Respuesta a Incidentes (IRP)
| Fase             | Actividad                                                      | Responsable               | SLA            | Herramientas                                |
|------------------|----------------------------------------------------------------|---------------------------|----------------|---------------------------------------------|
| **Detección**    | Alertas automáticas (SIEM, Prometheus, Uptime)                 | Monitoring                | < 1 min        | Prometheus, Splunk, PagerDuty, Uptime Robot |
| **Triaje**       | Clasificación severidad (P0-P3), Asignación Owner              | On-Call Engineer          | < 5 min        | PagerDuty, Slack                            |
| **Contención**   | Acciones inmediatas (Block IP, Revoke Tokens, Scale, Failover) | On-Call + SRE             | < 15 min (P0)  | Runbooks, Runbook Automation                |
| **Erradicación** | Root Cause Removal (Patch, Revoke, Rotate Keys, Block IP)      | On-Call + SRE + Sec       | < 1 hora (P0)  | Runbooks, Playbooks                         |
| **Recuperación** | Restore Service, Verify Health, Monitor                        | SRE + Dev                 | < 4 horas (P0) | Runbooks, Deploy, Smoke Tests               |
| **Postmortem**   | RCA (5 Whys), Timeline, Action Items, Blameless                | Incident Commander + Team | < 48 horas     | Postmortem Template, Jira, Confluence       |
| **Seguimiento**  | Action Items Tracking, Verification, Closure                   | Incident Commander        | < 2 semanas    | Jira, Confluence                            |

### 6.2 Plantilla Postmortem (Blameless)
```markdown
# Postmortem: [INC-ID] - [Título Incidente]
## Resumen Ejecutivo
- **Fecha/Hora Inicio:** YYYY-MM-DD HH:MM:SS (UTC)
- **Fecha/Hora Fin:** YYYY-MM-DD HH:MM:SS (UTC)
- **Duración Total:** Xh Ym Zs
- **Severidad:** P0 / P1 / P2 / P3
- **Servicios Afectados:** [Auth, Admin, Patient, Clinical, Audit]
- **Usuarios Afectados:** X usuarios / Y% base

## Línea de Tiempo (Timeline)
| Hora (UTC) | Evento    | Acción           | Responsable |
|------------|-----------|------------------|-------------|
| HH:MM:SS   | Detección | Alerta PagerDuty | Monitoring  |
| HH:MM:SS   | Triaje    | On-Call Eng      | On-Call     |
| ...        | ...       | ...              | ...         |

## Análisis de Causa Raíz (5 Whys)
1. **¿Qué pasó?** [Descripción técnica]
2. **¿Por qué pasó?** [Causa inmediata]
3. **¿Por qué pasó eso?** [Causa subyacente]
4. **¿Por qué pasó eso?** [Causa raíz sistémica]
5. **¿Por qué pasó eso?** [Causa raíz organizacional/procesal]
**Causa Raíz Identificada:** [Descripción concisa]

## Impacto
- **Usuarios Afectados:** X (Y% de base activa)
- **Transacciones Perdidas:** X
- **Datos Comprometidos:** No / Sí (detalle)
- **Impacto Negocio:** [Descripción]

## Acciones de Contención
- [Acción 1] - Hora - Responsable - Resultado
- [Acción 2] - Hora - Responsable - Resultado

## Acciones Correctivas (Action Items)
| ID     | Acción Correctiva | Responsable      | Fecha Límite | Estado  | Verificación   |
|--------|-------------------|------------------|--------------|---------|----------------|
| AI-001 | [Descripción]     | [Equipo/Persona] | YYYY-MM-DD   | Abierto | [Cómo validar] |
| AI-002 | [Descripción]     | [Equipo/Persona] | YYYY-MM-DD   | Abierto | [Cómo validar] |

## Lecciones Aprendidas
- [Lección 1]
- [Lección 2]

## Prevención Futura
- [Medida Preventiva 1] - Responsable - Fecha
- [Medida Preventiva 2] - Responsable - Fecha

## Enlaces
- [Incidente PagerDuty]
- [Ticket Jira]
- [Runbook Usado]
- [Dashboards Grafana]
- [Logs Relevantes]

**Estado Postmortem:** Borrador / Revisión / Aprobado / Publicado
**Revisado Por:** [Nombre] - [Rol] - [Fecha]
**Aprobado Por:** [Nombre] - [Rol] - [Fecha]
```
## 7. Gestión de Cambios y Configuración (CMDB)
| Elemento                                           | Herramienta                         | Responsable           | Frecuencia        |
|----------------------------------------------------|-------------------------------------|-----------------------|-------------------|
| **Infraestructura (K8s, DB, Redis, DNS)**          | Terraform + GitOps (ArgoCD)         | Platform Team         | Continuous        |
| **Aplicación (Imagen Docker, Config, Secrets)**    | ArgoCD + Helm + SealedSecrets       | Platform Team         | Continuous        |
| **Base de Datos (Schema, Migraciones)**            | Flyway / Liquibase + GitOps         | DBA + Platform        | Per Migration     |
| **Secrets / Certificados**                         | Vault / Cert-Manager / Cert-Manager | Platform + Security   | Rotation Schedule |
| **Políticas Seguridad (WAF, NetworkPolicy, RBAC)** | GitOps (ArgoCD) + OPA Gatekeeper    | Security + Platform   | Per Change        |
| **Feature Flags**                                  | LaunchDarkly / Unleash / Custom     | Product + Engineering | Per Feature       |
| **Feature Flags (Auth)**                           | LaunchDarkly / Custom               | Auth Team             | Per Feature       |

## 8. Gestión de Proveedores y Dependencias (Supply Chain Security)
| Categoría                             | Herramienta                      | Frecuencia          | Acción si Hallazgo                |
|---------------------------------------|----------------------------------|---------------------|-----------------------------------|
| **Dependencias Directas/Transitivas** | npm audit + Snyk / Trivy         | Por PR + Semanal    | Bloquear merge si Critical/High   |
| **Imágenes Base Docker**              | Trivy / Grype / Hadolint         | Por Build + Semanal | Rebuild + Patch Base Image        |
| **Licencias**                         | FOSSA / License Checker          | Por PR              | Bloquear si Licencia Incompatible |
| **SBOM (Software Bill of Materials)** | Syft + CycloneDX                 | Por Release         | Publicar SBOM + Attest            |
| **Firmado Imágenes/Artefactos**       | Cosign / Sigstore (SLSA Level 2) | Por Build           | Verificar Firma en Deploy         |
| **Provenance (SLSA L2)**              | Cosign + SLSA Provenance         | Por Build           | Verificar Provenance en Deploy    |
| **Vulnerabilidades Base Images**      | Trivy / Grype                    | Diario (Scheduled)  | Rebuild Base Image                |
| **Licencias Copyleft (GPL/AGPL)**     | License Checker                  | Por PR              | Bloquear Merge                    |

## 9. Métricas de Madurez SQA (SQA Maturity Model)
| Dimensión                         | Nivel Actual (1-5) | Objetivo | Acciones para Mejorar                                   |
|-----------------------------------|--------------------|----------|---------------------------------------------------------|
| **Gestión de Requisitos**         | 4                  | 5        | Trazabilidad bidireccional completa + Baselines         |
| **Planificación SQA**             | 4                  | 5        | Plan SQA formal + Métricas predictivas                  |
| **Gestión de Configuración**      | 4                  | 5        | GitOps Full + Policy as Code (OPA)                      |
| **Aseguramiento Calidad Proceso** | 3                  | 5        | Quality Gates automatizados en todos los gates          |
| **Verificación y Validación**     | 4                  | 5        | Contract Testing + Chaos Engineering + Mutation Testing |
| **Auditorías SQA**                | 3                  | 5        | Auditorías trimestrales formales + Checklists           |
| **Gestión de No Conformidades**   | 4                  | 5        | Blameless Postmortems + Action Tracking automatizado    |
| **Mejora Continua**               | 4                  | 5        | Retrospectivas + Kaizen + Quality Gates predictivos     |
| **Formación y Competencias**      | 4                  | 5        | Training Plan + Certificaciones (Security, Cloud, A11y) |
| **Gestión Proveedores**           | 3                  | 5        | Vendor Risk Assessment + Contractual SLAs               |
**Nivel Actual Promedio: 3.7/5 → Objetivo: 4.5/5 (Próximos 12 meses)**

## 9. Cronograma de Actividades SQA (Próximos 6 Meses)
| Mes       | Actividad                                         | Responsable          | Entregable                         |
|-----------|---------------------------------------------------|----------------------|------------------------------------|
| **Mes 1** | Ejecutar Plan Acción FTR (Sprint 1-2)             | Equipo Dev + QA      | Hallazgos Críticos/High = 0        |
| **Mes 1** | Penetration Testing (Auth Focus)                  | Security Vendor      | Reporte Pentest + Remediation Plan |
| **Mes 1** | Configurar Chaos Engineering (LitmusChaos)        | Platform + SRE       | Chaos Experiments Running          |
| **Mes 2** | Ejecutar Chaos Experiments (Redis, DB, Network)   | SRE + Platform       | Chaos Report + Improvements        |
| **Mes 2** | Load/Stress/Soak Testing (k6)                     | QA + Platform        | Performance Baseline + Bottlenecks |
| **Mes 2** | Configurar Contract Testing (Pact)                | QA + Dev             | Pact Broker + Consumer Contracts   |
| **Mes 3** | Penetration Testing (Re-test)                     | Security Vendor      | 0 Critical/High                    |
| **Mes 3** | Chaos Engineering: Network Partition, Clock Drift | SRE                  | Resilience Improvements            |
| **Mes 3** | Dependency Confusion / Supply Chain Audit         | Security + DevOps    | SBOM + Provenance Verified         |
| **Mes 4** | Disaster Recovery Drill (Full)                    | SRE + Platform + DBA | DR Report + RTO/RPO Verified       |
| **Mes 4** | Red Team Exercise (Auth Focus)                    | External Vendor      | Detection/Containment Metrics      |
| **Mes 5** | Accessibility Audit (External Expert)             | A11y Expert          | WCAG 2.1 AA Certified              |
| **Mes 5** | SQA Maturity Assessment                           | QA Lead + External   | Maturity Level 4.5 Target          |
| **Mes 6** | SQA Annual Report + Roadmap Next 12M              | QA Lead + Management | SQA Maturity 4.5/5                 |

## 10. Presupuesto SQA (Estimado Anual)
| Concepto                                                 | Costo Estimado (USD) | Frecuencia | Justificación                    |
|----------------------------------------------------------|----------------------|------------|----------------------------------|
| **Penetration Testing (External)**                       | $25,000              | 2x/año     | Compliance + Risk Reduction      |
| **Red Team Exercise**                                    | $50,000              | 1x/año     | Advanced Threat Simulation       |
| **Chaos Engineering Platform**                           | $15,000              | Anual      | LitmusChaos/Gremlin License      |
| **Load Testing (k6 Cloud / Grafana Cloud)**              | $10,000              | Mensual    | Continuous Performance           |
| **Pen Testing Tools (Burp Suite Pro, etc.)**             | $5,000               | Anual      | Tooling                          |
| **Security Training (Team)**                             | $10,000              | Semestral  | Secure Coding, Threat Modeling   |
| **Accessibility Audit (External)**                       | $8,000               | Anual      | WCAG 2.1 AA Certification        |
| **Chaos Engineering Platform**                           | $15,000              | Anual      | LitmusChaos Enterprise / Gremlin |
| **Observabilidad (Grafana Cloud / Datadog / New Relic)** | $50,000              | Anual      | Full Stack Observability         |
| **Penetration Testing Tools**                            | $5,000               | Anual      | Burp Suite Pro, etc.             |
| **Security Training (Team)**                             | $10,000              | Semestral  | Secure Coding, Threat Modeling   |
| **Pen Testing Tools**                                    | $5,000               | Anual      | Burp Suite Pro, etc.             |
| **Total Estimado Anual**                                 | **~$198,000**        |            | **~1.5% Revenue** (typical 1-3%) |

## 11. Roles y Responsabilidades SQA (RACI)
| Actividad                                | QA Lead | Dev Lead | Security Lead | Platform Lead | Dev Team | Product Owner |
|------------------------------------------|---------|----------|---------------|---------------|----------|---------------|
| **Definir Estándares Calidad**           | R       | C        | C             | C             | I        | A             |
| **Definir Métricas Calidad**             | R       | C        | C             | C             | I        | A             |
| **Ejecutar Tests Unitarios**             | I       | R        | I             | I             | R        | I             |
| **Ejecutar Tests Feature/E2E**           | R       | C        | I             | I             | R        | I             |
| **Ejecutar Tests Accesibilidad**         | R       | C        | I             | I             | C        | I             |
| **Ejecutar Tests Performance**           | C       | C        | I             | R             | R        | I             |
| **Ejecutar Tests Seguridad (SAST/DAST)** | C       | C        | R             | I             | C        | I             |
| **Ejecutar Chaos Engineering**           | C       | C        | I             | R             | C        | I             |
| **Ejecutar Pen Testing**                 | A       | I        | R             | C             | I        | I             |
| **Ejecutar Chaos Engineering**           | C       | C        | I             | R             | C        | I             |
| **Gestionar Incidentes (IRP)**           | I       | R        | R             | R             | R        | A             |
| **Postmortems**                          | R       | R        | R             | R             | R        | A             |
| **Definir Métricas Calidad**             | R       | C        | C             | C             | I        | A             |
| **Reportar Métricas Calidad**            | R       | C        | C             | C             | I        | I             |
| **Gestionar Incidentes (IRP)**           | I       | R        | R             | R             | R        | A             |
| **Postmortems**                          | R       | R        | R             | R             | R        | A             |
| **Gestionar Configuración (CMDB)**       | I       | C        | C             | R             | C        | A             |
| **Gestión Proveedores/Dependencias**     | C       | C        | R             | C             | C        | A             |
| **Entrenamiento Equipo**                 | R       | C        | R             | C             | R        | A             |
**R = Responsible, A = Accountable, C = Consulted, I = Informed**

## 12. Roadmap SQA (Próximos 12 Meses)
| Trimestre | Enfoque Principal                     | Hitos Clave                                                                                 | Métricas Objetivo                                                |
|-----------|---------------------------------------|---------------------------------------------------------------------------------------------|------------------------------------------------------------------|
| **Q1**    | **Fundamentos + Hardening**           | FTR Completado, Critical/High=0, PenTest Done, Chaos Eng Started, A11y Fix Sprint 1-2       | Critical=0, High≤3, A11y=0, Coverage Dom≥90%                     |
| **Q2**    | **Resiliencia + Performance**         | Chaos Eng Running, Load/Stress/Soak Done, Contract Testing (Pact), DR Drill Done            | p95 Login<300ms, p99 Token<50ms, MTTR<15min, Availability 99.99% |
| **Q3**    | **Seguridad Avanzada + Supply Chain** | Red Team Exercise, Supply Chain Hardening (SLSA L2), SBOM+Provenance, Dependency Drift=0    | 0 Critical/High, SLSA L2, SBOM 100%                              |
| **Q4**    | **Madurez SQA + Certificación**       | A11y Audit Certified (WCAG 2.1 AA), SQA Maturity 4.5/5, DR Drill Success, SQA Annual Report | SQA Maturity 4.5/5, WCAG 2.1 AA Certified                        |

## 13. Presupuesto SQA (Resumen Anual)
| Concepto                               | Costo Anual (USD) | Justificación                  |
|----------------------------------------|-------------------|--------------------------------|
| Penetration Testing (External)         | $25,000           | 2x/año                         |
| Red Team Exercise                      | $50,000           | 1x/año                         |
| Chaos Engineering Platform             | $15,000           | LitmusChaos/Gremlin            |
| Load Testing (k6 Cloud)                | $10,000           | Mensual                        |
| Pen Testing Tools                      | $5,000            | Burp Suite Pro, etc.           |
| Security Training                      | $10,000           | Semestral                      |
| Accessibility Audit                    | $8,000            | WCAG 2.1 AA Cert               |
| Chaos Engineering Platform             | $15,000           | LitmusChaos/Gremlin            |
| Observabilidad (Grafana Cloud/Datadog) | $50,000           | Full Stack                     |
| Pen Testing Tools                      | $5,000            | Burp Suite Pro                 |
| Security Training                      | $10,000           | Secure Coding, Threat Modeling |
| **TOTAL ESTIMADO**                     | **~$198,000/año** | ~1.5% Revenue                  |

## 14. Próximos Pasos Inmediatos (Próximas 2 Semanas)
| Acción                                             | Responsable     | Fecha Límite    | Estado    |
|----------------------------------------------------|-----------------|-----------------|-----------|
| Ejecutar Sprint 1 Plan Acción FTR                  | Equipo Dev + QA | Sprint 1 Día 5  | Pendiente |
| Configurar axe-core en CI/CD                       | DevOps          | Sprint 1 Día 2  | Pendiente |
| k6 Load Tests (Login 500 req/s)                    | DevOps + QA     | Sprint 1 Día 3  | Pendiente |
| Chaos Engineering (LitmusChaos)                    | DevOps          | Sprint 1 Día 5  | Pendiente |
| Penetration Testing (Auth Focus)                   | Security Vendor | Sprint 1 Fin    | Pendiente |
| Configurar Pact Broker + Consumer-Driven Contracts | QA + Dev        | Sprint 2 Inicio | Pendiente |
| Ejecutar Chaos Experiments (LitmusChaos)           | DevOps + SRE    | Sprint 2        | Pendiente |
