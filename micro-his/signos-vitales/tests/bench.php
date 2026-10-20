<?php

declare(strict_types=1);

use MicroHis\SignosVitales\Application\EpisodeContext;
use MicroHis\SignosVitales\Persistence\InMemoryCareEpisodeReader;
use MicroHis\SignosVitales\Persistence\InMemoryVitalSignRepository;
use MicroHis\SignosVitales\Persistence\PdoCareEpisodeReader;
use MicroHis\SignosVitales\Persistence\PdoVitalSignRepository;
use MicroHis\SignosVitales\Persistence\QueuedAlertNotifier;
use MicroHis\SignosVitales\Presentation\Request;

require __DIR__ . '/../app.php';

/**
 * Medición del costo del flujo completo (Router -> caso de uso -> Repository) para sustentar la decisión de frontera.
 * Es una medición del prototipo en proceso, sin red ni PostgreSQL: sirve para comparar contra un presupuesto, no como carga de producción.
 * Uso: php tests/bench.php [peticiones]
 */
$n = (int) ($argv[1] ?? 5000);
$headers = ['X-User-ID' => 'dra.lopez'];
$body = ['heart_rate' => 138, 'oxygen_saturation' => 94, 'measured_at' => '2026-10-14 08:30:00'];

$pdo = new PDO('sqlite::memory:');
$pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
$pdo->exec("INSERT INTO care_episodes VALUES ('ep1', 'open', '1980-05-10')");
$pdo->exec("INSERT INTO care_assignments VALUES ('ep1', 'dra.lopez', 'active')");
$adapters = [
    'InMemory' => signos_vitales_router(new InMemoryCareEpisodeReader(['ep1' => ['dra.lopez' => new EpisodeContext(true, 45)]]), new InMemoryVitalSignRepository(), new QueuedAlertNotifier()),
    'PDO (SQLite en memoria)' => signos_vitales_router(new PdoCareEpisodeReader($pdo), new PdoVitalSignRepository($pdo), new QueuedAlertNotifier()),
];

foreach ($adapters as $name => $router) {
    $times = [];
    for ($i = 0; $i < $n; $i++) {
        $start = hrtime(true);
        $response = $router->dispatch(new Request('POST', '/episodes/ep1/vital-signs', $headers, $body));
        $times[] = (hrtime(true) - $start) / 1e6;
        $response['status'] === 201 || exit("FALLO: estado {$response['status']}" . PHP_EOL);
    }
    sort($times);
    printf("%s: %d peticiones, p50 %.3f ms, p95 %.3f ms, máx %.3f ms, %.0f peticiones/s%s", $name, $n, $times[intdiv($n, 2)], $times[(int) ($n * 0.95)], end($times), $n / (array_sum($times) / 1000), PHP_EOL);
}
