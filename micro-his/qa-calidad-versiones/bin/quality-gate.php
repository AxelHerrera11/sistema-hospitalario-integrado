<?php

declare(strict_types=1);

/**
 * Evalúa el quality gate de una ejecución de CI a partir de los reportes JUnit.
 *
 *   php micro-his/qa-calidad-versiones/bin/quality-gate.php \
 *       --evidence-root=qa-evidence --out-dir=qa-evidence/gate \
 *       --sha=$GITHUB_SHA --release=0.0.0-ci.$GITHUB_RUN_NUMBER [--environment=ci] \
 *       [--manifest=...] [--policy=...] [--db=qa-evidence/gate/qa-runs.sqlite]
 *
 * Códigos de salida: 0 aprobado, 1 rechazado, 2 bloqueado, 3 error de configuración.
 * La cuenta de CI es una identidad de servicio ficticia dentro del hospital de pruebas.
 */
use MicroHis\QaCalidad\Application\Actor;
use MicroHis\QaCalidad\Application\QaPermissions;
use MicroHis\QaCalidad\Ci\EvidenceCollector;
use MicroHis\QaCalidad\Ci\GateReportWriter;
use MicroHis\QaCalidad\Ci\GateRunner;
use MicroHis\QaCalidad\Ci\JUnitReportReader;
use MicroHis\QaCalidad\Ci\ModuleFactory;

require __DIR__.'/../bootstrap.php';

$base = dirname(__DIR__);
$opts = getopt('', ['manifest:', 'policy:', 'evidence-root:', 'out-dir:', 'sha:', 'release:', 'environment:', 'db:']);
$get = static fn (string $key, ?string $default = null): ?string => isset($opts[$key]) && is_string($opts[$key]) ? $opts[$key] : $default;

try {
    $manifestFile = $get('manifest', $base.'/config/ci-manifest.json');
    $manifest = json_decode((string) @file_get_contents($manifestFile), true);
    if (! is_array($manifest) || ! is_array($manifest['suites'] ?? null) || ! is_string($manifest['tenant_id'] ?? null)) {
        throw new RuntimeException("Manifiesto inválido: {$manifestFile}");
    }
    $root = $get('evidence-root') ?? throw new RuntimeException('Falta --evidence-root.');
    $out = $get('out-dir') ?? throw new RuntimeException('Falta --out-dir.');
    is_dir($out) || mkdir($out, 0775, true);

    $ci = new Actor((string) ($manifest['ci_actor'] ?? 'ci-bot'), $manifest['tenant_id']);
    $grants = [$ci->userId.'@'.$ci->tenantId => [QaPermissions::VIEW, QaPermissions::REGISTER, QaPermissions::EVALUATE]];
    $module = ModuleFactory::build(
        ModuleFactory::repository($get('db')),
        $grants,
        ModuleFactory::policy($get('policy', $base.'/config/quality-gate.json')),
    );
    $runner = new GateRunner($module, new JUnitReportReader, new EvidenceCollector($root));
    $result = $runner->run(
        $ci,
        $manifest,
        $get('release', '0.0.0-local'),
        $get('sha') ?? throw new RuntimeException('Falta --sha (commit de 40 caracteres).'),
        $get('environment', 'ci'),
        'ci-'.bin2hex(random_bytes(6)),
    );
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR DE CONFIGURACIÓN DEL GATE: '.$e::class.': '.$e->getMessage().PHP_EOL);
    exit(3);
}

$writer = new GateReportWriter;
$markdown = $writer->markdown($result['run'], $result['decision'], $result['notes']);
file_put_contents($out.'/gate-decision.json', $writer->json($result['run'], $result['decision'], $result['notes']));
file_put_contents($out.'/gate-summary.md', $markdown);
$summary = getenv('GITHUB_STEP_SUMMARY');
if (is_string($summary) && $summary !== '') {
    file_put_contents($summary, $markdown, FILE_APPEND);
}
echo $markdown;

exit(match ($result['decision']->verdict->value) {
    'approved' => 0, 'rejected' => 1, default => 2
});
