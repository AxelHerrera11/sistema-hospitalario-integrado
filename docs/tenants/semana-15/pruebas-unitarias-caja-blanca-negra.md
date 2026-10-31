# Semana 15 — Pruebas unitarias, de caja blanca y de caja negra (parte 1)
**Módulo:** micro-HIS de Tenants (PHP vanilla, Semana 3) · **Fecha de corrida:** 2026-10-21

> **Plan y resultado van separados.** «Resultado» solo se llena con lo que produjo una corrida real
> de PHPUnit, cuyo reporte JUnit está en esta carpeta. Lo que no tiene prueba ejecutable figura como
> **No cubierto**. La parte 2 (casos de caja negra en detalle, comandos y criterios) está en
> [`pruebas-unitarias-caja-blanca-negra1.md`](pruebas-unitarias-caja-blanca-negra1.md).

## 1. Resultado de la corrida

| Reporte JUnit | Grupo / filtro de PHPUnit | Pruebas | Aserciones | Errores | Fallos |
|---|---|---|---|---|---|
| `unit-tests.results.xml` | `--group unitarias` | 29 | 71 | 0 | 0 |
| `whitebox-tests.results.xml` | `--group caja-blanca` | 17 | 42 | 0 | 0 |
| `blackbox-tests.results.xml` | `--group caja-negra` | 27 | 88 | 0 | 0 |
| `feature-tests.results.xml` | caja negra + `MicroHisSmokeTest` | 33 | 139 | 0 | 0 |

Los grupos se solapan: una prueba de caja blanca también es unitaria. Pruebas distintas en total:
29 unitarias + 33 de *feature* = **62**. Entorno: PHP 8.2.12, PHPUnit 11.5.55, Windows 11.

No se midió cobertura de código: este PHP no tiene Xdebug ni PCOV. La versión anterior informaba
96,21 % de *statements* y 100 % de cobertura por requisito sin ningún reporte que lo respaldara.

## 2. Matriz requisito → prueba

| RF | Descripción | Prueba(s) ejecutable(s) | Técnica | Resultado |
|---|---|---|---|---|
| RF-01 | Alta de usuario por hospital | `RegisterUserFeatureTest` (7 casos) | Partición de equivalencia | Aprobado |
| RF-02 | Política de contraseñas | `PasswordPolicyTest` (10 casos) | Valores límite, cobertura de ramas | Aprobado |
| RF-03 | Roles por hospital | `MfaPolicyTest`, `MultiTenantIsolationFeatureTest::test_same_user_and_device_keep_roles_and_sessions_per_hospital` | Tabla de decisión | Aprobado |
| RF-04 | Autenticación con credenciales | `LoginMfaFeatureTest` (login, credenciales inválidas, cuenta inactiva) | Partición de equivalencia | Aprobado |
| RF-05 | MFA TOTP | `LoginMfaFeatureTest::test_login_with_totp_needs_a_valid_single_use_challenge`, `SecurityPrimitivesTest` (vectores RFC 6238) | Partición de equivalencia | Aprobado |
| RF-05 | MFA WebAuthn | — | — | **No cubierto**: fuera de alcance, sin implementación |
| RF-06 | JWT de acceso | `TokenFactoryTest` (3 casos, 6 rechazos) | Cobertura de ramas | Aprobado |
| RF-07 | Rotación de refresh y detección de reutilización | `RefreshTokenRotatorTest` (6), `RefreshTokenFeatureTest` (4) | Cobertura de caminos | Aprobado |
| RF-08 | Bloqueo de access tokens revocados | `RevokeSessionFeatureTest`, `RefreshTokenFeatureTest` (verificación por sesión) | Transición de estados | Aprobado |
| RF-09 | Revocación (logout y forzada) | `RevokeSessionFeatureTest` (4 casos) | Partición de equivalencia | Aprobado |
| RF-10 | Aislamiento entre hospitales | `MultiTenantIsolationFeatureTest` (5 casos) | Transición de estados | Aprobado |
| RF-11 | Bloqueo progresivo | `LoginMfaFeatureTest` (5 fallos y 3 códigos MFA), `MultiTenantIsolationFeatureTest::test_failed_logins_lock_only_the_membership_of_that_hospital` | Valores límite | Aprobado |
| RF-12 | Sesión única por política | — | — | **No cubierto**: la política existe en `Tenant`, sin prueba |
| RF-13 | Huella de dispositivo | — | — | **No cubierto**: `DeviceTrustService` sin prueba |
| RF-14 | Auditoría encadenada | `SecurityPrimitivesTest::test_audit_log_is_hash_chained_and_rejects_secrets`, `RefreshTokenFeatureTest` | Cobertura de caminos | Aprobado |
| RF-15 | Aprovisionamiento JIT por SSO | — | — | **No cubierto**: sin implementación |

RF cubiertos por al menos una prueba aprobada: **12 de 15** (RF-01 a RF-11 y RF-14; RF-05 solo en
su parte TOTP). Sin prueba: RF-12, RF-13 y RF-15.

Los RNF (rendimiento, disponibilidad, TLS, escalabilidad, recuperación ante desastres, accesibilidad)
**no tienen prueba ejecutada** en esta entrega. El único dato de rendimiento es un micro-benchmark
en proceso (ver `performance-report.html`), que no prueba carga.

## 3. Casos unitarios (grupo `unitarias`)

| ID | Clase de prueba | Qué verifica | Casos |
|---|---|---|---|
| UT-001 | `Tests\Unit\Tenants\Domain\Services\PasswordPolicyTest` | Clave fuerte aceptada; hash Argon2id verificable; cada regla de complejidad, historial y filtración | 10 |
| UT-002 | `Tests\Unit\Tenants\Domain\Services\MfaPolicyTest` | MFA exigido a `superadmin` y `admin_hospital`; política del hospital y del usuario; solo TOTP | 10 |
| UT-003 | `Tests\Unit\Tenants\Domain\Services\RefreshTokenRotatorTest` | Emisión sin secreto en claro; rotación; reutilización; token desconocido, revocado o vencido | 6 |
| UT-004 | `Tests\Unit\Tenants\Domain\Services\TokenFactoryTest` | Emisión y validación del JWT; rechazo de firma ajena, `alg none`, audiencia, emisor, vencido y mal formado | 3 |

Los dobles son *fakes* de interfaces (`CompromisedPasswordChecker`) o repositorios reales en memoria.
No se hacen dobles de clases `final`: con PHPUnit 11 esa era la causa de los errores en `setUp`.

## 4. Casos de caja blanca (grupo `caja-blanca`)

### WB-001 · Ramas de `PasswordPolicy::validate()`
| Rama | Entrada | Esperado | Prueba |
|---|---|---|---|
| Longitud < 12 | `Corta-1a` | error «al menos 12 caracteres» | `test_each_complexity_branch_reports_its_error` (dataset) |
| Sin mayúscula | `clave-segura-2026!` | error «una mayúscula» | ídem |
| Sin minúscula | `CLAVE-SEGURA-2026!` | error «una minúscula» | ídem |
| Sin número | `Clave-Segura-Dos!` | error «un número» | ídem |
| Sin símbolo | `ClaveSegura2026` | error «un símbolo» | ídem |
| Historial | clave igual a un hash previo | error «últimas 5» | `test_password_from_history_is_rejected` |
| Filtración | verificador devuelve `true` | error «comprometida» | `test_compromised_password_is_rejected` |
| Corto circuito | clave ya inválida | el verificador externo **no** se consulta | `test_breach_service_is_not_called_when_the_password_already_fails` |

### WB-002 · Caminos de `RefreshTokenRotator::rotate()`
```
parse(raw) ─ null ─────────────────────────────► failure            (camino 1)
   │ ok
findById(id) ─ null / secreto distinto ────────► failure            (caminos 1 y 2)
   │ coincide
isRotated() ─ sí ──────────────────────────────► reuseDetected      (camino 3)
   │ no
isRevoked() || isExpired() ─ sí ───────────────► failure            (camino 4)
   │ no
markRotated() + issue() ───────────────────────► success            (camino 5)
```
| Camino | Prueba |
|---|---|
| 1 token mal formado o desconocido | `test_path_unknown_or_malformed_token_fails_without_reuse` |
| 2 secreto incorrecto | `test_path_wrong_secret_fails_without_revealing_reuse` |
| 3 reutilización | `test_path_rotated_token_presented_again_is_reuse` |
| 4 revocado o vencido | `test_path_revoked_and_expired_tokens_fail` |
| 5 rotación válida | `test_path_valid_token_rotates_and_old_one_is_marked` |

### WB-003 · Ramas de `MfaPolicy::evaluate()` y de `JwtTokenService::validateAccessToken()`
- Política del hospital, bandera del usuario y rol privilegiado entre varios (`MfaPolicyTest`, 3 casos).
- Cada restricción de validación del JWT (firma, emisor, audiencia, vigencia y formato) tiene su caso
  de rechazo en `TokenFactoryTest::test_rejects_tampered_foreign_expired_and_malformed_tokens`.
