# Guion de la demo funcional (10 minutos)

Flujo: **registrar quién hizo qué y consultarlo con autorización en el hospital actual**. Datos ficticios.
Salida real de esta demo: [`evidencias/01-demo.txt`](evidencias/01-demo.txt).

## Preparación

```bash
php micro-his/gobernanza-auditoria/tests/run.php          # «TODAS LAS SUITES APROBADAS»
php -S 127.0.0.1:8091 -t micro-his/gobernanza-auditoria/public micro-his/gobernanza-auditoria/public/index.php
```

Abrir `http://127.0.0.1:8091/prototipo/`. Plan B: los pasos por API y las capturas de la Semana 11
(`../evidencias/s11-*.jpg`).

## Recorrido

| Min | Paso | Qué mostrar | Resultado esperado | Requisito |
|---|---|---|---|---|
| 0–1 | Contexto | Cada módulo registra eventos con `RecordAuditEvent`; la consulta es solo para auditoría | — | Alcance S1 |
| 1–3 | Administrador del hospital 1 | Lista paginada; solo eventos de su hospital | 200, 52 de 64 eventos | Consulta |
| 3–4 | Filtros `modulo=rbac&resultado=denied` | Los intentos denegados se distinguen de lo ocurrido | 200, `occurred:false` | Trazabilidad |
| 4–5 | Médico sin `auditoria.ver` | RBAC se evalúa antes que los filtros | 403 `RBAC_DENIED` | Autorización |
| 5–6 | Otro hospital por cabecera o por query | El tenant lo decide el servidor | 403 `TENANT_MISMATCH` | Aislamiento |
| 6–7 | Almacén o RBAC caídos | Falla cerrada, sin detalle técnico | 503 con `trace_id` | Resiliencia |
| 7–8 | Datos personales en el contexto | Correo, DPI y teléfono no se guardan; quedan en `redacted_keys` | `tests/seguridad.php` 10/10 | H-GOB-17-01 |
| 8–9 | Integración real | `GET /api/v1/audit-events` con JWT, tenant y `auditoria.ver`; hash compatible con Tenants | `GovernanceHttpTest` 5/5; `hash_compat` 3/3 | Integración |
| 9–10 | Cierre | Hallazgos corregidos y pendientes (PostgreSQL, permiso para `admin_hospital`) | — | Semanas 16–17 |

## Por API (plan B)

```bash
B=http://127.0.0.1:8091/api/v1/audit-events
curl -s "$B?por_pagina=5" -H 'X-Demo-Session: admin'                       # 200
curl -s "$B?modulo=rbac&resultado=denied" -H 'X-Demo-Session: admin'        # 200
curl -s "$B" -H 'X-Demo-Session: medico'                                    # 403
curl -s "$B" -H 'X-Demo-Session: admin' -H 'X-Tenant-ID: otro'              # 403
```

## Preguntas probables y respuesta corta

| Pregunta | Respuesta |
|---|---|
| ¿Cómo se sabe que nadie alteró la bitácora? | `audit_log` es append-only y cada evento encadena el hash del anterior, con el mismo algoritmo que Tenants (`hash_compat`). |
| ¿Por qué el médico recibe 403 aunque mande un filtro inválido? | RBAC va primero: sin permiso no se revela cómo valida la API (TC-N-11, TC-S-05). |
| ¿Qué se hace con datos sensibles que otro módulo envía? | `ContextSanitizer` los elimina al escribir y al leer y deja el nombre de la clave en `redacted_keys`. |
| ¿Quién puede consultar? | Hoy solo `superadmin`; dar `auditoria.ver` a `admin_hospital` es una decisión pendiente de RBAC. |
| ¿Qué no se probó? | El trigger append-only en PostgreSQL y usuarios reales. |
