# Guion de la demo funcional (10 minutos)

Flujo: **identidad CENTRAL, sesión por hospital y administración de usuarios con autenticación endurecida**.
Usuarios ficticios `@demo.test`. Salida real de esta demo: [`evidencias/01-demo.txt`](evidencias/01-demo.txt).

## Preparación

```bash
export MICRO_HIS_JWT_SECRET="$(php -r 'echo bin2hex(random_bytes(32));')"
php -d date.timezone=UTC app/Modules/Tenants/micro-his.php seed "Hospital Demo" admin@demo.test '<clave>'
php -d date.timezone=UTC -S 127.0.0.1:8081 app/Modules/Tenants/micro-his.php
php docs/tenants/semana-16/smoke-http.php . http://127.0.0.1:8081 <tenant_id> <totp_secret>
```

Plan B: la salida guardada en [`evidencias/01-demo.txt`](evidencias/01-demo.txt) y las pruebas PHPUnit.

## Recorrido

| Min | Paso | Qué mostrar | Resultado esperado | Requisito |
|---|---|---|---|---|
| 0–1 | Contexto | Dos piezas: micro-HIS vanilla (Cindy) y autenticación Laravel integrada (#501–#515, Marielos) | — | Alcance |
| 1–3 | Login de `admin_hospital` | Primero un desafío MFA, sin tokens; luego TOTP; el desafío no se puede repetir | 202 → 200 → 401 | MFA |
| 3–4 | Alta de un médico | Contraseña débil rechazada; válida aceptada | 422 → 201 | Política de contraseñas |
| 4–5 | Sesión del médico | `me` con su hospital y rol; no puede administrar | 200, 403 | RBAC |
| 5–6 | Refresh | Rotación; reutilizar el anterior revoca la familia | 200 → 401 → 401 | Sesiones |
| 6–7 | Fuerza bruta | 5 fallos y luego la clave correcta | 401 ×5 → 423 | Bloqueo |
| 7–8 | Cabeceras | `Cache-Control: no-store` y `nosniff` en respuestas con tokens | Presentes | H-TEN-16-01 |
| 8–9 | Laravel | Rutas `auth/*` y `admin/*`; 107 pruebas de Tenants | OK | Integración |
| 9–10 | Cierre | Despliegue: secreto ≥ 32 bytes, rotación, UTC, rollback con estado; pendientes con Marielos | — | Semana 17 |

## Preguntas probables y respuesta corta

| Pregunta | Respuesta |
|---|---|
| ¿Por qué hay dos implementaciones? | El micro-HIS es la versión vanilla por capas que exige el curso; producción usa la autenticación Laravel de #501–#515. No se duplicó: se valida una contra la otra. |
| ¿Qué pasa si roban un refresh token? | Al reutilizarlo se revoca toda la familia y también el token nuevo. |
| ¿Cómo se rota el secreto JWT? | Se reinicia con uno nuevo; los tokens anteriores responden 401 (ensayo de la Semana 17). |
| ¿Se puede escalar a `superadmin`? | No: `admin_hospital` no puede otorgarlo ni cambiar sus propios roles. |
| ¿Qué falta? | Endurecimiento de Laravel (Argon2id, bloqueo en caché, política de contraseñas) acordado con Javier y Marielos, y validar contra CENTRAL en PostgreSQL. |
