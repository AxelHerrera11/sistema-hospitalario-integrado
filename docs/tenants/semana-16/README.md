# Semana 16 — Integración, validación y primera entrega funcional

**Módulo:** Gestión de usuarios, tenants y endurecimiento de autenticación (ASII-25).

**Tarea:** integrar el módulo en `develop`, validarlo en un ambiente on-premise, corregir los errores encontrados y
dejar una primera entrega funcional con evidencia.

## Qué quedó integrado en `develop`

| Bloque | PR | Contenido |
|---|---|---|
| Entregas semanales 1–15 (Cindy) | #181–#195 | Documentación por semana |
| Micro-HIS de Tenants en PHP vanilla (Cindy) | 85 PR entre #279 y #399 | `app/Modules/Tenants`: login con MFA, refresh rotativo, bloqueo, administración |
| Autenticación Laravel (Marielos, Mar-03) | #501–#515 | Modelos CENTRAL, JWT, refresh, rutas `auth/*` y `admin/*`, auditoría, cliente Vue y pruebas |

Esta entrega **no duplica** el trabajo de #501–#515: lo valida junto con el micro-HIS. El endurecimiento pendiente
(Argon2id en Laravel, bloqueo en caché, política de contraseñas) sigue fuera de `develop` hasta acordarlo con
Javier y Marielos.

## Ambiente on-premise usado

No hay servidor de staging del grupo y GitHub Actions no ejecuta jobs (facturación bloqueada desde el 24-oct-2026).

| Elemento | Valor |
|---|---|
| Equipo | Windows 11, PHP 8.2.12 (XAMPP), copia limpia de `origin/develop` `6e841849` |
| Micro-HIS | `php -S 127.0.0.1:8081 app/Modules/Tenants/micro-his.php`, secreto JWT aleatorio de 32 bytes, estado en archivo temporal |
| Datos | Un hospital ficticio sembrado con `micro-his.php seed`; usuarios `@demo.test` |
| Cliente | [`smoke-http.php`](smoke-http.php) (curl; calcula el TOTP con `TotpVerifier`) |
| Laravel | Pruebas Feature con el esquema SQLite de `TenantAuthTestCase` (CENTRAL en PostgreSQL no está disponible) |

## Validación ejecutada (2026-10-29)

| # | Qué se validó | Resultado | Evidencia |
|---|---|---|---|
| V-01 | Login de `admin_hospital` exige MFA | 202 con `challenge_id`, sin tokens | [01](evidencias/01-smoke-on-premise.txt) |
| V-02 | TOTP incorrecto / correcto / desafío repetido | 401 / 200 con JWT de 900 s / 401 | [01](evidencias/01-smoke-on-premise.txt) |
| V-03 | Alta de usuario con contraseña débil / válida | 422 / 201 | [01](evidencias/01-smoke-on-premise.txt) |
| V-04 | Médico: login, `me` y alta de usuarios | 200, rol `medico` en su hospital, 403 al administrar | [01](evidencias/01-smoke-on-premise.txt) |
| V-05 | Refresh: rotación y reutilización del anterior | 200; luego 401 y también se revoca el nuevo (familia) | [01](evidencias/01-smoke-on-premise.txt) |
| V-06 | Sin token / hospital inexistente | 401 sin revelar si el correo existe | [01](evidencias/01-smoke-on-premise.txt) |
| V-07 | 5 intentos fallidos y luego la clave correcta | 401 ×5 y después 423 (bloqueo temporal) | [01](evidencias/01-smoke-on-premise.txt) |
| V-08 | Rutas Laravel `auth/*` (4) y `admin/*` (6) | Publicadas | [02](evidencias/02-pruebas-y-rutas.txt) |
| V-09 | Pruebas de Tenants (Laravel + micro-HIS) | 106 pruebas, 529 aserciones OK | [02](evidencias/02-pruebas-y-rutas.txt) |
| V-10 | Grupos de la Semana 15 sobre `develop` | Unitarias 29, caja blanca 17, caja negra 27: OK | [02](evidencias/02-pruebas-y-rutas.txt) |

## Errores corregidos en esta entrega

| Id | Problema | Corrección | PR |
|---|---|---|---|
| E-TEN-16-01 | `semana-15` conservaba archivos v1.0 con resultados que nunca se ejecutaron (90,67 % de cobertura, axe, E2E, k6, XML escritos a mano). La Semana 15 ya los marcaba como «no son evidencia» y dejaba pendiente retirarlos | Se retiran `test-summary.md`, `matriz-rf-rnf-tests.md`, `accessibility-report.html`, `coverage/` y `test-results/` (1331 líneas) | Partes 2 a 5 de esta semana, para no pasar de 400 líneas por PR |

## Hallazgos para la Semana 17

| Id | Hallazgo | Severidad |
|---|---|---|
| H-TEN-16-01 | Las respuestas del micro-HIS (incluidas las que llevan tokens) no envían `Cache-Control: no-store` ni `X-Content-Type-Options` | Media |
| H-TEN-16-02 | Las fechas usan la zona de `php.ini` del servidor (`Europe/Berlin` en XAMPP); el desplazamiento es correcto, pero el despliegue debe fijar `date.timezone=UTC` | Baja |

## Pendiente

- Validar el login Laravel contra CENTRAL en PostgreSQL cuando exista un servidor del grupo.
- Acordar con Javier y Marielos el endurecimiento de Laravel (Argon2id, bloqueo, política de contraseñas).
