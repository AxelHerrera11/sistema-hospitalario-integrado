<?php

declare(strict_types=1);

use MicroHis\Occupancy\Application\BedSnapshotReader;
use MicroHis\Occupancy\Application\ConsultOccupancy;
use MicroHis\Occupancy\Application\PersistenceFailure;
use MicroHis\Occupancy\Domain\BedSnapshot;
use MicroHis\Occupancy\Domain\OccupancyCalculator;
use MicroHis\Occupancy\Presentation\OccupancyController;

require __DIR__ . '/../bootstrap.php';

$failures = 0;
function check(string $label, bool $condition): void
{
    global $failures;
    echo ($condition ? 'PASS' : 'FAIL') . "  {$label}\n";
    $failures += $condition ? 0 : 1;
}

$cutoff = new DateTimeImmutable('2026-10-13T10:00:00-06:00');
$reader = new class implements BedSnapshotReader {
    public function readAt(DateTimeImmutable $cutoff): array
    {
        return [
            new BedSnapshot('b1', 'w1', 'MED', 'available', true),
            new BedSnapshot('b2', 'w1', 'MED', 'available', false),
            new BedSnapshot('b3', 'w1', 'MED', 'maintenance', false),
            new BedSnapshot('b4', 'w1', 'MED', 'occupied', false),
            new BedSnapshot('b5', 'w2', 'UCI', 'inactive', false),
        ];
    }
};
$controller = new OccupancyController(new ConsultOccupancy($reader, new OccupancyCalculator()));
$response = $controller->summary($cutoff);
$global = $response['body']['global'];

echo "== Camino feliz y reglas de dominio ==\n";
check('responde 200 con la misma fecha de corte', $response['status'] === 200 && $response['body']['cutoff_at'] === $cutoff->format(DATE_ATOM));
check('cada cama se cuenta exactamente una vez', $global['total_beds'] === 5 && array_sum(array_intersect_key($global, array_flip(['occupied', 'available', 'maintenance', 'inactive', 'unverified_occupied']))) === 5);
check('la estancia activa prevalece sobre status available', $global['occupied'] === 1 && $global['inconsistencies'] === 2);
check('occupied sin estancia queda no verificada', $global['unverified_occupied'] === 1);
check('la tasa usa solo ocupadas y disponibles', $global['occupancy_rate'] === 50.0);
check('la distribución usa el inventario total', $global['distribution_percentages']['maintenance'] === 20.0);
check('consolida las dos salas sin perder camas', count($response['body']['wards']) === 2);

$emptyCapacity = (new OccupancyCalculator())->calculate([
    new BedSnapshot('b6', 'w3', 'PED', 'maintenance', false),
], $cutoff);
check('sin capacidad operativa la tasa es null', $emptyCapacity['global']['occupancy_rate'] === null);

echo "\n== Error de persistencia ==\n";
$failingReader = new class implements BedSnapshotReader {
    public function readAt(DateTimeImmutable $cutoff): array
    {
        throw new PersistenceFailure('simulated');
    }
};
$failure = (new OccupancyController(new ConsultOccupancy($failingReader, new OccupancyCalculator())))->summary($cutoff);
check('la falla devuelve 503 y nunca ocupación cero', $failure === ['status' => 503, 'body' => ['error' => 'OCCUPANCY_UNAVAILABLE']]);

echo "\n== Arquitectura ==\n";
$root = __DIR__ . '/../src';
$domain = implode('', array_map('file_get_contents', glob("{$root}/Domain/*.php")));
$application = implode('', array_map('file_get_contents', glob("{$root}/Application/*.php")));
$presentation = implode('', array_map('file_get_contents', glob("{$root}/Presentation/*.php")));
$persistence = implode('', array_map('file_get_contents', glob("{$root}/Persistence/*.php")));
check('Domain y Application no dependen de PDO', !str_contains($domain . $application, 'PDO'));
check('Presentation no contiene SQL', !preg_match('/\b(SELECT|INSERT|UPDATE|DELETE)\b/', $presentation));
check('Persistence usa prepare y no query/exec', str_contains($persistence, '->prepare(') && !preg_match('/->(query|exec)\(/', $persistence));

echo "\n" . ($failures === 0 ? 'OK: 12 verificaciones aprobadas' : "ERROR: {$failures} verificaciones fallaron") . "\n";
exit($failures === 0 ? 0 : 1);
