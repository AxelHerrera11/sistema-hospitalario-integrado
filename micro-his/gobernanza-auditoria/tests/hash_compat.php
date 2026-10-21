<?php

declare(strict_types=1);

use MicroHis\Gobernanza\Persistence\AuditChainHasher;

/**
 * Regresión de compatibilidad (Semana 7): AuditChainHasher debe producir el
 * mismo hash que AuditLogRepository::eventHash() de Tenants, para que ambos
 * escritores mantengan una única cadena verificable en audit_log.
 *
 * El archivo de Tenants no está en esta rama; se exporta sin cambiar de rama:
 *   git show origin/feature/asii-25-semana-16-tenants-auditoria:app/Modules/Tenants/Domain/Audit/AuditLogRepositoryInterface.php > <dir>/AuditLogRepositoryInterface.php
 *   git show origin/feature/asii-25-semana-16-tenants-auditoria:app/Modules/Tenants/Infrastructure/Audit/AuditLogRepository.php > <dir>/AuditLogRepository.php
 *   TENANTS_AUDIT_DIR=<dir> php tests/hash_compat.php
 */
$dir = getenv('TENANTS_AUDIT_DIR') ?: '';
if ($dir === '' || !is_file($dir . '/AuditLogRepository.php')) {
    echo 'OMITIDA: defina TENANTS_AUDIT_DIR con AuditLogRepository.php de la rama de Tenants (ver encabezado).' . PHP_EOL;
    exit(0);
}
require __DIR__ . '/harness.php';
require_once $dir . '/AuditLogRepositoryInterface.php';
require_once $dir . '/AuditLogRepository.php';

$reference = new \App\Modules\Tenants\Infrastructure\Audit\AuditLogRepository();
$eventHash = new ReflectionMethod($reference, 'eventHash');
$canonicalize = new ReflectionMethod($reference, 'canonicalize');
$ours = new AuditChainHasher();

$cases = [
    'denegación RBAC' => [AuditChainHasher::GENESIS, '5f0c8a52-1d3e-4b6a-9c7d-2e1f0a9b8c7d', '2026-10-15T20:00:00.123456Z', 'rbac.access_denied', MED, H1, ['user_id' => MED, 'permission' => 'auditoria.ver', 'reason' => 'permiso_faltante', 'trace_id' => 'trace-aaa111'], 'trace-aaa111', null],
    'detalle anidado y unicode' => [str_repeat('a', 64), '9b2f6c1e-3f4a-4c5d-8e6f-7a8b9c0d1e2f', '2026-10-16T01:02:03.000000Z', 'gobernanza.auditoria_consultada', ADMIN, H2, ['z' => 1, 'context' => ['página' => 2, 'b' => [3, 1]], 'a' => 1.0], 'req-1', 'success'],
];

section('AuditChainHasher vs AuditLogRepository (Tenants @ ' . (getenv('TENANTS_AUDIT_REF') ?: 'ref no indicada') . ')');
$n = 0;
foreach ($cases as $name => $args) {
    $n++;
    $detail = $canonicalize->invoke($reference, $args[6]);
    $expected = $eventHash->invoke($reference, $args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $detail, $args[7], $args[8]);
    check("TC-R-0{$n}", "mismo hash SHA-256 que Tenants: {$name}", $ours->hash(...$args) === $expected);
}
check('TC-R-03', 'misma canonicalización del detalle (ksort recursivo, listas intactas)', $ours->canonicalize($cases['detalle anidado y unicode'][6]) === $canonicalize->invoke($reference, $cases['detalle anidado y unicode'][6]));

summary('hash_compat');
