<?php

declare(strict_types=1);

// Mide el costo EN PROCESO de la consulta y de una denegación (Router → caso de uso → PDO/SQLite → auditoría
// en memoria), para comparar con el costo que agregaría un salto de red a un microservicio.
//   php docs/expedientes-medicos/semana-05/herramientas/medir-consulta.php [repeticiones]
require __DIR__ . '/../../../../micro-his/expediente-medico/tests/harness.php';

$n = max(10, (int) ($argv[1] ?? 500));
['router' => $router] = make_app('pdo');
$medir = static function (string $actor) use ($router, $n): array {
    $tiempos = [];
    for ($i = 0; $i < $n; $i++) {
        $t = hrtime(true);
        call($router, 'GET', 'P-0001', $actor);
        $tiempos[] = (hrtime(true) - $t) / 1e6;
    }
    sort($tiempos);

    return ['p50' => $tiempos[intdiv($n, 2)], 'p95' => $tiempos[(int) floor($n * 0.95)], 'max' => end($tiempos)];
};
printf("PHP %s, %d repeticiones, SQLite en memoria, auditoría en memoria%s", PHP_VERSION, $n, PHP_EOL);
foreach (['consulta 200 (médica tratante)' => DRA_LOPEZ, 'denegación 403 (enfermería sin vínculo)' => ENF_RUIZ] as $caso => $actor) {
    $r = $medir($actor);
    printf("%-42s p50 %.3f ms  p95 %.3f ms  máx %.3f ms%s", $caso, $r['p50'], $r['p95'], $r['max'], PHP_EOL);
}
