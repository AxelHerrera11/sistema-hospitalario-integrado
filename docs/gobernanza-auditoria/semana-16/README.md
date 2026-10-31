# Gobernanza y Auditoría — Semana 16: integración, validación y primera entrega funcional

## Objetivo

Integrar el módulo en `develop`, validarlo en un ambiente on-premise con datos ficticios, corregir los errores de
integración encontrados y dejar una primera entrega funcional con evidencia.

## Qué quedó integrado en `develop`

Los 25 PR del módulo (#564–#654) están fusionados. Los PR #640, #649, #650 y #651 se cerraron sin fusionar porque su
contenido ya había llegado con los demás. Con ellos y con el trabajo de RBAC y Tenants, en `develop`:

| Pieza | Estado | Evidencia |
|---|---|---|
| `micro-his/gobernanza-auditoria` (registro, consulta, cadena de hashes) | Integrado; 9 suites, 112 pruebas en verde | [02](evidencias/02-integracion-develop.txt) |
| `GobernanzaAuditoriaServiceProvider` en `bootstrap/providers.php` | Registrado | [02](evidencias/02-integracion-develop.txt) |
| `GET /api/v1/audit-events` con `api`, `tenant`, `JwtAuth` y `RequirePermission:auditoria.ver` | Publicado | [02](evidencias/02-integracion-develop.txt) |
| Permiso `auditoria.ver` en `config/rbac.php` | Catalogado; hoy solo lo tiene `superadmin` | [02](evidencias/02-integracion-develop.txt) |
| Denegaciones RBAC persistidas (`DenialRecorder` → `AuditDenials`) | Enlazado por `TenantsServiceProvider` | [02](evidencias/02-integracion-develop.txt) |
| Consulta HTTP con JWT, tenant y RBAC reales | 5 pruebas Feature de RBAC (`GovernanceHttpTest`) en verde | [02](evidencias/02-integracion-develop.txt) |

## Ambiente on-premise usado

No hay servidor de staging del grupo y GitHub Actions no ejecuta jobs (facturación de la organización bloqueada desde
el 24-oct-2026). La validación se hizo **on-premise, en el equipo de desarrollo**: Windows 11, PHP 8.2.12, copia limpia
de `origin/develop` (`6e841849`) con los cambios de esta entrega, servidor `php -S 127.0.0.1:8091` del módulo y
64 eventos ficticios. La prueba Feature usa SQLite en memoria con el esquema de prueba del repositorio.

## Validación ejecutada (2026-10-29)

| # | Qué se validó | Resultado | Evidencia |
|---|---|---|---|
| V-01 | Administrador del hospital 1 consulta la bitácora | 200, 25 de 52 eventos (solo su hospital) | [01](evidencias/01-smoke-on-premise.txt) |
| V-02 | Filtros `modulo=rbac&resultado=denied&por_pagina=5` | 200, solo `rbac/denied`, 6 en total | [01](evidencias/01-smoke-on-premise.txt) |
| V-03 | Médico sin `auditoria.ver` / sin sesión | 403 `RBAC_DENIED` / 401 `AUTH_REQUIRED` | [01](evidencias/01-smoke-on-premise.txt) |
| V-04 | Otro hospital por cabecera o por query | 403 `TENANT_MISMATCH` en ambos | [01](evidencias/01-smoke-on-premise.txt) |
| V-05 | Página fuera de rango y parámetro desconocido | 422 `AUDIT_FILTER_INVALID` con cada campo | [01](evidencias/01-smoke-on-premise.txt) |
| V-06 | Almacén o RBAC caídos | 503 con código y `trace_id`, sin detalle técnico | [01](evidencias/01-smoke-on-premise.txt) |
| V-07 | Suites del micro-HIS con RBAC y Tenants de `develop` | 112 OK, 0 omitidas | [02](evidencias/02-integracion-develop.txt) |
| V-08 | Endpoint Laravel con JWT y RBAC reales | 5 pruebas, 33 aserciones OK | [02](evidencias/02-integracion-develop.txt) |

## Errores corregidos en esta entrega

| Id | Problema | Causa | Corrección | Verificación |
|---|---|---|---|---|
| E-GOB-16-01 | En `develop`, la suite `hash_compat` se **omitía** siempre: la compatibilidad de hash con Tenants no se comprobaba | Buscaba el repositorio de Tenants en una carpeta exportada de la rama `semana-16-tenants-auditoria`, que ya está fusionada | Por defecto usa `app/Modules/Tenants` del repositorio; `TENANTS_AUDIT_DIR` queda para comparar otra versión | TC-R-01…03 en verde contra el código de Tenants ([02](evidencias/02-integracion-develop.txt)) |
| E-GOB-16-02 | El README del módulo decía que el provider, `auditoria.ver`, el enlace de denegaciones y H-GOB-07 (`App\Models\Tenant`) estaban pendientes | Documento escrito antes de la integración | Tabla de dependencias actualizada con el estado real | Comandos `grep` en [02](evidencias/02-integracion-develop.txt) |

Durante el smoke, un primer intento con `module`, `outcome` y `per_page` devolvió 422. No es un defecto: el contrato
usa nombres en español (`modulo`, `resultado`, `por_pagina`) y rechaza los demás a propósito. Se repitió con los
nombres correctos. Queda como observación que la respuesta usa `meta.page` y `meta.per_page` en inglés.

## Archivos cambiados

`micro-his/gobernanza-auditoria/tests/hash_compat.php`, `docs/gobernanza-auditoria/README.md` y esta carpeta.

## Pendiente

- Validar con PostgreSQL (triggers append-only y bloqueo de la cadena) en un servidor del grupo.
- Decidir con RBAC si `admin_hospital` debe recibir `auditoria.ver` (hoy solo `superadmin`).
