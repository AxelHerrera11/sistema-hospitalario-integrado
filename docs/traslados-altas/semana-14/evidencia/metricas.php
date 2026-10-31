<?php

declare(strict_types=1);

// Mide las métricas SQA de ASII-08 que se pueden calcular desde el repositorio, sin red ni base de datos.
// Uso (desde la raíz del repositorio): php docs/traslados-altas/semana-14/evidencia/metricas.php [rama]
$rama = $argv[1] ?? 'origin/develop';
$raiz = dirname(__DIR__, 4);
$modulo = "{$raiz}/micro-his/traslados-altas";
$medir = static fn (string $comando): string => trim((string) shell_exec($comando));

// M1: verificaciones automáticas sin base de datos (tests/run.php).
$salida = $medir('php ' . escapeshellarg("{$modulo}/tests/run.php") . ' 2>&1');
preg_match('/(OK|ERROR): (\d+)(?: de (\d+))? verificaciones/', $salida, $m);
$m1 = ($m[1] ?? '') === 'OK' ? "{$m[2]}/{$m[2]}" : (isset($m[3]) ? ($m[3] - $m[2]) . "/{$m[3]}" : 'sin datos');

// M2: tamaño de cada PR de ASII-08 integrado en develop (adiciones + eliminaciones del merge contra su primer padre; ~1 y no ^1, que cmd de Windows trata como escape).
$merges = array_filter(explode("\n", $medir("git -C " . escapeshellarg($raiz) . " log --first-parent --format=%H%x09%s {$rama}")),
    static fn (string $l): bool => preg_match('/Merge pull request #\d+ from \S*asii-08/', $l) === 1);
$tamanos = [];
foreach ($merges as $linea) {
    [$hash, $asunto] = explode("\t", $linea, 2);
    preg_match('/#(\d+)/', $asunto, $pr);
    $numstat = $medir("git -C " . escapeshellarg($raiz) . " diff --numstat {$hash}~1 {$hash}");
    $tamanos[$pr[1]] = array_sum(array_map(static fn (string $f): int => array_sum(array_map('intval', array_slice(preg_split('/\s+/', $f), 0, 2))),
        array_filter(explode("\n", $numstat))));
}
$m2 = count($tamanos) . ' PR; máximo ' . max($tamanos ?: [0]) . ' líneas; ' . count(array_filter($tamanos, static fn (int $t): bool => $t > 400)) . ' superan 400';

// M3: RF-TA-01..10 citados por al menos un archivo de pruebas.
$citados = [];
foreach (glob("{$modulo}/tests/*.{php,mjs}", GLOB_BRACE) as $archivo) {
    preg_match_all('/RF-TA-(\d{2})/', (string) file_get_contents($archivo), $rf);
    $citados += array_flip($rf[1]);
}
$m3 = count(array_intersect(array_keys($citados), array_map(static fn (int $n): string => sprintf('%02d', $n), range(1, 10)))) . '/10';

// M4: dependencias prohibidas entre capas (las mismas reglas que tests/arquitectura.php).
$reglas = ['Domain' => '/MicroHis.TrasladosAltas.(Application|Persistence|Presentation)|\bPDO\b/',
    'Application' => '/MicroHis.TrasladosAltas.(Persistence|Presentation)|\bPDO\b/',
    'Presentation' => '/MicroHis.TrasladosAltas.Persistence|\bPDO\b|\b(SELECT|INSERT|UPDATE|DELETE)\b/'];
$violaciones = 0;
foreach ($reglas as $capa => $patron) {
    foreach (glob("{$modulo}/src/{$capa}/*.php") as $archivo) {
        $violaciones += preg_match($patron, (string) file_get_contents($archivo));
    }
}

printf("M1 verificaciones en verde: %s\nM2 tamaño de PR: %s\nM3 RF con prueba: %s\nM4 dependencias prohibidas: %d\n", $m1, $m2, $m3, $violaciones);
