# Bitácora de despliegue — Gobernanza y auditoría (on-premise)

Ensayo real del 2026-10-29 en el equipo de desarrollo, con la guía del grupo
([`docs/CI-E2E/guia-despliegue`](../../CI-E2E/guia-despliegue/README.md)) adaptada a un micro-HIS sin PostgreSQL.
Salida completa: [`evidencias/02-ensayo-despliegue.txt`](evidencias/02-ensayo-despliegue.txt).

| Campo | Valor |
|---|---|
| Responsable | Cindy Ruano |
| Ambiente | On-premise local: Windows 11, PHP 8.2.12, puerto 8291 |
| Herramienta | `micro-his/qa-calidad-versiones/bin/desplegar-micro-his.sh` (PR de la Semana 17 del módulo QA) |
| Contenido del release | `micro-his/gobernanza-auditoria` (no depende de Composer ni de otros micro-HIS en ejecución) |

## Pasos ejecutados

| Hora | Paso | Resultado |
|---|---|---|
| 22:03:05–08 | Release anterior `6e841849f99a` (`develop`): exportación (50 archivos), `php -l`, salud `/prototipo/` | 200, desplegado |
| 22:03:09–12 | Release nuevo `5286dc4ed1fe` (`develop` + código de esta entrega) | 200, `current` → `5286dc4ed1fe` |
| 22:03:12 | V2: administrador del hospital 1 | 200 |
| 22:03:12 | V3: médico sin `auditoria.ver` | 403 `RBAC_DENIED` |
| 22:03:12 | V4: cabecera de otro hospital | 403 `TENANT_MISMATCH` |
| 22:03:12 | V5: cabecera de caché | `no-store, private` |
| 22:03:13 | V6: `tests/seguridad.php` ejecutado **desde el release desplegado** | 10 pruebas, 0 fallos (la corrección H-GOB-17-01 está en el release) |
| 22:03:13–16 | Ensayo de fallo: release `e0985a3ea35f` con un archivo PHP roto | `php -l` lo detecta; no se activa |
| 22:03:17 | Rollback automático | `current` sigue en `5286dc4ed1fe` y responde 200 |
| 22:03:17 | Cierre | Servidor detenido; puerto libre |

## Observaciones

- `5286dc4ed1fe` y `e0985a3ea35f` son commits **locales** de ensayo (nunca publicados). El primero contiene
  exactamente el código de `micro-his/gobernanza-auditoria` de este PR.
- El release no incluye la corrección de `hash_compat` (PR de la Semana 16); no afecta al servidor.
- El servidor de demostración genera eventos ficticios en memoria; la persistencia real es `PdoAuditLog` sobre
  `audit_log`, que en producción vive en PostgreSQL. No se ensayó: migraciones, trigger append-only de PostgreSQL,
  HTTPS ni gestor de procesos.

## Checklist de salida a producción

- [ ] El servidor de demostración (`X-Demo-Session`) **no** se publica; en producción solo existe la ruta Laravel.
- [ ] Trigger `audit_log_reject_update_or_delete` verificado en PostgreSQL por Javier.
- [ ] Definir con RBAC si `admin_hospital` recibe `auditoria.ver`.
- [ ] Quality gate aprobado para el commit a desplegar y respaldo de la base CENTRAL.
