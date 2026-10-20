# Semana 06 — Cambio Práctico Defendido: Migración RS256 → ES256
## 1. Planteamiento del Cambio
### Solicitud del Equipo de Seguridad
> "Se requiere migrar el algoritmo de firma JWT de **RS256 (RSA 2048-bit + SHA-256)** a **ES256 (ECDSA P-256 + SHA-256)** para:
> - Reducir tamaño de tokens (~40% más pequeños)
> - Mejorar rendimiento de firma/validación (2-3x más rápido)
> - Mejor compatibilidad con dispositivos móviles/embebidos
> - Alineación con recomendaciones NIST para criptografía post-cuántica preparada"

## 2. Análisis de Impacto con Arquitectura Actual
### 2.1 Matriz de Impacto por Capa

| Capa               | Componente                                    | Impacto                            | Archivos a Modificar | Esfuerzo |
|--------------------|-----------------------------------------------|------------------------------------|----------------------|----------|
| **DOMAIN**         | `TokenFactory` (Interface)                    | **NINGUNO**                        | 0                    | 0h       |
|                    | `JwtClaims` (Value Object)                    | **NINGUNO**                        | 0                    | 0h       |
|                    | `AccessToken` (Value Object)                  | **NINGUNO**                        | 0                    | 0h       |
|                    | `RefreshTokenRotator`                         | **NINGUNO**                        | 0                    | 0h       |
|                    | `MfaPolicy`, `PasswordPolicy`, etc.           | **NINGUNO**                        | 0                    | 0h       |
| **APPLICATION**    | `AuthenticateUserUseCase`                     | **NINGUNO**                        | 0                    | 0h       |
|                    | `RefreshTokenUseCase`                         | **NINGUNO**                        | 0                    | 0h       |
|                    | `RegisterUserUseCase`, `RevokeSessionUseCase` | **NINGUNO**                        | 0                    | 0h       |
| **INFRASTRUCTURE** | `JwtTokenService`                             | **ALTO** (Implementación concreta) | 1 principal + config | 2-3h     |
|                    | `ModuleBootstrap.php` (DI Config)             | **MEDIO**                          | 1                    | 0.5h     |
|                    | Vault/Key Management                          | **MEDIO**                          | Operacional          | 1-2h     |
| **PRESENTATION**   | `AuthController`                              | **NINGUNO**                        | 0                    | 0h       |
|                    | `AuthResponseTransformer`                     | **NINGUNO**                        | 0                    | 0h       |
|                    | `JwtAuthMiddleware`                           | **NINGUNO** (usa JwtTokenService)  | 0                    | 0h       |
| **TESTS**          | `TokenFactoryTest`                            | **MEDIO**                          | 1                    | 0.5h     |
|                    | `JwtTokenServiceTest` (nuevo)                 | **ALTO**                           | 1 nuevo              | 1h       |

**TOTAL ARCHIVOS A MODIFICAR: 4 (1 nuevo test)**
**TIEMPO ESTIMADO: 4-7 horas**

### 2.2 Comparación: Arquitectura Monolítica Tradicional vs Actual
| Aspecto              | Monolito Tradicional (AuthController 116 líneas)          | Arquitectura Actual (Clean Architecture)   |
|----------------------|-----------------------------------------------------------|--------------------------------------------|
| **Archivos a tocar** | 8-12 (Controller + Middleware + Config + Tests dispersos) | **4** (1 servicio + config + 2 tests)      |
| **Riesgo regresión** |  Alto (lógica mezclada HTTP+JWT+BD)                       |  Bajo (aislado en JwtTokenService)         |
| **Tiempo estimado**  | 2-3 días                                                  | **4-7 horas**                              |
| **Rollback**         | Complejo (cambios dispersos)                              | Inmediato (feature flag `jwt_algorithm`)   |
| **Validación**       | Tests E2E lentos + manuales                               | Tests unitarios < 30s + contract tests     |
| **Zero-downtime**    | Difícil                                                   |  JWKS soporta múltiples `kid` simultáneos  |

## 3. Plan de Implementación Detallado
### Paso 1: Preparación de Claves EC P-256 (Operacional - 1-2h)

```bash
# Generar par de claves EC P-256 (prime256v1)
openssl ecparam -name prime256v1 -genkey -noout -out private_key_ec.pem
openssl ec -in private_key_ec.pem -pubout -out public_key_ec.pem

# Formato PEM para variables de entorno
JWT_PRIVATE_KEY_EC="$(cat private_key_ec.pem | sed ':a;N;s/\n/\\n/g;ta')"
JWT_PUBLIC_KEY_EC="$(cat public_key_ec.pem | sed ':a;N;s/\n/\\n/g;ta')"

# Almacenar en HashiCorp Vault / AWS Secrets Manager / Azure Key Vault
vault kv put secret/jwt/ec keys=@private_key_ec.pem
vault kv put secret/jwt/ec public=@public_key_ec.pem
```

### Paso 2: Modificar `JwtTokenService.php` (2-3h)
```php
// Cambios clave en JwtTokenService.php
use Lcobucci\JWT\Signer\Ecdsa\Sha256; 
use Lcobucci\JWT\Signer\Key\InMemory;

final class JwtTokenService implements TokenFactory
{
    // ... existing code ...
    
    public function __construct(
        string $privateKeyPem,
        string $publicKeyPem,
        string $issuer = 'his-tenants-auth',
        string $audience = 'his-api',
        int $accessTokenTtl = 900,
        string $keyId = 'ec-key-1',  
        string $algorithm = 'ES256'  
    ) {
        // Soporte dual durante transición
        $this->privateKey = InMemory::plainText($privateKeyPem);
        $this->publicKey = InMemory::plainText($publicKeyPem);
        
        // Selector de algoritmo
        $this->signer = match ($algorithm) {
            'ES256' => new Sha256(),  
            'RS256' => new \Lcobucci\JWT\Signer\Rsa\Sha256(),
            default => throw new \InvalidArgumentException("Algoritmo no soportado: {$algorithm}")
        };
        
        $this->keyId = $keyId;
        $this->algorithm = $algorithm;
    }
    
    // createAccessToken() - usa $this->signer dinámicamente
    // validateAccessToken() - usa $this->signer dinámicamente
    // JWKS endpoint genera keys según $this->algorithm
}
```

### Paso 3: Actualizar `ModuleBootstrap.php` (0.5h)
```php
// En ModuleBootstrap.php - registro de JwtTokenService
$container->set(TokenFactory::class, function (ContainerInterface $c) {
    $config = $c->get('config');
    $jwtConfig = $config['jwt'];
    
    // Feature flag para transición gradual
    $algorithm = $jwtConfig['algorithm'] ?? 'RS256';  
    
    if ($algorithm === 'ES256') {
        return new JwtTokenService(
            $jwtConfig['private_key_ec'] ?? $_ENV['JWT_PRIVATE_KEY_EC'],
            $jwtConfig['public_key_ec'] ?? $_ENV['JWT_PUBLIC_KEY_EC'],
            $jwtConfig['issuer'] ?? 'his-tenants-auth',
            $jwtConfig['audience'] ?? 'his-api',
            (int)($jwtConfig['access_ttl'] ?? 900),
            $jwtConfig['key_id'] ?? 'ec-key-1',
            'ES256'
        );
    }
    
    // RS256 (actual - default)
    return new JwtTokenService(
        $jwtConfig['private_key'] ?? $_ENV['JWT_PRIVATE_KEY'],
        $jwtConfig['public_key'] ?? $_ENV['JWT_PUBLIC_KEY'],
        $jwtConfig['issuer'] ?? 'his-tenants-auth',
        $jwtConfig['audience'] ?? 'his-api',
        (int)($jwtConfig['access_ttl'] ?? 900),
        $jwtConfig['key_id'] ?? 'key-1',
        'RS256'
    );
});
```
### Paso 4: Variables de Entorno (Configuración)
```env
# .env - Soporte dual durante transición
JWT_ALGORITHM=RS256                    # Cambiar a ES256 para activar
JWT_PRIVATE_KEY=-----BEGIN PRIVATE KEY-----...
JWT_PUBLIC_KEY=-----BEGIN PUBLIC KEY-----...

# Nuevas para ES256 (opcionales, solo si JWT_ALGORITHM=ES256)
JWT_PRIVATE_KEY_EC=-----BEGIN EC PRIVATE KEY-----...
JWT_PUBLIC_KEY_EC=-----BEGIN PUBLIC KEY-----...
JWT_KEY_ID=ec-key-1
```
### Paso 5: JWKS Endpoint Dual (Transición Zero-Downtime)
```php
// En JwtTokenService::getJwks() - Soporta múltiples keys simultáneamente
public function getJwks(): JwksResponse
{
    $keys = [];
    
    // Key actual (según algoritmo configurado)
    $keys[] = $this->buildJwkKey($this->publicKey, $this->keyId, $this->algorithm);
    
    // Key anterior (para overlap de 1h durante rotación)
    if ($this->previousPublicKey) {
        $keys[] = $this->buildJwkKey(
            $this->previousPublicKey, 
            $this->previousKeyId, 
            $this->previousAlgorithm
        );
    }
    
    return new JwksResponse($keys, $this->rotatedAt);
}
// Ejemplo JWKS ES256:
{
  "keys": [
    {
      "kty": "EC",
      "crv": "P-256",
      "alg": "ES256",
      "use": "sig",
      "kid": "ec-key-1",
      "x": "MKBCTNIcKUSDii11ySs3526iDZ8AiTo7Tu6KPAqv7D4",
      "y": "4Etl6SRW2YiLUrN5vfvVHuhp7x8PxltmWWlbbM4IFyM"
    },
    {
      "kty": "RSA",
      "alg": "RS256",
      "use": "sig",
      "kid": "key-1",
      "n": "0vx7agoebGcQSuuPiLJXZptN9nndrQmbXEps2aiAFbWhM78LhWx4cbbfAAtVT86zwu1RK7aPFFxuhDR1L6tSoc_BJECPebWKRXjBZCiFV4n3oknjhMstn64tZ_2W-5JsGY4Hc5n9yBXArwl93nq7RG5SX2p"
      "e": "AQAB"
    }
  ]
}
```
### Paso 6: Tests Actualizados (1.5h)
```php
// tests/Unit/Tenants/Domain/Services/TokenFactoryTest.php
// Actualizar expectations para ES256

public function testCreateAccessToken_ES256(): void
{
    $factory = new JwtTokenService(
        $this->ecPrivateKey,
        $this->ecPublicKey,
        'test-issuer',
        'test-audience',
        900,
        'ec-key-1',
        'ES256'
    );
    $accessToken = $factory->createAccessToken($user, $tenant, $sessionId, $device);
    $this->assertNotEmpty($accessToken->token());
    
    // Validar header del JWT
    $parts = explode('.', $accessToken->token());
    $header = json_decode(base64_decode($parts[0]), true);
    
    $this->assertEquals('ES256', $header['alg']);
    $this->assertEquals('JWT', $header['typ']);
    $this->assertEquals('ec-key-1', $header['kid']);
}

public function testValidateValidToken_ES256(): void
{
    $factory = new JwtTokenService(
        $this->ecPrivateKey,
        $this->ecPublicKey,
        'test-issuer',
        'test-audience',
        900,
        'ec-key-1',
        'ES256'
    );
    
    $accessToken = $factory->createAccessToken($user, $tenant, $sessionId, $device);
    $claims = $factory->validateAccessToken($accessToken->token());
    
    $this->assertEquals($user->id()->value(), $claims->subject());
}

// Nuevo test: tests/Unit/Tenants/Infrastructure/Security/JwtTokenServiceTest.php
class JwtTokenServiceTest extends TestCase
{
    public function testES256SigningAndValidation(): void
    {
        $service = new JwtTokenService(
            $this->ecPrivateKey,
            $this->ecPublicKey,
            'test-issuer',
            'test-audience',
            900,
            'ec-key-1',
            'ES256'
        );
        
        // Test signing
        $token = $service->createAccessToken($user, $tenant, $sessionId, $device);
        $this->assertStringContainsString('.', $token->token()); // JWT format
        
        // Test validation
        $claims = $service->validateAccessToken($token->token());
        $this->assertEquals($user->id()->value(), $claims->subject());
    }
    
    public function testJwksEndpointReturnsECKey(): void
    {
        $service = new JwtTokenService(/* ... ES256 config ... */);
        $jwks = $service->getJwks();
        
        $this->assertCount(1, $jwks->keys());
        $key = $jwks->keys()[0];
        $this->assertEquals('EC', $key->kty());
        $this->assertEquals('P-256', $key->crv());
        $this->assertEquals('ES256', $key->alg());
    }
    
    public function testDualAlgorithmTransition(): void
    {
        // Simular transición: RS256 key + ES256 key en JWKS
        $service = new JwtTokenService(/* RS256 config */);
        $service->setPreviousKey($ecPublicKey, 'ec-key-1', 'ES256');
        
        $jwks = $service->getJwks();
        $this->assertCount(2, $jwks->keys());
        
        // Validar que ambos algoritmos presentes
        $algs = array_column($jwks->keys(), 'alg');
        $this->assertContains('RS256', $algs);
        $this->assertContains('ES256', $algs);
    }
}
```

---

## 4. Estrategia de Despliegue Zero-Downtime
### 4.1 Fase de Transición (1-2 horas)
```mermaid
timeline
    title Transición RS256 → ES256 (Zero-Downtime)
    
    T+0min  : Deploy JwtTokenService con soporte dual (RS256 activo, ES256 listo)
    T+5min  : JWKS endpoint expone AMBAS keys (RS256 key-1 + ES256 ec-key-1)
    T+10min : Canary 5% tráfico → ES256 (feature flag JWT_ALGORITHM=ES256)
    T+30min : Monitorear métricas: latency, error_rate, token_size
    T+60min : Si OK → 50% tráfico ES256
    T+90min : Si OK → 100% tráfico ES256
    T+120min: Deprecar RS256 key (remover de JWKS tras 1h overlap)
```

### 4.2 Métricas de Validación (Dashboards Grafana)
| Métrica                    | Objetivo RS256 | Objetivo ES256 | Alerta si |
|----------------------------|----------------|----------------|-----------|
| `jwt_sign_duration_ms`     | p99 < 5ms      | p99 < 2ms      | > 10ms    |
| `jwt_validate_duration_ms` | p99 < 3ms      | p99 < 1ms      | > 5ms     |
| `jwt_token_size_bytes`     | ~800-1200      | ~400-600       | > 1000    |
| `auth_login_p95_ms`        | < 300ms        | < 250ms        | > 350ms   |
| `auth_error_rate`          | < 0.1%         | < 0.1%         | > 0.5%    |

### 4.3 Rollback Inmediato (Feature Flag)
```bash
# Rollback instantáneo (sin redeploy)
JWT_ALGORITHM=RS256  # En .env o ConfigMap K8s
kubectl rollout restart deployment/auth-service  # Solo si cache de config
# O: feature flag en Consul/Etcd para cambio en caliente
```

---

## 5. Riesgos y Mitigaciones
| Riesgo                                          | Probabilidad | Impacto | Mitigación                                                                           |
|-------------------------------------------------|--------------|---------|--------------------------------------------------------------------------------------|
| **Librería no soporta ES256 correctamente**     | Media        | Alto    | lcobucci/jwt 5.x soporta ES256 nativamente desde 2021; test exhaustivo pre-deploy    |
| **Clients no validan ES256**                    | Baja         | Medio   | JWKS expone ambos `kid` durante overlap; clients validan contra JWKS dinámicamente   |
| **Tokens ES256 rechazados por API Gateway**     | Media        | Crítico | Gateway valida contra JWKS (no algoritmo hardcodeado); test integración Gateway+Auth |
| **Claves EC comprometidas**                     | Baja         | Crítico | Rotación 24h + HSM/Vault + monitoring anomalías                                      |
| **Performance degradado en validación**         | Baja         | Medio   | Benchmark pre-deploy: ES256 2-3x más rápido que RS256 en lcobucci/jwt                |
| **Incompatibilidad librerías cliente antiguas** | Media        | Medio   | Documentar versión mínima cliente; comunicar 30 días antes                           |

---

## 6. Validación Post-Cambio
### Checklist Post-Deploy
- [ ] JWKS endpoint retorna keys ES256 (`kty=EC`, `crv=P-256`, `alg=ES256`)
- [ ] Login genera tokens ES256 (`alg=ES256` en header JWT)
- [ ] Validación tokens ES256 exitosa (p99 < 2ms)
- [ ] Refresh rotation funciona con ES256
- [ ] Blocklist/Revocación funciona con tokens ES256
- [ ] MFA challenge/verification intacto
- [ ] JWKS rotation 24h funciona con ES256
- [ ] Métricas Grafana: latency reducida 40-50%, token size reducido 40%
- [ ] Sin errores 401/500 en logs Auth Service
- [ ] Clinical Service valida tokens ES256 correctamente (integración)
- [ ] Governance Service auditoría intacta

### Pruebas de Regresión Automatizadas
```bash
# Suite completa (< 30s)
./vendor/bin/phpunit tests/Unit/Tenants/Domain/Services/TokenFactoryTest.php
./vendor/bin/phpunit tests/Unit/Tenants/Domain/Services/RefreshTokenRotatorTest.php
./vendor/bin/phpunit tests/Feature/Tenants/LoginMfaFeatureTest.php
./vendor/bin/phpunit tests/Feature/Tenants/RefreshTokenFeatureTest.php
./vendor/bin/phpunit tests/Feature/Tenants/RevokeSessionFeatureTest.php

# Tests de integración (requiere staging)
./vendor/bin/phpunit tests/Feature/Tenants/ --filter "ES256"
```
## 7. Conclusión y Recomendación
### Veredicto: **APROBADO PARA IMPLEMENTACIÓN**
| Criterio                   | Evaluación                                        |
|----------------------------|---------------------------------------------------|
| **Factibilidad técnica**   | Alta - Arquitectura soporta cambio aislado        |
| **Riesgo operativo**       | Bajo - Zero-downtime via JWKS dual + feature flag |
| **Esfuerzo**               | Bajo - 4-7 horas vs 2-3 días monolito             |
| **Rollback**               | Inmediato - Feature flag `JWT_ALGORITHM`          |
| **Beneficio medible**      | Tokens 40% menores, validación 2-3x más rápida    |
| **Alineación estratégica** | NIST post-quantum readiness, mobile-first         |
