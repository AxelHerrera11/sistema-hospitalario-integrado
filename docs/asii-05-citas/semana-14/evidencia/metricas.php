<?php

declare(strict_types=1);
/*
 * Metricas SQA medidas sobre el Micro-HIS fusionado en develop (Semana 3).
 * Ejecutar: php evidencia/metricas.php [ruta-micro-his]   (por defecto ../semana-03/micro-his)
 */

$raiz = rtrim($argv[1] ?? __DIR__ . '/../../semana-03/micro-his', '/\\');

/** Lineas de codigo sin vacias ni comentarios. */
$loc = static function (string $archivo): int {
    $n = 0;
    foreach (file($archivo, FILE_IGNORE_NEW_LINES) as $l) {
        $l = trim($l);
        $n += ($l === '' || str_starts_with($l, '//') || str_starts_with($l, '/*') || str_starts_with($l, '*')) ? 0 : 1;
    }
    return $n;
};

$porCapa = [];
$violaciones = [];
foreach (glob($raiz . '/src/*', GLOB_ONLYDIR) as $capa) {
    $nombre = basename($capa);
    $porCapa[$nombre] = 0;
    foreach (glob($capa . '/*.php') as $archivo) {
        $porCapa[$nombre] += $loc($archivo);
        if ($nombre === 'Domain' && preg_match('/\bPDO\b|\$_(GET|POST|SERVER)|header\(/', (string) file_get_contents($archivo))) {
            $violaciones[] = basename($archivo);
        }
    }
}
$totalSrc = array_sum($porCapa);
$runner = $raiz . '/tests/ejecutar_pruebas.php';
$salida = [];
exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($runner), $salida, $codigo);
preg_match('/Resumen: (\d+) pruebas, (\d+) fallidas/', implode("\n", $salida), $r);
[$pruebas, $fallidas] = [(int) ($r[1] ?? 0), (int) ($r[2] ?? 0)];

foreach ($porCapa as $capa => $n) {
    printf("  LOC %-12s %4d\n", $capa, $n);
}
printf("  LOC total src     %4d\n\n", $totalSrc);
printf("M-01 Pruebas que pasan:        %d/%d (%.0f %%)\n", $pruebas - $fallidas, $pruebas, $pruebas ? 100 * ($pruebas - $fallidas) / $pruebas : 0);
printf("M-02 Pruebas por 100 LOC:      %.2f\n", $totalSrc ? 100 * $pruebas / $totalSrc : 0);
printf("M-03 Violaciones de capa:      %d %s\n", count($violaciones), $violaciones ? '(' . implode(', ', $violaciones) . ')' : '');
exit($fallidas === 0 && $violaciones === [] ? 0 : 1);
