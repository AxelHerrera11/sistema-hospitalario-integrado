<?php

declare(strict_types=1);

/**
 * Reglas de arquitectura verificables (Semanas 3, 4 y 7):
 *  - Domain no depende de ninguna otra capa ni de PDO.
 *  - Application solo depende de Domain.
 *  - Presentation no contiene SQL ni PDO; el controlador no tiene reglas de negocio.
 *  - Ningún archivo del módulo supera 400 líneas.
 */
require __DIR__.'/harness.php';

$root = dirname(__DIR__);
$files = static function (string $dir) use ($root): array {
    $out = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/{$dir}", FilesystemIterator::SKIP_DOTS)) as $f) {
        if (in_array($f->getExtension(), ['php', 'js', 'css', 'html', 'json', 'sql', 'mjs', 'md'], true)) {
            $out[str_replace('\\', '/', substr($f->getPathname(), strlen($root) + 1))] = (string) file_get_contents($f->getPathname());
        }
    }

    return $out;
};
$imports = static fn (string $code): array => preg_match_all('/^use (MicroHis\\\\[A-Za-z\\\\]+)/m', $code, $m) ? $m[1] : [];
$violations = static function (array $sources, callable $bad) use ($imports): array {
    $found = [];
    foreach ($sources as $file => $code) {
        foreach ($imports($code) as $import) {
            $bad($import) && $found[] = "{$file} → {$import}";
        }
    }

    return $found;
};

section('Dependencias entre capas');
$domain = $violations($files('src/Domain'), static fn (string $i): bool => ! str_starts_with($i, 'MicroHis\\QaCalidad\\Domain\\'));
check('AR01', 'Domain solo importa Domain'.($domain ? ': '.implode('; ', $domain) : ''), $domain === []);
check('AR02', 'Domain no usa PDO ni SQL', ! preg_match('/\\\\PDO|SELECT |INSERT /', implode("\n", $files('src/Domain'))));
$app = $violations($files('src/Application'), static fn (string $i): bool => ! preg_match('/^MicroHis\\\\QaCalidad\\\\(Domain|Application)\\\\/', $i));
check('AR03', 'Application solo importa Domain y Application'.($app ? ': '.implode('; ', $app) : ''), $app === []);
$presentation = implode("\n", $files('src/Presentation'));
check('AR04', 'Presentation sin SQL ni PDO', ! preg_match('/\\\\PDO|SELECT |INSERT |UPDATE /i', $presentation));
$pres = $violations($files('src/Presentation'), static fn (string $i): bool => str_contains($i, '\\Persistence\\') || str_contains($i, '\\Ci\\'));
check('AR05', 'Presentation no importa Persistence ni Ci', $pres === []);
$controller = (string) file_get_contents("{$root}/src/Presentation/QaController.php");
check('AR06', 'El controlador no decide veredictos ni valida reglas (sin GateVerdict/QualityGate/InvalidQaInput)', ! preg_match('/\bGateVerdict\b|\bQualityGate\b|\bInvalidQaInput\b|throw new/', $controller));
$gov = $violations($files('src'), static fn (string $i): bool => str_starts_with($i, 'MicroHis\\Gobernanza\\'));
$allowed = array_filter($gov, static fn (string $v): bool => ! str_starts_with($v, 'src/Integration/') && ! str_starts_with($v, 'src/Ci/'));
check('AR07', 'Solo Integration/ y Ci/ conocen a Gobernanza (contrato público)', $allowed === []);

section('Tamaño de archivos');
$big = [];
foreach ($files('') as $file => $code) {
    $lines = substr_count($code, "\n") + 1;
    $lines > 400 && $big[] = "{$file} ({$lines})";
}
check('AR08', 'Ningún archivo del módulo supera 400 líneas'.($big ? ': '.implode(', ', $big) : ''), $big === []);

summary('architecture');
