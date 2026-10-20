# Semana 14 — Checklist SQA y Confiabilidad
## Instrucciones
- Marcar ✅ si cumple, ❌ si no cumple, ⚠️ si parcial/observación
- Referenciar evidencia (documento, test, métrica, dashboard)
- Asignar severidad si no cumple: 🔴 Crítica | 🟠 Alta | 🟡 Media | 🟢 Baja | ℹ️ Informativa

## 1. Métricas de Calidad (KPIs) - 12 KPIs Definidos
| ID      | KPI                           | Objetivo               | Actual | ✅/❌/⚠️ | Evidencia                     | Acción si No Cumple          |
|---------|-------------------------------|------------------------|--------|--------|-------------------------------|------------------------------|
| **M1**  | Tasa Éxito Login              | ≥ 99.5%                |        |        | Auth Service Logs / Dashboard | Investigar causa raíz        |
| **M2**  | Tiempo Login p95              | < 300ms                |        |        | APM (New Relic/Datadog)       | Optimizar queries / Cache    |
| **M3**  | Tasa Éxito MFA (1er intento)  | ≥ 95%                  |        |        | Auth Service Metrics          | Revisar UX MFA / Timeout     |
| **M4**  | Latencia Validación Token p99 | < 50ms                 |        |        | API Gateway Metrics           | Optimizar JWKS Cache / Redis |
| **M5**  | Tasa Revocación Exitosa       | 100%                   |        |        | Admin Service Logs            | Investigar fallos            |
| **M6**  | Detección Reuse Refresh       | < 500ms                |        |        | Auth Service Metrics          | Optimizar Redis / Argon2id   |
| **M7**  | Disponibilidad Servicio Auth  | 99.99%                 |        |        | Uptime Monitor                | Investigar Downtime          |
| **M8**  | Vulnerabilidades Crit/High    | 0                      |        |        | Trivy/CodeQL/Snyk + PenTest   | Remediación Inmediata        |
| **M9**  | Cobertura Domain/UC           | Domain ≥ 95%, UC ≥ 90% |        |        | CI/CD Coverage                | Aumentar Tests               |
| **M10** | Violaciones Accesibilidad     | 0 (axe-core AA)        |        |        | CI/CD axe-core                | Fix Inmediato                |
| **M11** | MTTR Incidentes Auth          | < 15 min               |        |        | Incident Tracker              | Postmortem + Mejora          |
| **M12** | MTBF Auth                     | > 720h (30 días)       |        |        | Incident Tracker              | Revisión Arquitectura        |

## 2. Matriz de Riesgos (Top 10) - Estado Controles
| ID      | Riesgo                            | Probabilidad | Impacto | Nivel      | Controles Preventivos                                      | Controles Detectivos                | Estado Controles |
|---------|-----------------------------------|--------------|---------|------------|------------------------------------------------------------|-------------------------------------|------------------|
| **R01** | Compromiso Refresh Token          | Media        | Crítico | 🔴 Crítico | Refresh Rotation + Reuse Detection + Blocklist + Short TTL | SIEM Alert + Jira Auto + PagerDuty  |                  |
| **R02** | Credential Stuffing / Brute Force | Alta         | Alto    | 🔴 Crítico | Rate Limiting, Lockout Progresivo, MFA Obligatorio         | SIEM Alert + Lockout Alerts         |                  |
| **R03** | Compromiso Credenciales Admin     | Media        | Crítico | 🔴 Crítico | MFA Obligatorio, Force-Revoke, Device Trust                | SIEM Geo/Anomaly Alerts             |                  |
| **R04** | Fuga Datos PII/PHI                | Baja         | Crítico | 🟠 Alto    | Cifrado AES-256, RLS, Tokenization, DLP                    | DLP Alerts + Audit Monitoring       |                  |
| **R05** | DDoS / DoS en Auth                | Media        | Alto    | 🟠 Alto    | Rate Limiting, WAF, Auto-scaling, Circuit Breaker          | Latency/Error Alerts                |                  |
| **R06** | Falla Redis Cluster               | Baja         | Crítico | 🟠 Alto    | Redis HA (3M+3R), AOF, Multi-AZ, Fallback Cache            | Health Checks + Replication Lag     |                  |
| **R06** | Falla PostgreSQL                  | Baja         | Crítico | 🟠 Alto    | Patroni HA, Sync Replication, Failover < 30s               | PG Health + Replication Lag         |                  |
| **R07** | Fuga Refresh Token (Log/URL)      | Media        | Alto    | 🟠 Alto    | HttpOnly Secure Cookies, No URL/Logs, Short TTL            | Log Scanning + Token Leak Detection |                  |
| **R08** | Bypass MFA (Social Eng/SIM Swap)  | Baja         | Alto    | 🟡 Medio   | WebAuthn/FIDO2, TOTP + Device Trust, Recovery Codes        | MFA Bypass Alerts + Device Alerts   |                  |
| **R10** | Insider Threat (Admin Malicioso)  | Baja         | Crítico | 🟠 Alto    | Force-Revoke + Dual Approval + SIEM UEBA                   | SIEM UEBA + Priv Esc Alerts         |                  |

## 3. Controles Preventivos - Estado Implementación
| Capa          | Control                                 | Implementado | Evidencia | Pendiente |
|---------------|-----------------------------------------|--------------|-----------|-----------|
| **Red**       | WAF + DDoS Protection                   |              |           |           |
| **Red**       | Network Segmentation                    |              |           |           |
| **App**       | Secure Coding (SAST/DAST/SCA)           |              |           |           |
| **App**       | Input Validation (Zod)                  |              |           |           |
| **App**       | Output Encoding (React/DOMPurify)       |              |           |           |
| **App**       | CSP / Security Headers                  |              |           |           |
| **Datos**     | Cifrado Reposo (AES-256)                |              |           |           |
| **Datos**     | Cifrado Tránsito (TLS 1.3)              |              |           |           |
| **Datos**     | Tokenization (Vault Transit)            |              |           |           |
| **Identidad** | MFA Adaptativo (TOTP/WebAuthn)          |              |           |           |
| **Identidad** | JWT RS256 + JWKS Rotation               |              |           |           |
| **Identidad** | Refresh Rotation + Reuse Detection      |              |           |           |
| **Identidad** | Blocklist JWT (Redis)                   |              |           |           |
| **Datos**     | RLS PostgreSQL                          |              |           |           |
| **Datos**     | Auditoría Inmutable (Hash Chain)        |              |           |           |
| **Infra**     | Secrets Management (Vault/KMS)          |              |           |           |
| **Infra**     | Image Security (Trivy, SBOM, Cosign)    |              |           |           |
| **Infra**     | K8s Hardening (Non-root, NetworkPolicy) |              |           |           |
| **Infra**     | Secrets Rotation (Vault/AWS)            |              |           |           |
| **Red**       | mTLS Service Mesh (Istio)               |              |           |           |
| **Obs**       | Distributed Tracing (OTel/Jaeger)       |              |           |           |
| **Obs**       | Metrics + Alerting (Prom/Grafana)       |              |           |           |
| **Obs**       | Logs Centralizados (Loki/ELK)           |              |           |           |
| **Obs**       | Audit Trail Inmutable                   |              |           |           |

## 4. Controles Detectivos - Alertas Configuradas
| Métrica/Evento                     | Herramienta              | Regla                 | Severidad  | Canal                     | Estado |
|------------------------------------|--------------------------|-----------------------|------------|---------------------------|--------|
| Failed Login Spike > 50/min/tenant | SIEM                     | Rate > threshold 5min | 🔴 Crítica | PagerDuty + Slack         |        |
| MFA Failure Spike > 20%/5min       | SIEM/Prometheus          | Rate > 20%/5min       | 🟠 Alta    | Slack #security           |        |
| Refresh Reuse Detected             | Auth Service + SIEM      | Event = REFRESH_REUSE | 🔴 Crítica | PagerDuty + Slack + Email |        |
| Token Validation p99 > 100ms       | Grafana/Datadog          | p99 > 100ms 5min      | 🟠 Alta    | Slack #platform           |        |
| Login p95 > 500ms                  | Grafana/Datadog          | p95 > 500ms 5min      | 🟠 Alta    | Slack #platform           |        |
| Redis Memory > 85%                 | Prometheus/Grafana       | Used > 85%            | 🟠 Alta    | Slack #infra              |        |
| PG Replication Lag > 1s            | Patroni/Prometheus       | Lag > 1s 1min         | 🟠 Alta    | PagerDuty + Slack         |        |
| PG Replication Lag > 10s           | Patroni/Prometheus       | Lag > 10s             | 🔴 Crítica | PagerDuty                 |        |
| Circuit Breaker Open               | Resilience4j             | State = OPEN          | 🟠 Alta    | Slack #platform           |        |
| Queue Depth > 1000                 | Redis/Prometheus         | Size > 1000 5min      | 🟡 Media   | Slack #platform           |        |
| Failed Deploy Rate > 10%           | GitHub Actions           | Rate > 10% last 10    | 🟠 Alta    | Slack #devops             |        |
| SSL Cert Expiry < 30d              | Cert Manager             | Expiry < 30d          | 🟡 Media   | Slack #infra              |        |
| SSL Cert Expiry < 7d               | Cert Manager             | Expiry < 7d           | 🔴 Crítica | PagerDuty                 |        |
| Disk Usage > 85%                   | Prometheus/Node Exporter | Disk > 85%            | 🟠 Alta    | Slack #infra              |        |
| CPU Saturation > 90% (5min)        | Prometheus/cAdvisor      | CPU > 90% 5min        | 🟠 Alta    | Slack #infra              |        |

## 6. Plan de Pruebas de Confiabilidad - Estado
| Tipo Prueba              | Herramienta            | Frecuencia            | Última Ejecución | Resultado | Próxima |
|--------------------------|------------------------|-----------------------|------------------|-----------|---------|
| Load Testing             | k6                     | Por Release + Semanal |                  |           |         |
| Stress Testing           | k6                     | Mensual               |                  |           |         |
| Spike Testing            | k6                     | Mensual               |                  |           |         |
| Soak Testing             | k6                     | Mensual (4h)          |                  |           |         |
| Chaos Engineering        | LitmusChaos            | Quincenal             |                  |           |         |
| Failover Testing         | Patroni / K8s          | Mensual               |                  |           |         |
| Chaos: Redis Failure     | LitmusChaos            | Quincenal             |                  |           |         |
| Chaos: DB Failover       | LitmusChaos/Patroni    | Quincenal             |                  |           |         |
| Chaos: Network Partition | LitmusChaos            | Mensual               |                  |           |         |
| Penetration Testing      | External/Internal      | Trimestral            |                  |           |         |
| Dependency Confusion     | Trivy/Snyk/Syft/Cosign | Por PR + Semanal      |                  |           |         |
| Pen Testing (Auth Focus) | Internal Red Team      | Trimestral            |                  |           |         |
| Red Team Exercise        | External Vendor        | Anual                 |                  |           |         |
| Disaster Recovery Drill  | Manual + Automated     | Semestral             |                  |           |         |
| Backup Restore Test      | Automated + Manual     | Mensual               |                  |           |         |

## 6. Gestión de Incidentes - Estado Proceso
| Fase                              | Implementado | Documentado | Probado | Último Ejercicio |
|-----------------------------------|--------------|-------------|---------|------------------|
| Detección (Alertas)               |              |             |         |                  |
| Triaje (Clasificación P0-P3)      |              |             |         |                  |
| Contención (Runbooks)             |              |             |         |                  |
| Erradicación (Root Cause Removal) |              |             |         |                  |
| Recuperación (Restore + Verify)   |              |             |         |                  |
| Postmortem (Blameless)            |              |             |         |                  |
| Seguimiento (Action Items)        |              |             |         |                  |

### Runbooks Disponibles
| Runbook             | Existe | Última Actualización | Probado Recientemente |
|---------------------|--------|----------------------|-----------------------|
| Auth Service Down   |        |                      |                       |
| Redis Failover      |        |                      |                       |
| PostgreSQL Failover |        |                      |                       |
| Refresh Token Reuse |        |                      |                       |
| Credential Stuffing |        |                      |                       |
| DDoS Mitigation     |        |                      |                       |
| Credential Leak | | |
| Force-Revoke Mass | | |

## 7. Gestión de Cambios y Configuración
| Elemento                      | Herramienta                      | Responsable         | Último Cambio | Auditado |
|-------------------------------|----------------------------------|---------------------|---------------|----------|
| Infra (K8s, DB, Redis, DNS)   | Terraform + ArgoCD               | Platform            |               |          |
| App (Docker, Config, Secrets) | ArgoCD + Helm + SealedSecrets    | Platform            |               |          |
| DB Schema (Migraciones)       | Flyway / Liquibase + GitOps      | DBA + Platform      |               |          |
| Secrets / Certificados        | Vault / Cert-Manager             | Platform + Security |               |          |
| Políticas Seguridad           | GitOps (ArgoCD) + OPA Gatekeeper | Security + Platform |               |          |
| Feature Flags                 | LaunchDarkly / Unleash           | Product + Eng       |               |          |

## 8. Supply Chain Security
| Categoría                             | Herramienta              | Frecuencia          | Último Escaneo | Hallazgos Abiertos |
|---------------------------------------|--------------------------|---------------------|----------------|--------------------|
| Dependencias (npm audit + Snyk/Trivy) | npm audit + Snyk/Trivy   | Por PR + Semanal    |                |                    |
| Imágenes Base Docker                  | Trivy / Grype / Hadolint | Por Build + Semanal |                |                    |
| Licencias                             | FOSSA / License Checker  | Por PR              |                |                    |
| SBOM (CycloneDX)                      | Syft                     | Por Release         |                |                    |
| Firmado Imágenes (Cosign/SLSA L2)     | Cosign/Sigstore          | Por Build           |                |                    |
| Provenance (SLSA L2)                  | Cosign + SLSA            | Por Build           |                |                    |
| Vulnerabilidades Base Images          | Trivy / Grype            | Diario (Scheduled)  |                |                    |
| Licencias Copyleft (GPL/AGPL)         | License Checker          | Por PR              |                |                    |

## 10. Próximos Pasos (Próximas 2 Semanas)
| Acción                                             | Responsable     | Fecha Límite    | Estado    |
|----------------------------------------------------|-----------------|-----------------|-----------|
| Ejecutar Sprint 1 Plan Acción FTR                  | Dev + QA        | Sprint 1 Día 5  | Pendiente |
| Configurar axe-core en CI/CD                       | DevOps          | Sprint 1 Día 2  | Pendiente |
| k6 Load Tests (Login 500 req/s)                    | DevOps + QA     | Sprint 1 Día 3  | Pendiente |
| Chaos Engineering (LitmusChaos)                    | DevOps          | Sprint 1 Día 5  | Pendiente |
| Penetration Testing (Auth Focus)                   | Security Vendor | Sprint 1 Fin    | Pendiente |
| Configurar Pact Broker + Consumer-Driven Contracts | QA + Dev        | Sprint 2 Inicio | Pendiente |
| Ejecutar Chaos Experiments                         | DevOps + SRE    | Sprint 2        | Pendiente |
