<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use MicroHis\DashboardBase\Application\CitasDelDia;
use MicroHis\DashboardBase\Application\ResumenTablero;
use MicroHis\DashboardBase\Domain\Indicador;

$failures = 0;
$total = 0;

function check(string $label, bool $condition): void
{
    global $failures, $total;
    $total++;
    echo ($condition ? 'PASS' : 'FAIL') . "  {$label}\n";
    $failures += $condition ? 0 : 1;
}

check('menor es mejor: 15 min con meta 20 cumple (RTF-01)', (new Indicador('espera', 'Espera', 15, 20, Indicador::MENOR_MEJOR))->cumpleMeta());
check('mayor es mejor: 79.9 con meta 80 no cumple', !(new Indicador('ocupacion', 'Ocupacion', 79.9, 80, Indicador::MAYOR_MEJOR))->cumpleMeta());
try {
    new Indicador('x', 'X', -1, 10, Indicador::MAYOR_MEJOR);
    check('rechaza valor negativo (RTF-02)', false);
} catch (InvalidArgumentException) {
    check('rechaza valor negativo (RTF-02)', true);
}

$citas = new class implements CitasDelDia {
    public function porEstado(string $fecha): array
    {
        return ['scheduled' => 6, 'completed' => 3, 'cancelled' => 1];
    }
};
$resumen = (new ResumenTablero($citas))->ejecutar('2026-11-02', ['citas.ver']);
check('compone la tarjeta de citas con su total', $resumen['tarjetas'][0]['total'] === 10);
check('10 % de cancelaciones cumple la meta de 10 %', $resumen['tarjetas'][0]['indicador']['cumple_meta'] === true);
check('sin permisos no hay tarjetas (falla cerrada)', (new ResumenTablero($citas))->ejecutar('2026-11-02', [])['tarjetas'] === []);

echo "\n" . ($failures === 0 ? "OK: {$total} verificaciones aprobadas" : "ERROR: {$failures} de {$total} verificaciones fallaron") . "\n";
exit($failures === 0 ? 0 : 1);
