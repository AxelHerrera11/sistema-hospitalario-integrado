# Semana 14 — Plan SQA y Confiabilidad
## Entregables Generados (`docs/tenants/semana-14/`)
| Archivo                          | Tipo     | Descripción                                                                                                                                                                                                                                               |
|----------------------------------|----------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `plan-sqa-confiabilidad.md`      | Markdown | **Plan SQA y Confiabilidad Completo**: 12 KPIs, Matriz Riesgos (Top 10), Controles Preventivos/Detectivos, Plan Pruebas Confiabilidad, Gestión Incidentes, CMDB, Supply Chain Security, Métricas Madurez SQA, Roadmap 6 meses, Presupuesto, RACI, Roadmap |
| `checklist-sqa-confiabilidad.md` | Markdown | **Checklist Detallado**: 12 KPIs, Matriz Riesgos Top 10, Controles Preventivos/Detectivos, Plan Pruebas Confiabilidad, Gestión Incidentes, CMDB, Supply Chain Security, Métricas Madurez SQA, Roadmap 6 meses, Presupuesto, RACI, Roadmap                 |
| `README.md`                      | Markdown | Este índice                                                                                                                                                                                                                                               |
## Resumen Semana 14 — Plan SQA y Confiabilidad
### 12 KPIs de Calidad Definidos
| ID      | KPI                           | Objetivo               | Fuente               | Frecuencia             | Umbral Alerta              |
|---------|-------------------------------|------------------------|----------------------|------------------------|----------------------------|
| **M1**  | Tasa Éxito Login              | ≥ 99.5%                | Auth Logs            | Tiempo real            | < 99% → P1                 |
| **M2**  | Tiempo Login p95              | < 300ms                | APM                  | Continuo               | > 500ms → P2               |
| **M3**  | Tasa Éxito MFA (1er intento)  | ≥ 95%                  | Auth Metrics         | Tiempo real            | < 90% → P2                 |
| **M4**  | Latencia Validación Token p99 | < 50ms                 | API Gateway          | Continuo               | > 100ms → P2               |
| **M5**  | Tasa Revocación Exitosa       | 100%                   | Admin Logs           | Por evento             | < 100% → P1                |
| **M6**  | Detección Reuse Refresh       | < 500ms                | Auth Metrics         | Por evento             | > 1s → P1                  |
| **M7**  | Disponibilidad Servicio Auth  | 99.99%                 | Uptime Monitor       | Mensual                | < 99.95% → P1              |
| **M7**  | Vulnerabilidades Crit/High    | 0                      | Trivy/CodeQL/PenTest | Por release/Trimestral | > 0 → P0                   |
| **M9**  | Cobertura Domain/UC           | Domain ≥ 95%, UC ≥ 90% | CI/CD Coverage       | Por PR/Diario          | Domain < 90% → Block merge |
| **M10** | Violaciones Accesibilidad     | 0 (axe-core AA)        | CI/CD axe-core       | Por PR                 | > 0 → Block merge          |
| **M11** | MTTR Incidentes Auth          | < 15 min               | Incident Tracker     | Por incidente          | > 30min → Postmortem       |
| **M12** | MTBF Auth                     | > 720h (30 días)       | Incident Tracker     | Mensual                | < 168h → Revisión          |

## Matriz de Riesgos (Top 10)
| ID      | Riesgo                           | Probabilidad | Impacto | Nivel      | Controles Preventivos                                      | Controles Detectivos          |
|---------|----------------------------------|--------------|---------|------------|------------------------------------------------------------|-------------------------------|
| **R01** | Compromiso Refresh Token         | Media        | Crítico | 🔴 Crítico | Refresh Rotation + Reuse Detection + Blocklist + Short TTL | SIEM + Jira + PagerDuty       |
| **R02** | Credential Stuffing              | Alta         | Alto    | 🔴 Crítico | Rate Limiting, Lockout Progresivo, MFA Obligatorio         | SIEM + Lockout Alerts         |
| **R03** | Compromiso Credenciales Admin    | Media        | Crítico | 🔴 Crítico | MFA Obligatorio, Force-Revoke, Device Trust                | SIEM Geo/Anomaly              |
| **R04** | Fuga Datos PII/PHI               | Baja         | Crítico | 🟠 Alto    | AES-256, RLS, Tokenization, DLP                            | DLP Alerts + Audit            |
| **R05** | DDoS / DoS en Auth               | Media        | Alto    | 🟠 Alto    | Rate Limiting, WAF, Auto-scaling, Circuit Breaker          | Latency/Error Alerts          |
| **R06** | Falla Redis Cluster              | Baja         | Crítico | 🟠 Alto    | Redis HA (3M+3R), AOF, Multi-AZ, Fallback Cache            | Health Checks + Lag           |
| **R06** | Falla PostgreSQL                 | Baja         | Crítico | 🟠 Alto    | Patroni HA, Sync Replication, Failover < 30s               | PG Health + Lag               |
| **R07** | Fuga Refresh Token (Log/URL)     | Media        | Alto    | 🟠 Alto    | HttpOnly Secure Cookies, No URL/Logs, Short TTL            | Log Scanning + Leak Detection |
| **R08** | Bypass MFA (Social Eng/SIM Swap) | Baja         | Alto    | 🟡 Medio   | WebAuthn/FIDO2, TOTP + Device Trust, Recovery Codes        | MFA Bypass Alerts             |
| **R10** | Insider Threat (Admin Malicioso) | Baja         | Crítico | 🟠 Alto    | Force-Revoke + Dual Approval + SIEM UEBA                   | SIEM UEBA + Priv Esc Alerts   |

## Controles Implementados (Resumen)
| Capa          | Controles Implementados                                                                                         |
|---------------|-----------------------------------------------------------------------------------------------------------------|
| **Red**       | WAF, DDoS Protection, Network Segmentation                                                                      |
| **App**       | SAST/DAST/SCA (CI/CD), Zod Validation, CSP, Helmet                                                              |
| **Datos**     | AES-256 Reposo, TLS 1.3 Tránsito, Vault Tokenization                                                            |
| **Identidad** | JWT RS256 + JWKS Rotation, Refresh Rotation + Reuse Detection, MFA TOTP/WebAuthn, Blocklist Redis, Device Trust |
| **Datos**     | RLS PostgreSQL, Auditoría Inmutable (Hash Chain + Trigger)                                                      |
| **Infra**     | Vault/KMS, Trivy/SBOM/Cosign (SLSA L2), K8s Hardening, Istio mTLS                                               |
| **Obs**       | OpenTelemetry + Jaeger, Prometheus+Grafana, Loki, Audit Trail Inmutable                                         |

## Pruebas de Confiabilidad Planificadas
| Tipo                         | Herramienta            | Frecuencia            | Criterio Éxito                        |
|------------------------------|------------------------|-----------------------|---------------------------------------|
| **Load Testing**             | k6                     | Por Release + Semanal | p95 < SLA, Error Rate < 0.1%          |
| **Stress Testing**           | k6                     | Mensual               | Graceful Degradation, No Data Loss    |
| **Spike Testing**            | k6                     | Mensual               | Auto-scaling < 2min, No Errors        |
| **Soak Testing**             | k6                     | Mensual (4h)          | No Memory Leaks, Stable Latency       |
| **Chaos Engineering**        | LitmusChaos            | Quincenal             | RTO < 15min, RPO=0, No Data Loss      |
| **Failover Testing**         | Patroni/K8s            | Mensual               | RTO < 30s, RPO=0                      |
| **Chaos: Redis Failure**     | LitmusChaos            | Quincenal             | Auto Failover < 30s, No Data Loss     |
| **Chaos: DB Failover**       | LitmusChaos/Patroni    | Quincenal             | Failover < 30s, No Data Loss          |
| **Chaos: Network Partition** | LitmusChaos            | Mensual               | Circuit Breaker Works, Degraded Mode  |
| **Penetration Testing**      | External/Internal      | Trimestral            | 0 Critical/High, < 5 Medium           |
| **Dependency Confusion**     | Trivy/Snyk/Syft/Cosign | Por PR + Semanal      | 0 Critical/High, SLSA L2              |
| **Pen Testing (Auth Focus)** | Internal Red Team      | Trimestral            | 0 Critical/High                       |
| **Red Team Exercise**        | External Vendor        | Anual                 | Detection < 1hr, Containment < 4hr    |
| **Disaster Recovery Drill**  | Manual + Automated     | Semestral             | RTO < 4h, RPO=0, Full Restore         |
| **Backup Restore Test**      | Automated + Manual     | Mensual               | Restore < 2h, Data Integrity Verified |

## Gestión de Incidentes - Proceso
| Fase             | SLA            | Herramientas                                |
|------------------|----------------|---------------------------------------------|
| **Detección**    | < 1 min        | Prometheus, Splunk, PagerDuty, Uptime Robot |
| **Triaje**       | < 5 min        | PagerDuty, Slack                            |
| **Contención**   | < 15 min (P0)  | Runbooks, Automation                        |
| **Erradicación** | < 1 hora (P0)  | Runbooks, Playbooks                         |
| **Recuperación** | < 4 horas (P0) | Runbooks, Deploy, Smoke Tests               |
| **Postmortem**   | < 48 horas     | Blameless Template, Jira, Confluence        |

## Presupuesto SQA Anual (~$198,000)
| Concepto                         | Costo             | Frecuencia    |
|----------------------------------|-------------------|---------------|
| Pen Testing (External)           | $25,000           | 2x/año        |
| Red Team Exercise                | $50,000           | 1x/año        |
| Chaos Engineering Platform       | $15,000           | Anual         |
| Load Testing (k6 Cloud)          | $10,000           | Mensual       |
| Pen Testing Tools                | $5,000            | Anual         |
| Security Training                | $10,000           | Semestral     |
| Accessibility Audit              | $8,000            | Anual         |
| Chaos Engineering Platform       | $15,000           | Anual         |
| Observabilidad (Grafana/Datadog) | $50,000           | Anual         |
| Pen Testing Tools                | $5,000            | Anual         |
| Security Training                | $10,000           | Semestral     |
| **TOTAL**                        | **~$198,000/año** | ~1.5% Revenue |

## Próximos Pasos (Semana 15)
1. **Ejecutar Plan de Pruebas Semana 15**: Unit (3), White Box (2), Black Box (4) - Matriz RF→Test
2. **Ejecutar Sprint 1 Plan Acción FTR** - Hotfixes Críticos
3. **Configurar axe-core en CI/CD** - Pipeline Quality Gate
3. **Ejecutar k6 Load Tests** - Login 500 req/s baseline
4. **Configurar Chaos Engineering** - LitmusChaos Experiments
4. **Penetration Testing (Auth Focus)** - Security Vendor
5. **Configurar Pact Broker + CDC** - Consumer-Driven Contracts

## Verificación Rápida
```bash
ls docs/tenants/semana-14/
grep -c "^| M[0-9]" docs/tenants/semana-14/plan-sqa-confiabilidad.md
grep -c "^| R0[0-9]" docs/tenants/semana-14/plan-sqa-confiabilidad.md
grep -c "^\| **Red**\|**App**\|**Datos**\|**Identidad**\|**Infra**\|**Obs**" docs/tenants/semana-14/plan-sqa-confiabilidad.md
grep -c "Failed Login Spike\|MFA Failure\|Refresh Reuse\|Token Validation\|Login p95\|Redis Memory\|PG Replication\|Circuit Breaker\|Queue Depth\|Failed Deploy\|SSL Cert\|Disk Usage\|CPU Saturation" docs/tenants/semana-14/plan-sqa-confiabilidad.md
grep -c "Load Testing\|Stress\|Spike\|Soak\|Chaos\|Failover\|Penetration\|Dependency\|Red Team\|DR Drill\|Backup" docs/tenants/semana-14/plan-sqa-confiabilidad.md
```
