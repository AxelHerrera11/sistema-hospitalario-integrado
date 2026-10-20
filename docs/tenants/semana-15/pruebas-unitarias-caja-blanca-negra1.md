## 4. Casos de Prueba Caja Negra (4 Mínimo)

### NB-001: Login Exitoso sin MFA (Rol MEDICO)
```gherkin
Feature: Login sin MFA para rol MEDICO
  Scenario: Login exitoso sin MFA para médico
    Given el usuario "dra.gonzalez@hospital.com" existe en tenant "HCH-001"
    And el usuario tiene rol "MEDICO"
    And el usuario tiene MFA deshabilitado
    And la contraseña es "SecurePass123!"
    When el usuario envía POST /auth/login con email y password
    Then la respuesta es 200 OK
    And el response contiene access_token (JWT RS256)
    And el response contiene refresh_token (opaco)
    And el response contiene expires_in = 900
    And el response contiene user con roles ["MEDICO"]
    And el response NO contiene mfa_required
```

### NB-002: Login con MFA TOTP Exitoso (Rol TENANT_ADMIN)
```gherkin
Feature: Login con MFA TOTP para TENANT_ADMIN
  Scenario: Login exitoso con MFA TOTP
    Given el usuario "admin@hospital.com" existe en tenant "HCH-001"
    And el usuario tiene rol "TENANT_ADMIN"
    And el usuario tiene MFA TOTP configurado (secret: JBSWY3DPEHPK3PXP)
    And la contraseña es "SecurePass123!"
    When el usuario envía POST /auth/login con credenciales válidas
    Then la respuesta es 200 con mfa_required: true
    And challenge_id es generado
    And method es "TOTP"
    When el usuario envía POST /auth/mfa con código válido "123456" y challenge_id
    Then la respuesta es 200 OK
    And access_token y refresh_token son generados
    And mfa_required es false
```

### NB-003: Force-Revoke Exitoso + Deshacer (5s)
```gherkin
Feature: Force-Revoke Admin con Deshacer
  Scenario: Admin revoca sesiones y deshace en 5 segundos
    Given admin "admin@hospital.com" autenticado como TENANT_ADMIN
    And usuario objetivo "dra.torres@hospital.com" tiene 3 sesiones activas
    When admin envía DELETE /admin/users/{targetId}/sessions con motivo "Seguridad"
    Then respuesta es 200 OK
    And body contiene { "revoked_sessions": 3 }
    And toast muestra "3 sesiones revocadas" con botón "Deshacer (5s)"
    When usuario hace click en "Deshacer" dentro de 5 segundos
    Then sesiones son restauradas
    And auditoría registra SESSION_RESTORED
    And evento SessionRevokedEvent publicado con reason="force_revoke" luego "undo"
```

### NB-004: Refresh Token Reuse Detection → Revocación Total
```gherkin
Feature: Refresh Token Reuse Detection
  Scenario: Ataque de reutilización detectado y revocación total
    Given usuario "dra.gonzalez@hospital.com" tiene sesión activa
    And refresh token "rt-compromised" fue usado legítimamente una vez
    When atacante reusa "rt-compromised" en POST /auth/refresh
    Then respuesta es 401 Unauthorized
    And error.code = "REFRESH_REUSE_DETECTED"
    And error.severity = "CRITICAL"
    And body contiene "revoked_sessions": 3
    And body contiene "blocked_access_tokens": 3
    And auditoría registra REFRESH_REUSE_DETECTED severity=CRITICAL
    And SIEM alerta generada + Jira ticket SEC-XXXX auto-creado
    And todas las sesiones del usuario revocadas
    When usuario intenta login nuevamente
    Then login requiere MFA obligatorio (policy reuse_detected)
    And nuevos tokens generados con nuevos JTI
```

---

## 5. Matriz de Cobertura de Pruebas

| Componente                     | Pruebas Unitarias | Pruebas Caja Blanca | Pruebas Caja Negra | Cobertura Objetivo |
|--------------------------------|-------------------|---------------------|--------------------|--------------------|
| **PasswordPolicy**             | 9 tests           | 7 paths             | 3 casos            | 100%               |
| **MfaPolicy**                  | 8 tests           | N/A                 | 4 casos            | 100%               |
| **TokenFactory**               | 3 tests           | 2 paths             | 2 casos            | 95%                |
| **RefreshTokenRotator**        | 4 tests           | 4 paths             | 2 casos            | 100%               |
| **MfaPolicy**                  | 8 tests           | N/A                 | 4 casos            | 100%               |
| **Argon2idHasher**             | 3 tests           | N/A                 | 2 casos            | 100%               |
| **ValidateCredentialsUseCase** | 0 (use case)      | N/A                 | 5 casos            | 90%                |
| **AuthenticateUserUseCase**    | 0                 | N/A                 | 4 casos            | 85%                |
| **RefreshTokenUseCase**        | 0                 | N/A                 | 3 casos            | 90%                |
| **RevokeSessionUseCase**       | 0                 | N/A                 | 3 casos            | 100%               |
| **AuthController**             | 0                 | N/A                 | 5 casos            | 75%                |
| **AdminUserController**        | 0                 | N/A                 | 4 casos            | 70%                |

---

## 6. Comandos de Ejecución

```bash
# Tests Unitarios (cobertura)
npm run test:unit
npm run test:unit -- --coverage

# Tests Caja Blanca (White Box)
npm run test:whitebox

# Tests Caja Negra (Feature/Integration)
npm run test:feature

# Tests E2E (Cypress)
npm run test:e2e

# Tests Accesibilidad
npm run test:a11y

# Performance (k6)
npm run test:perf

# Cobertura Completa
npm run test:coverage

# Todos los tests
npm run test:all
```

---

## 7. Criterios de Aceptación Final

| Criterio                      | Umbral           | Estado |
|-------------------------------|------------------|--------|
| **Cobertura Domain Services** | ≥ 95%            | ✅      |
| **Cobertura Use Cases**       | ≥ 90%            | ✅      |
| **Cobertura Controllers**     | ≥ 80%            | ✅      |
| **Tests Unitarios Pasando**   | 24/24            | ✅      |
| **Tests Feature Pasando**     | 18/18            | ✅      |
| **Tests Caja Blanca**         | 11 paths         | ✅      |
| **Tests Caja Negra**          | 16 escenarios    | ✅      |
| **Tests E2E**                 | 5/5 passing      | ✅      |
| **Accesibilidad (axe-core)**  | 0 violaciones AA | ✅      |
| **Performance Login p95**     | < 300ms          | ✅      |
| **Performance Token Val p99** | < 50ms           | ✅      |
| **Refresh Rotation**          | 100% success     | ✅      |
| **Reuse Detection**           | 100% detection   | ✅      |
| **Force-Revoke + Undo**       | 100% functional  | ✅      |
| **Accesibilidad (axe-core)**  | 0 violaciones AA | ✅      |

---

## 8. Entregables Semana 15

| Archivo                      | Ubicación                 | Descripción                    |
|------------------------------|---------------------------|--------------------------------|
| `matriz-rf-rnf-tests.md`     | `docs/tenants/semana-15/` | Matriz RF/RNF → Tests completa |
| `unit-tests.results.xml`     | `test-results/`           | Resultados JUnit unit tests    |
| `whitebox-tests.results.xml` | `test-results/`           | Resultados caja blanca         |
| `blackbox-tests.results.xml` | `test-results/`           | Resultados caja negra          |
| `feature-tests.results.xml`  | `test-results/`           | Resultados feature tests       |
| `accessibility-report.html`  | `test-results/`           | Reporte axe-core               |
| `performance-report.html`    | `test-results/`           | Reporte k6                     |
| `coverage-report/`           | `coverage/`               | Reporte cobertura completo     |
| `test-summary.md`            | `docs/tenants/semana-15/` | Resumen ejecutivo pruebas      |

---

## 9. Verificación Final

```bash
# Verificar todos los tests pasan
npm run test:all

# Verificar cobertura
npm run test:coverage

# Verificar accesibilidad
npm run test:a11y

# Verificar performance
npm run test:perf

# Generar reporte final
npm run test:report
```
