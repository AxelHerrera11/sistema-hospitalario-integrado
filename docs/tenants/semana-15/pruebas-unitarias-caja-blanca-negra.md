# Semana 15 — Pruebas Unitarias, Caja Blanca y Caja Negra
## 1. Matriz de Trazabilidad RF/RNF → Casos de Prueba
| ID Requisito | Descripción                             | Tipo Prueba                       | Casos de Prueba (ID)           | Técnica                                      | Cobertura |
|--------------|-----------------------------------------|-----------------------------------|--------------------------------|----------------------------------------------|-----------|
| **RF-01**    | Alta usuario por tenant                 | Unidad / Caja Negra               | UT-001, UT-002, NB-001, NB-002 | Partición Equivalencia, Valores Límite       | 100%      |
| **RF-02**    | Password Policy                         | Unidad / Caja Blanca              | UT-003, UT-004, WB-001, WB-002 | Partición Equivalencia, Análisis Condiciones | 100%      |
| **RF-03**    | Roles por tenant                        | Unidad / Caja Negra               | UT-005, NB-003                 | Tabla Decisiones                             | 100%      |
| **RF-04**    | Autenticación credenciales              | Unidad / Caja Blanca / Caja Negra | UT-006, WB-003, NB-004         | Cobertura Sentencias, Partición Equivalencia | 100%      |
| **RF-05**    | MFA Adaptativo (TOTP/WebAuthn)          | Unidad / Caja Blanca / Caja Negra | UT-007, WB-004, NB-005         | Cobertura Ramas, Partición Equivalencia      | 100%      |
| **RF-06**    | JWT RS256 + Refresh Rotation            | Unidad / Caja Blanca              | UT-008, WB-005                 | Cobertura Ramas, Path Testing                | 100%      |
| **RF-07**    | Refresh Rotation + Reuse Detection      | Unidad / Caja Blanca              | UT-009, WB-006                 | Path Testing, Mutation Testing               | 100%      |
| **RF-08**    | Blocklist Access Tokens                 | Unidad / Caja Negra               | UT-010, NB-006                 | Partición Equivalencia, Pruebas Estado       | 100%      |
| **RF-09**    | Revocación Sesión (Logout/Force-Revoke) | Unidad / Caja Blanca / Caja Negra | UT-011, WB-007, NB-007         | Path Testing, Partición Equivalencia         | 100%      |
| **RF-10**    | Aislamiento Multi-tenant (RLS)          | Unidad / Caja Negra               | UT-012, NB-008                 | Partición Equivalencia, Pruebas Estado       | 100%      |
| **RF-11**    | Lockout Progresivo                      | Unidad / Caja Blanca              | UT-013, WB-008                 | Path Testing, Boundary Value                 | 100%      |
| **RF-12**    | Single Session Policy                   | Unidad / Caja Negra               | UT-014, NB-009                 | Partición Equivalencia                       | 100%      |
| **RF-13**    | Device Fingerprinting                   | Unidad / Caja Negra               | UT-015, NB-010                 | Partición Equivalencia                       | 100%      |
| **RF-14**    | Auditoría Inmutable                     | Unidad / Caja Blanca              | UT-016, WB-009                 | Path Testing, Mutation Testing               | 100%      |
| **RF-15**    | JIT Provisioning SSO                    | Unidad / Caja Negra               | UT-017, NB-011                 | Partición Equivalencia                       | 100%      |

### RNFs - Pruebas No Funcionales
| ID Requisito | Descripción                          | Tipo Prueba                   | Casos de Prueba    | Herramienta             | Criterio Éxito            |
|--------------|--------------------------------------|-------------------------------|--------------------|-------------------------|---------------------------|
| **RNF-01**   | Disponibilidad 99.99%                | Caja Negra / Carga            | NB-012, NB-013     | k6 / Chaos Mesh         | 0 downtime no planificado |
| **RNF-02**   | Login p95 < 300ms                    | Performance                   | PERF-001, PERF-002 | k6                      | p95 < 300ms               |
| **RNF-03**   | Token Validation p99 < 50ms          | Performance                   | PERF-003           | k6 / Synthetic          | p99 < 50ms                |
| **RNF-04**   | Revocación < 100ms                   | Performance                   | PERF-004           | k6                      | p99 < 100ms               |
| **RNF-05**   | Throughput 500-2000 req/s            | Stress/Load                   | PERF-005, PERF-006 | k6                      | 0 errores, latency stable |
| **RNF-06**   | TLS 1.3 + HSTS                       | Seguridad / Caja Negra        | SEC-001, SEC-002   | OWASP ZAP / testssl.sh  | A+ SSL Labs               |
| **RNF-07**   | AES-256 / Argon2id                   | Caja Blanca / Revisión Código | SEC-003, SEC-004   | Revisión Código / Trivy | 0 hallazgos Críticos      |
| **RNF-08**   | Auditoría 7 años                     | Caja Negra / Auditoría        | NB-014             | Verificación Logs       | 0 gaps                    |
| **RNF-09**   | Evidencias 90d WORM                  | Caja Negra / Auditoría        | NB-015             | Verificación S3         | 100% inmutables           |
| **RNF-10**   | OWASP ASVS L2 / NIST 800-63B         | Seguridad / Revisión          | SEC-005 a SEC-010  | OWASP ZAP / Manual      | Nivel 2 Cumplido          |
| **RNF-11**   | Escalabilidad K8s HPA                | Escalabilidad                 | PERF-007           | k6 + K8s                | HPA < 2 min               |
| **RNF-12**   | Observabilidad (OTel, Prom, Grafana) | Observabilidad                | OBS-001 a OBS-005  | Dashboards              | 100% instrumentado        |
| **RNF-13**   | RPO=0, RTO<15min                     | DR Testing                    | DR-001, DR-002     | DR Drill                | RPO=0, RTO<15min          |
| **RNF-14**   | MFA Success ≥ 95%                    | Caja Negra / Métricas         | NB-016             | Métricas Auth           | ≥ 95%                     |
| **RNF-15**   | WCAG 2.1 AA                          | Accesibilidad                 | ACC-001 a ACC-020  | axe-core + Manual       | 0 violaciones AA          |

## 2. Casos de Prueba Unitarios (3 Mínimo)
### UT-001: Validar Creación Usuario Exitoso
```typescript
describe('PasswordPolicy', () => {
  let passwordPolicy: PasswordPolicy;
  let hasher: Argon2idHasher;
  let hibpClient: HaveIBeenPwnedClient;
  beforeEach(() => {
    hasher = new Argon2idHasher();
    hibpClient = createMock<HaveIBeenPwnedClient>();
    passwordPolicy = new PasswordPolicy(hasher, hibpClient, 12, true, true, 5);
  });
  it('debe validar contraseña fuerte correctamente', () => {
    const result = passwordPolicy.validate('SecurePass123!', []);
    expect(result.isValid()).toBe(true);
    expect(result.errors()).toHaveLength(0);
  });
  it('debe rechazar contraseña corta (< 12 chars)', () => {
    const result = passwordPolicy.validate('Short1!', []);
    expect(result.isValid()).toBe(false);
    expect(result.errors()).toContain('Contraseña debe tener al menos 12 caracteres');
  });
  it('debe rechazar contraseña sin mayúscula', () => {
    const result = passwordPolicy.validate('lowercase123!', []);
    expect(result.isValid()).toBe(false);
    expect(result.errors()).toContain('Contraseña debe contener al menos una mayúscula');
  });
  it('debe rechazar contraseña en historial (últimas 5)', () => {
    const oldHash = hasher.hash('OldPassword123!');
    const result = passwordPolicy.validate('OldPassword123!', [oldHash]);
    expect(result.isValid()).toBe(false);
    expect(result.errors()).toContain('Contraseña no puede coincidir con las últimas 5 contraseñas');
  });
  it('debe rechazar contraseña comprometida (HIBP)', () => {
    hibpClient.check.mockResolvedValue(new HibpResult(true, 100));
    const result = passwordPolicy.validate('CompromisedPass123!', []);
    expect(result.isValid()).toBe(false);
    expect(result.errors()).toContain('Contraseña ha sido comprometida');
  });
  it('debe hashear correctamente con Argon2id', () => {
    const password = passwordPolicy.hash('TestPassword123!');
    expect(password.hash()).toMatch(/^\$argon2id\$v=\d+\$/);
    expect(hasher.verify('TestPassword123!', password.hash())).toBe(true);
  });
});
```
### UT-002: Validar Política MFA por Rol
```typescript
describe('MfaPolicy', () => {
  let mfaPolicy: MfaPolicy;
  beforeEach(() => {
    mfaPolicy = new MfaPolicy();
  });
  it('TENANT_ADMIN siempre requiere MFA', () => {
    const result = mfaPolicy.evaluate(
      [new Role(Role.TENANT_ADMIN)],
      { mfa_required: false },
      false
    );
    expect(result.required()).toBe(true);
    expect(result.type()).toBe('TOTP');
  });
  it('AUDITOR siempre requiere MFA', () => {
    const result = mfaPolicy.evaluate(
      [new Role(Role.AUDITOR)],
      { mfa_required: false },
      false
    );
    expect(result.required()).toBe(true);
  });
  it('MEDICO no requiere MFA por defecto', () => {
    const result = mfaPolicy.evaluate(
      [new Role(Role.MEDICO)],
      { mfa_required: false },
      false
    );
    expect(result.required()).toBe(false);
  });
  it('Política tenant MFA obligatorio anula rol', () => {
    const result = mfaPolicy.evaluate(
      [new Role(Role.MEDICO)],
      { mfa_required: true },
      false
    );
    expect(result.required()).toBe(true);
  });
  it('Usuario con mfaRequired=true requiere MFA', () => {
    const result = mfaPolicy.evaluate(
      [new Role(Role.MEDICO)],
      { mfa_required: false },
      true
    );
    expect(result.required()).toBe(true);
  });
  it('WebAuthn habilitado por tenant policy', () => {
    const result = mfaPolicy.evaluate(
      [new Role(Role.TENANT_ADMIN)],
      { mfa_required: false, mfa_webauthn_enabled: true },
      false
    );
    expect(result.required()).toBe(true);
    expect(result.type()).toBe('WEBAUTHN');
  });
});
```
### UT-003: Validar Rotación Refresh Token + Reuse Detection
```typescript
describe('RefreshTokenRotator', () => {
  let rotator: RefreshTokenRotator;
  let mockRepo: RefreshTokenRepositoryInterface;
  let mockTokenFactory: TokenFactory;
  let hasher: Argon2idHasher;
  beforeEach(() => {
    mockRepo = createMock<RefreshTokenRepositoryInterface>();
    mockTokenFactory = createMock<TokenFactory>();
    hasher = new Argon2idHasher();
    rotator = new RefreshTokenRotator(mockRepo, hasher, mockTokenFactory, 604800);
  });
  it('debe generar y almacenar refresh token correctamente', () => {
    const user = createMockUser();
    const tenant = createMockTenant();
    const device = new DeviceFingerprint('dev-1', '192.168.1.1', 'Mozilla/5.0');
    const sessionId = SessionId.generate();
    mockRepo.save.mockResolvedValue(undefined);
    const result = rotator.generateAndStore(user, tenant, device, sessionId, 'access-jti-123');
    expect(result.token()).toBeDefined();
    expect(result.expiresAt()).toBeGreaterThan(Date.now());
    expect(mockRepo.save).toHaveBeenCalledWith(expect.any(RefreshToken));
  });
  it('debe rotar tokens exitosamente (hash coincide)', () => {
    const oldToken = 'old-refresh-token';
    const oldHash = hasher.hash(oldToken);
    const storedToken = RefreshToken.reconstitute(
      oldToken, oldHash, userId, tenantId, device, 'old-access-jti',
      new Date(Date.now() - 3600000), new Date(Date.now() + 604800000), false
    );
    mockRepo.findByUserIdAndDevice.mockResolvedValue(storedToken);
    const newAccessToken = new AccessToken('new.access.token', new JwtClaims(...));
    mockTokenFactory.createAccessToken.mockReturnValue(newAccessToken);
    mockRepo.delete.mockResolvedValue(undefined);
    mockRepo.save.mockResolvedValue(undefined);
    const result = rotator.rotate(oldToken, user, tenant, device, sessionId);
    expect(result.isSuccess()).toBe(true);
    expect(result.accessToken()).toBe('new.access.token');
    expect(result.refreshToken()).toBeDefined();
    expect(mockRepo.delete).toHaveBeenCalledWith(storedToken);
    expect(mockRepo.save).toHaveBeenCalled();
  });
  it('debe detectar REUSE ATTACK (hash no coincide)', () => {
    const oldToken = 'old-refresh-token';
    const oldHash = hasher.hash(oldToken);
    const storedToken = RefreshToken.reconstitute(
      oldToken, oldHash, userId, tenantId, device, 'old-access-jti',
      new Date(Date.now() - 3600000), new Date(Date.now() + 604800000), false
    );
    mockRepo.findByUserIdAndDevice.mockResolvedValue(storedToken);

    const result = rotator.rotate('different-token', user, tenant, device, sessionId);
    expect(result.isSuccess()).toBe(false);
    expect(result.isReuseDetected()).toBe(true);
    expect(result.error()).toBe('Token compromise detected');
  });
  it('debe fallar si refresh token no existe (revocado/expirado)', () => {
    mockRepo.findByUserIdAndDevice.mockResolvedValue(null);
    const result = rotator.rotate('any-token', user, tenant, device, sessionId);
    expect(result.isSuccess()).toBe(false);
    expect(result.isReuseDetected()).toBe(false);
    expect(result.error()).toBe('Refresh token revocado o expirado');
  });
});
```
## 3. Casos de Prueba Caja Blanca (2 Mínimo)
### WB-001: Cobertura Ramas - PasswordPolicy.validate()
```typescript
describe('PasswordPolicy - White Box (Branch Coverage)', () => {
  let policy: PasswordPolicy;
  let hasher: Argon2idHasher;
  let hibpClient: HaveIBeenPwnedClient;
  beforeEach(() => {
    hasher = new Argon2idHasher();
    hibpClient = createMock<HaveIBeenPwnedClient>();
    policy = new PasswordPolicy(hasher, hibpClient, 12, true, true, 5);
  });
  it('debe cubrir rama: longitud insuficiente', () => {
    const result = policy.validate('Short1!', []);
    expect(result.isValid()).toBe(false);
    expect(result.errors()).toContain('al menos 12 caracteres');
  });
  it('debe cubrir rama: falta mayúscula', () => {
    const result = policy.validate('lowercase123!', []);
    expect(result.isValid()).toBe(false);
    expect(result.errors()).toContain('mayúscula');
  });
  it('debe cubrir rama: falta minúscula', () => {
    const result = policy.validate('UPPERCASE123!', []);
    expect(result.isValid()).toBe(false);
    expect(result.errors()).toContain('minúscula');
  });

  it('debe cubrir rama: falta número', () => {
    const result = policy.validate('NoNumbers!', []);
    expect(result.isValid()).toBe(false);
    expect(result.errors()).toContain('número');
  });

  it('debe cubrir rama: falta símbolo', () => {
    const result = policy.validate('NoSymbols123', []);
    expect(result.isValid()).toBe(false);
    expect(result.errors()).toContain('símbolo');
  });

  it('debe cubrir rama: contraseña en historial', () => {
    const oldHash = new Argon2idHasher().hash('OldPass123!');
    const result = policy.validate('OldPass123!', [oldHash]);
    expect(result.isValid()).toBe(false);
    expect(result.errors()).toContain('últimas 5');
  });

  it('debe cubrir rama: HIBP comprometida', () => {
    const mockHibp = createMock<HaveIBeenPwnedClient>();
    mockHibp.check.mockResolvedValue(new HibpResult(true, 50));
    const policyWithHibp = new PasswordPolicy(new Argon2idHasher(), mockHibp, 12, true, true, 5);
    const result = policyWithHibp.validate('CompromisedPass123!', []);
    expect(result.isValid()).toBe(false);
    expect(result.errors()).toContain('comprometida');
  });

  it('debe cubrir rama: HIBP falla (fail-open)', () => {
    const mockHibp = createMock<HaveIBeenPwnedClient>();
    mockHibp.check.mockRejectedValue(new Error('Network error'));
    const policyWithHibp = new PasswordPolicy(new Argon2idHasher(), mockHibp, 12, true, true, 5);
    const result = policyWithHibp.validate('ValidPass123!', []);
    expect(result.isValid()).toBe(true); // Fail-open
  });
});
```

### WB-002: Cobertura Ramas - RefreshTokenRotator.rotate()
```typescript
// tests/whitebox/RefreshTokenRotator.whitebox.test.ts
describe('RefreshTokenRotator - White Box (Branch/Path Coverage)', () => {
  let rotator: RefreshTokenRotator;
  let mockRepo: RefreshTokenRepositoryInterface;
  let mockTokenFactory: TokenFactory;
  let hasher: Argon2idHasher;
  let user: User;
  let tenant: Tenant;
  let device: DeviceFingerprint;
  let sessionId: SessionId;

  beforeEach(() => {
    mockRepo = createMock<RefreshTokenRepositoryInterface>();
    mockTokenFactory = createMock<TokenFactory>();
    hasher = new Argon2idHasher();
    rotator = new RefreshTokenRotator(mockRepo, hasher, mockTokenFactory, 604800);

    user = createMockUser();
    tenant = createMockTenant();
    device = DeviceFingerprint.createNew('dev-1', '192.168.1.1', 'Mozilla/5.0');
    sessionId = SessionId.generate();
  });

  it('Path: Rotación exitosa - hash coincide', () => {
    const oldToken = 'valid-refresh-token';
    const oldHash = hasher.hash(oldToken);
    const storedToken = RefreshToken.reconstitute(
      oldToken, oldHash, user.id(), tenant.id(), device, 'old-jti',
      new Date(Date.now() - 3600000), new Date(Date.now() + 604800000), false
    );

    mockRepo.findByUserIdAndDevice.mockResolvedValue(storedToken);
    mockRepo.delete.mockResolvedValue(undefined);
    mockRepo.save.mockResolvedValue(undefined);

    const newAccessToken = new AccessToken('new.access.token', new JwtClaims(...));
    mockTokenFactory.createAccessToken.mockReturnValue(newAccessToken);

    const result = rotator.rotate('valid-refresh-token', user, tenant, device, sessionId);

    expect(result.isSuccess()).toBe(true);
    expect(result.accessToken()).toBe('new.access.token');
    expect(result.refreshToken()).toBeDefined();
    expect(mockRepo.delete).toHaveBeenCalledWith(storedToken);
    expect(mockRepo.save).toHaveBeenCalled();
  });

  it('Path: Reuse Detection - hash NO coincide', () => {
    const oldToken = 'old-token';
    const oldHash = hasher.hash(oldToken);
    const storedToken = RefreshToken.reconstitute(
      'old-token', oldHash, user.id(), tenant.id(), device, 'old-jti',
      new Date(Date.now() - 3600000), new Date(Date.now() + 604800000), false
    );

    mockRepo.findByUserIdAndDevice.mockResolvedValue(storedToken);
    mockRepo.keys.mockResolvedValue(['refresh:user1:dev1']);

    const result = rotator.rotate('different-token', user, tenant, device, sessionId);

    expect(result.isSuccess()).toBe(false);
    expect(result.isReuseDetected()).toBe(true);
    expect(result.error()).toBe('Token compromise detected');
    expect(mockRepo.keys).toHaveBeenCalled();
    expect(mockRepo.delete).toHaveBeenCalledTimes(1); // All refresh tokens deleted
  });

  it('Path: Token no encontrado en Redis (revocado/expirado)', () => {
    mockRepo.findByUserIdAndDevice.mockResolvedValue(null);

    const result = rotator.rotate('any-token', user, tenant, device, sessionId);

    expect(result.isSuccess()).toBe(false);
    expect(result.isReuseDetected()).toBe(false);
    expect(result.error()).toBe('Refresh token revocado o expirado');
  });

  it('Path: generateAndStore - crea nuevo token con hash', () => {
    const result = rotator.generateAndStore(user, tenant, device, sessionId, 'access-jti-123');

    expect(result.token()).toBeDefined();
    expect(result.expiresAt()).toBeGreaterThan(Date.now());
    expect(mockRepo.save).toHaveBeenCalledWith(expect.objectContaining({
      hash: expect.any(String), // hash Argon2id
      accessTokenJti: 'access-jti-123'
    }));
  });
});
```
