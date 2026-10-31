# Bitácora de despliegue — micro-HIS de Tenants (on-premise)

Ensayo real del 2026-10-29 en el equipo de desarrollo. Salida completa con la hora de cada paso:
[`evidencias/02-ensayo-despliegue.txt`](evidencias/02-ensayo-despliegue.txt). El despliegue de la autenticación
Laravel se ensayó con la guía del grupo en el módulo QA (Semana 17).

| Campo | Valor |
|---|---|
| Responsable | Cindy Ruano |
| Ambiente | Windows 11, PHP 8.2.12 (XAMPP), `php -S 127.0.0.1:8281 app/Modules/Tenants/micro-his.php` |
| Releases | `887f24b54390` = `develop` + corrección H-TEN-16-01 (commit local de ensayo, mismo código que el PR); `6e841849f99a` = `develop` |
| Estado | `MICRO_HIS_STATE` en `shared/`, fuera de los releases |

## Pasos ejecutados

| Hora | Paso | Resultado |
|---|---|---|
| 22:16:43 | P3: zona horaria de `php.ini` = `Europe/Berlin` | Se fuerza `-d date.timezone=UTC` (H-TEN-16-02) |
| 22:16:44 | Exportación del release nuevo | OK |
| 22:16:45 | P4: arrancar con un secreto JWT de 16 bytes | Rechazado: «Defina MICRO_HIS_JWT_SECRET con al menos 32 bytes» (exit 1) |
| 22:16:45 | P4: secreto de 32 bytes con `random_bytes` | Generado; no se imprime ni se guarda |
| 22:16:46 | Primera cuenta `admin_hospital` en un hospital ficticio | OK |
| 22:16:47 | V1: cabeceras del login | 202 con `Cache-Control: no-store` y `X-Content-Type-Options: nosniff` |
| 22:16:48 | V2: `/auth/me` sin token | 401 |
| 22:16:48 | V3: login con MFA y `/auth/me` con el token | 200 |
| 22:16:48–49 | Rotación del secreto y reinicio; V4: el token anterior | 401 «Token inválido o expirado» |
| 22:16:49 | V5: el estado sobrevive al reinicio | Login del admin → 202 (desafío MFA) |
| 22:16:51–52 | Rollback al release `develop` con el mismo `shared/`; V6 | Login → 202: estado conservado |
| 22:16:52 | V7: cabeceras en el release anterior | Sin `no-store`: confirma por qué se despliega el nuevo |
| 22:16:57 | Cierre | Servidores detenidos; carpeta de ensayo borrada |

## Reglas de despliegue que deja este ensayo

1. El secreto JWT se genera en el servidor (≥ 32 bytes) y se rota invalidando los tokens emitidos.
2. `date.timezone=UTC` en el `php.ini` del servidor.
3. El archivo de estado vive fuera del release: un rollback no borra usuarios ni sesiones.
4. Producción usa la autenticación Laravel (#501–#515); el micro-HIS es la versión vanilla de referencia y pruebas.
