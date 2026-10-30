# Bitácora de despliegue — Expediente médico (on-premise)

Ensayo real del 2026-10-29 en el equipo de desarrollo, siguiendo la guía del grupo
([`docs/CI-E2E/guia-despliegue`](../../CI-E2E/guia-despliegue/README.md)) adaptada a un micro-HIS sin PostgreSQL.
Salida completa con la hora de cada paso: [`evidencias/02-ensayo-despliegue.txt`](evidencias/02-ensayo-despliegue.txt).

| Campo | Valor |
|---|---|
| Responsable | Cindy Ruano |
| Ambiente | On-premise local: Windows 11, PHP 8.2.12 con `pdo_sqlite`, puerto 8296 |
| Herramienta | `micro-his/qa-calidad-versiones/bin/desplegar-micro-his.sh` (PR de la Semana 17 del módulo QA) |
| Estructura | `releases/<sha>` inmutables exportados con `git archive`; `current` con el release activo; `shared/` con las bases SQLite y el log del servidor |
| Contenido del release | `micro-his/expediente-medico`, `micro-his/rbac`, `micro-his/gobernanza-auditoria`, `micro-his/pacientes`, `config/rbac.php` |

## Pasos ejecutados

| Hora | Paso | Resultado |
|---|---|---|
| 21:57:50 | P3: PHP ≥ 8.2 y `pdo_sqlite` | OK |
| 21:57:50–59 | Release anterior `6e841849f99a` (`develop`): exportación (149 archivos), `php -l` de cada archivo, salud `/ui/` | 200, desplegado |
| 21:58:00–09 | Release nuevo `9a55b0c9134d` (`develop` + código de esta entrega): exportación (150 archivos), `php -l`, salud | 200, `current` → `9a55b0c9134d` |
| 21:58:09 | V2: consulta de la médica tratante | 200 |
| 21:58:09 | V3: petición sin identidad | 401 `IDENTIDAD_REQUERIDA` |
| 21:58:09 | V4: apertura y repetición | 201 / 200 |
| 21:58:09 | V5: bitácora en `shared/` | `consultado`, `abierto` y `apertura_repetida` (corrección H-EXP-17-01 en el release desplegado) |
| 21:58:10–19 | Ensayo de fallo: release `2987a7fc2dfa` con un archivo PHP roto | `php -l` lo detecta; no se activa |
| 21:58:19 | Rollback automático | `current` sigue en `9a55b0c9134d` y responde 200 |
| 21:58:19 | Cierre | Servidor detenido; puerto libre |

## Decisiones y observaciones

- Los datos viven en `shared/`, fuera de cada release: el rollback no pierde la bitácora ni los expedientes.
- Los releases `9a55b0c9134d` y `2987a7fc2dfa` son commits **locales** de ensayo (nunca publicados). El primero tiene
  exactamente el código de `micro-his/expediente-medico` de este PR; el segundo agrega un archivo roto a propósito.
- La interfaz de este ensayo no incluye la corrección DEF-EXP-10, que llega con el PR de la Semana 16.
- No se ejecutó: despliegue de Laravel con PostgreSQL (migraciones), HTTPS ni gestor de procesos. Son los puntos
  pendientes §5 de la guía del grupo.

## Checklist de salida a producción (para el ambiente final)

- [ ] El prototipo con `X-User-ID` **no** se expone fuera de 127.0.0.1; en producción solo se usa la ruta Laravel con JWT.
- [ ] `APP_DEBUG=false`, HTTPS y `TENANTS_REFRESH_COOKIE_SECURE=true` (guía §1, P4).
- [ ] Quality gate aprobado para el commit a desplegar (P1) y respaldo de bases (P5).
- [ ] Permiso de apertura catalogado por RBAC antes de publicar `POST`.
