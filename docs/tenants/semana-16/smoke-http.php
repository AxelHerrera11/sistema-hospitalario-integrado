<?php
// Smoke HTTP contra el micro-HIS de Tenants servido con php -S (uso: php ten-smoke.php <repo> <base-url> <tenant_id> <totp_secret>).
declare(strict_types=1);
require $argv[1] . '/vendor/autoload.php';

use App\Modules\Tenants\Infrastructure\Security\TotpVerifier;

[, , $base, $tenant, $secret] = $argv;
const CLAVE = 'Clave-Segura-2026!';

function http(string $method, string $path, array $body = [], ?string $tenant = null, ?string $token = null): array
{
    global $base;
    $h = ['Content-Type: application/json'];
    $tenant && $h[] = "X-Tenant-ID: $tenant";
    $token && $h[] = "Authorization: Bearer $token";
    $c = curl_init($base . $path);
    curl_setopt_array($c, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $h, CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true]);
    $body && curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($body));
    $raw = (string) curl_exec($c);
    $size = curl_getinfo($c, CURLINFO_HEADER_SIZE);
    $status = curl_getinfo($c, CURLINFO_RESPONSE_CODE);

    return ['status' => $status, 'headers' => substr($raw, 0, $size), 'body' => json_decode(substr($raw, $size), true) ?? []];
}

function show(string $label, array $r, array $keys = []): void
{
    $b = $keys ? array_intersect_key($r['body'], array_flip($keys)) : $r['body'];
    array_walk_recursive($b, function (&$v, $k) { if (is_string($v) && in_array($k, ['access_token', 'refresh_token', 'challenge_id'], true)) { $v = substr($v, 0, 8) . '…(' . strlen($v) . ')'; } });
    echo "\$ $label\nHTTP {$r['status']} " . json_encode($b, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
}

$totp = fn () => (new TotpVerifier())->codeAt($secret, intdiv(time(), 30));

$ch = http('POST', '/auth/login', ['email' => 'admin@demo.test', 'password' => CLAVE], $tenant);
show('POST /auth/login admin (rol con MFA obligatorio)', $ch, ['challenge_id', 'mfa_required']);
show('POST /auth/mfa con código incorrecto', http('POST', '/auth/mfa', ['challenge_id' => $ch['body']['challenge_id'], 'code' => '000000']));
$adm = http('POST', '/auth/mfa', ['challenge_id' => $ch['body']['challenge_id'], 'code' => $totp()]);
show('POST /auth/mfa con TOTP válido', $adm, ['access_token', 'refresh_token', 'expires_in', 'user']);
show('POST /auth/mfa repitiendo el mismo desafío', http('POST', '/auth/mfa', ['challenge_id' => $ch['body']['challenge_id'], 'code' => $totp()]));
$admin = $adm['body']['access_token'];

show('POST /admin/users medico (contraseña débil)', http('POST', '/admin/users', ['email' => 'x@demo.test', 'name' => 'X', 'password' => 'debil', 'roles' => ['medico']], null, $admin));
show('POST /admin/users medico@demo.test', http('POST', '/admin/users', ['email' => 'medico@demo.test', 'name' => 'Médico Demo', 'password' => CLAVE, 'roles' => ['medico']], null, $admin), ['user_id', 'roles']);

$med = http('POST', '/auth/login', ['email' => 'medico@demo.test', 'password' => CLAVE], $tenant);
show('POST /auth/login medico', $med, ['access_token', 'refresh_token', 'user']);
show('GET /auth/me medico', http('GET', '/auth/me', [], null, $med['body']['access_token']), ['tenant_id', 'roles']);
show('POST /admin/users con token de medico', http('POST', '/admin/users', ['email' => 'z@demo.test', 'name' => 'Z', 'password' => CLAVE, 'roles' => ['medico']], null, $med['body']['access_token']));
$rot = http('POST', '/auth/refresh', ['refresh_token' => $med['body']['refresh_token']]);
show('POST /auth/refresh (rotación)', $rot, ['access_token', 'refresh_token']);
show('POST /auth/refresh reutilizando el refresh anterior', http('POST', '/auth/refresh', ['refresh_token' => $med['body']['refresh_token']]));
show('POST /auth/refresh con el refresh nuevo tras la reutilización', http('POST', '/auth/refresh', ['refresh_token' => $rot['body']['refresh_token']]));
show('GET /auth/me sin token', http('GET', '/auth/me'));
show('POST /auth/login con X-Tenant-ID inexistente', http('POST', '/auth/login', ['email' => 'medico@demo.test', 'password' => CLAVE], '00000000-0000-4000-8000-000000000000'));
for ($i = 1; $i <= 5; $i++) {
    $bad = http('POST', '/auth/login', ['email' => 'medico@demo.test', 'password' => 'incorrecta'], $tenant);
}
show('5 intentos fallidos de medico; el 5.º', $bad);
show('POST /auth/login medico con la clave correcta después', http('POST', '/auth/login', ['email' => 'medico@demo.test', 'password' => CLAVE], $tenant));
$h = http('GET', '/auth/me', [], null, $admin)['headers'];
echo "\$ Cabeceras de seguridad de GET /auth/me\n" . implode("\n", array_filter(array_map('trim', explode("\n", $h)), fn ($l) => preg_match('/^(cache-control|x-content-type-options|content-type):/i', $l))) . "\n";
