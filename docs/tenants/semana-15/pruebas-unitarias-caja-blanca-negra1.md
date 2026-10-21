# Semana 15 — Pruebas unitarias, de caja blanca y de caja negra (parte 2)

Continúa [`pruebas-unitarias-caja-blanca-negra.md`](pruebas-unitarias-caja-blanca-negra.md). Cada
escenario remite a la prueba PHPUnit que lo ejecuta. Todas recorren el router real del micro-HIS con
peticiones PSR-7 (trait `Tests\Feature\Tenants\Concerns\MicroHisHttp`) y están en el grupo `caja-negra`.

## 4. Casos de caja negra

### NB-001 · Login sin MFA (`medico`)
```gherkin
Escenario: login exitoso sin segundo factor
  Dado un usuario "medico@a.test" con rol "medico" en el Hospital A
  Cuando envía POST /auth/login con X-Tenant-ID del Hospital A y su contraseña
  Entonces la respuesta es 200 con token_type "Bearer" y expires_in 900
  Y user.roles es ["medico"]
  Y la respuesta no incluye mfa_required
```
Prueba: `LoginMfaFeatureTest::test_login_without_mfa_returns_tokens` — **Aprobado**.

### NB-002 · Login con MFA TOTP (`admin_hospital`)
```gherkin
Escenario: el desafío MFA es de un solo uso
  Dado un usuario con rol "admin_hospital" y TOTP enrolado al darlo de alta
  Cuando envía POST /auth/login con credenciales válidas
  Entonces la respuesta es 202 con challenge_id y method "TOTP", sin tokens
  Cuando envía POST /auth/mfa con el challenge_id y el código vigente
  Entonces la respuesta es 200 con tokens y LOGIN_SUCCESS registra mfa_used = true
  Cuando repite el mismo challenge_id
  Entonces la respuesta es 401
```
Prueba: `LoginMfaFeatureTest::test_login_with_totp_needs_a_valid_single_use_challenge` — **Aprobado**.
Relacionadas: tres códigos incorrectos bloquean la cuenta (`test_three_wrong_codes_lock_the_account`).

### NB-003 · Revocación forzada por `admin_hospital`
```gherkin
Escenario: el administrador cierra todas las sesiones de un usuario
  Dado "medico@a.test" con sesiones abiertas en "telefono" y "portatil"
  Cuando el admin_hospital envía DELETE /admin/users/{id}/sessions
  Entonces la respuesta es 200 con revoked_sessions = 2
  Y los dos access tokens reciben 401 en la siguiente petición
  Y SESSION_REVOKED registra reason "admin_revoke" y el requester_id del admin
  Y el admin conserva su propia sesión
```
Prueba: `RevokeSessionFeatureTest::test_admin_force_revokes_every_session_of_a_user` — **Aprobado**.

La versión anterior pedía «Deshacer (5 s)» con sesiones restauradas y un evento `SESSION_RESTORED`.
**Esa función no existe y se descartó**: revocar invalida los tokens y el acceso solo se recupera
iniciando sesión otra vez (ver Semana 11, `prototype-screens-mobile1.md`, sección 6).

### NB-004 · Reutilización de refresh token
```gherkin
Escenario: un refresh ya rotado vuelve a presentarse
  Dado un usuario con sesión iniciada que ya rotó su refresh token una vez
  Cuando alguien envía POST /auth/refresh con el refresh anterior
  Entonces la respuesta es 401 y no informa cuántas sesiones se revocaron
  Y el access token y el refresh nuevos dejan de servir
  Y la auditoría registra REFRESH_REUSE_DETECTED con severidad CRITICAL
  Y la cadena de hashes de la auditoría sigue siendo válida
```
Prueba: `RefreshTokenFeatureTest::test_reusing_a_rotated_refresh_token_revokes_the_session` — **Aprobado**.

Se retiraron de la versión anterior el ticket Jira automático, la alerta al SIEM, el campo
`blocked_access_tokens` y la exigencia de MFA en el siguiente login: ninguno está implementado.

### NB-005 · Aislamiento entre hospitales
```gherkin
Escenario: la misma persona y el mismo equipo en dos hospitales
  Dada "ana@red.test" con rol "medico" en el Hospital A y "jefatura_medica" en el B
  Cuando inicia sesión en ambos desde "equipo-01"
  Entonces cada respuesta trae solo los roles de ese hospital y el mismo id de usuario
  Cuando el admin del Hospital A revoca sus sesiones
  Entonces la sesión de A recibe 401 y la de B sigue respondiendo 200, también al rotar el refresh
```
Prueba: `MultiTenantIsolationFeatureTest::test_same_user_and_device_keep_roles_and_sessions_per_hospital` — **Aprobado**.
Relacionadas: token de A con cabecera de B → 403 y `CROSS_TENANT_ATTEMPT`; un bloqueo en A no
bloquea en B; hospital suspendido → 403.

## 5. Matriz de cobertura por componente (casos ejecutados)

| Componente | Unitarias | Caja blanca | Caja negra |
|---|---|---|---|
| `PasswordPolicy` | 10 | 8 | 1 (`RegisterUserFeatureTest`, clave débil) |
| `MfaPolicy` | 10 | 3 | 2 (`LoginMfaFeatureTest`) |
| `JwtTokenService` (`TokenFactory`) | 3 | 1 | todas (cada petición autenticada) |
| `RefreshTokenRotator` | 6 | 5 | 4 (`RefreshTokenFeatureTest`) |
| `AuthenticateUserUseCase` | — | — | 7 (`LoginMfaFeatureTest`) + 2 de aislamiento |
| `RegisterUserUseCase` | — | — | 7 (`RegisterUserFeatureTest`) |
| `RevokeSessionUseCase` | — | — | 4 (`RevokeSessionFeatureTest`) + 1 de aislamiento |
| `JwtAuthMiddleware` | — | — | 2 (`MultiTenantIsolationFeatureTest`) + rechazos 401 |

Sin porcentajes: no hay medición de cobertura de líneas (falta Xdebug o PCOV).

## 6. Comandos

```bash
# Todas las pruebas del micro-HIS (Semanas 3 y 15)
vendor/bin/phpunit --group unitarias
vendor/bin/phpunit --group caja-blanca
vendor/bin/phpunit --group caja-negra
vendor/bin/phpunit tests/Feature/Tenants/MicroHisSmokeTest.php

# Reportes JUnit de esta carpeta (mismo comando con --log-junit)
vendor/bin/phpunit --group unitarias --log-junit docs/tenants/semana-15/unit-tests.results.xml

# Micro-benchmark en proceso
php docs/tenants/semana-15/bench/micro-his-bench.php 200
```
El proyecto no tiene scripts `npm run test:*`, Cypress, k6 ni axe configurados; los comandos `npm`
de la versión anterior no existían.

## 7. Criterios de aceptación

| Criterio | Umbral | Resultado |
|---|---|---|
| Pruebas unitarias | 0 fallos | **Cumple**: 29/29 |
| Pruebas de caja blanca | todos los caminos de WB-001 y WB-002 | **Cumple**: 17/17 |
| Pruebas de caja negra | ≥ 4 escenarios | **Cumple**: 27 casos, 5 escenarios documentados |
| Requisitos funcionales con prueba | 15/15 | **No cumple**: 12/15 (faltan RF-12, RF-13 y RF-15; RF-05 solo TOTP) |
| Cobertura de líneas | ≥ 90 % | **Sin medir** (sin Xdebug ni PCOV) |
| Rendimiento con carga, accesibilidad, E2E | según RNF | **Sin ejecutar** |
