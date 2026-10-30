# Bitácora de despliegue — ensayo de la guía final (on-premise)

Ensayo real del 2026-10-29 de la [guía de despliegue](../guia-despliegue/README.md) en el equipo de desarrollo, sin
PostgreSQL. Salida completa: [`evidencias/02-ensayo-guia-laravel.txt`](evidencias/02-ensayo-guia-laravel.txt).

| Campo | Valor |
|---|---|
| Responsable | Cindy Ruano |
| Commit | `origin/develop` `6e841849f99a` |
| Ambiente | Windows 11, PHP 8.2.12 con `pdo_pgsql`, Node 22.13.1; sin `psql` ni servidor PostgreSQL |
| Release | `git archive` del commit, sin `docs/`, en una carpeta aparte; `.env` propio de producción |
| Servidor | `php -S` con el router de Laravel desde `public/` (no hay servidor web ni gestor de procesos) |

## Resultado por paso de la guía

| Paso | Hora | Resultado | Estado |
|---|---|---|---|
| P1 Quality gate | — | No hay ejecución en GitHub (facturación); el gate local de la Semana 16 quedó RECHAZADO por falta de PostgreSQL | No cumplido |
| P2 Commit exacto | 22:11:39 | `6e841849f99a` | OK |
| P3 Plataforma | 22:11:39 | PHP 8.2.12 con `pdo_pgsql`; Node 22; **sin PostgreSQL 16** | Parcial |
| P4 Variables | 22:11:43 | 0 variables de `.env.example` faltantes; `APP_ENV=production`, `APP_DEBUG=false`, cookie segura; `APP_KEY` y `JWT_SECRET` nuevos | OK |
| P5/P6 Respaldo y migraciones | 22:11:43 | `migrate:status` → `SQLSTATE[08006] … no password supplied` | No ejecutable aquí |
| §2 `down` | 22:11:44 | Mantenimiento ENABLED | OK |
| §2 `config:cache` y `route:cache` | 22:11:46 | OK, 51 rutas `api` | OK |
| §2 `up` | 22:11:46 | OK | OK |
| V1 Salud `/up` | 22:11:47 | 200 | OK |
| V2 Tenant obligatorio | 22:11:48 | 400 `TENANT_REQUIRED` | OK |
| V3 Sin token | 22:11:48 | **500** «Server Error» (esperado 401): sin CENTRAL, el middleware `tenant` falla antes de revisar el token | Hallazgo H-QA-17-02 |
| V4 Contrato de rutas | 22:11:49 | exit 0, 51 OK, 0 FALLO | OK |
| V6 Logs | 22:11:49 | 2 `production.ERROR`, ambos por la conexión a PostgreSQL | Esperado aquí |
| §4 Rollback de configuración | 22:11:50 | `config:clear` y `route:clear` OK | OK |
| Cierre | 22:11:53 | Servidor detenido; release de ensayo eliminado | OK |

## Despliegue de micro-HIS con rollback

`micro-his/qa-calidad-versiones/bin/desplegar-micro-his.sh` (nuevo) aplica §2–§4 a un micro-HIS: release inmutable
por commit con `git archive`, datos en `shared/`, `php -l` de cada archivo, salud y rollback automático. Se usó en
los ensayos del expediente (21:57–21:58) y de Gobernanza (22:03), con un release roto a propósito en ambos:
el rollback dejó activo el release anterior y respondiendo 200.

## Cambios propuestos a la guía

1. V3: aceptar 401 **solo** con CENTRAL disponible; con CENTRAL caída hoy se obtiene 500 (ver H-QA-17-02).
2. Antes de conservar evidencias: `php micro-his/qa-calidad-versiones/bin/escanear-secretos.php qa-evidence`.
3. Fijar `shivammathur/setup-php` por SHA cuando se pueda ejecutar el workflow.
