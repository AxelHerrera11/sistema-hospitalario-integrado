<?php

declare(strict_types=1);

require __DIR__.'/../app.php';

use MicroHis\Reportes\Persistence\InMemoryEpisodeSummaryReader;
use MicroHis\Reportes\Persistence\PdoEpisodeSummaryReader;

/** Escenario 1: camino feliz con PDO preparado (SQLite en memoria). */
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec((string) file_get_contents(__DIR__.'/../database/schema.sql'));
$pdo->exec("INSERT INTO care_episodes VALUES
    ('e1', 'inpatient', 'closed',    '2026-01-02T08:00:00-06:00', '2026-01-04T08:00:00-06:00', 'HOSP-UCI'),
    ('e2', 'inpatient', 'closed',    '2026-01-05T08:00:00-06:00', '2026-01-07T08:00:00-06:00', 'HOSP-UCI'),
    ('e3', 'inpatient', 'cancelled', '2026-01-06T08:00:00-06:00', '2026-01-06T09:00:00-06:00', 'HOSP-UCI'),
    ('e4', 'inpatient', 'open',      '2026-01-08T08:00:00-06:00', NULL, 'HOSP-UCI'),
    ('e5', 'outpatient', 'cancelled', '2026-01-09T08:00:00-06:00', '2026-01-09T09:00:00-06:00', 'HOSP-UCI'),
    ('e6', 'inpatient', 'closed',    '2026-01-10T08:00:00-06:00', '2026-01-11T08:00:00-06:00', 'HOSP-PED')");
$map = [
    ['e1', 33.33, '', 'total 3 = {e1 closed, e2 closed, e3 cancelled} (open e4 y outpatient e5 fuera); cancelados 1'],
    ['e2', 50.00, 'HOSP-UCI', 'mismo periodo con servicio']];
$check = static function (string $label, bool $ok) use (&$checks): void {
    $checks++;
    echo ($ok ? 'OK   ' : 'FAIL '), $label, "\n";
    !$ok && exit(1);
};
$router = reportes_router(new PdoEpisodeSummaryReader($pdo));
$call = static function (array $body) use ($router): array {
    return $router->dispatch('POST', '/indicators/IND-RA-01/calculate', $body);
};
$period = static function (string $from, string $until): array {
    return ['from' => $from, 'until' => $until];
};
$ok = $call($period('2026-01-01T00:00:00-06:00', '2026-02-01T00:00:00-06:00'));
$check('MicroHIS: resulta 25.00 con 4 finalizadas y 1 cancelada (sin open/outpatient)', $ok['body']['percentage'] === 25.00);
$check('MicroHIS: trazabilidad completa', $ok['body']['indicator'] === 'IND-RA-01'
    && $ok['body']['formula_version'] === 'TCI-1.0' && $ok['body']['source'] === 'HOSPITAL.care_episodes'
    && isset($ok['body']['computed_at']));
$con_servicio = $call($period('2026-01-01T00:00:00-06:00', '2026-02-01T00:00:00-06:00') + ['service_code' => 'HOSP-UCI']);
$check('MicroHIS: filtro service_code excluye HOSP-PED => 33.33', $con_servicio['body']['percentage'] === 33.33);

/** Escenario 2: regla de dominio (periodo_invalido: desde > hasta) => 422 sin pasar por la fuente. */
$invalido = $call($period('2026-02-01T00:00:00-06:00', '2026-01-01T00:00:00-06:00'));
$check('MicroHIS: periodo invertido rechazado => 422 periodo_invalido sin porcentaje', $invalido['status'] === 422
    && $invalido['body']['code'] === 'periodo_invalido' && !array_key_exists('percentage', $invalido['body']));
$sintaxon = $call(['until' => '2026-02-01T00:00:00-06:00']);
$check('MicroHIS: entrada sin from => 422 entrada_invalida', $sintaxon['status'] === 422
    && $sintaxon['body']['code'] === 'entrada_invalida');
$sinzona = $call(['from' => '2026-01-01T00:00:00', 'until' => '2026-02-01T00:00:00-06:00']);
$check('MicroHIS: periodo sin offset explicito => 422 periodo_invalido', $sinzona['status'] === 422
    && $sinzona['body']['code'] === 'periodo_invalido');

/** Escenario 3: error de persistencia (falla del lector) => 503 sin datos. */
$falla = reportes_router(new InMemoryEpisodeSummaryReader());
$falla->dispatch('POST', '/indicators/IND-RA-01/calculate', $period('2026-01-01T00:00:00-06:00', '2026-02-01T00:00:00-06:00'));
$failing = reportes_router(new class implements MicroHis\Reportes\Application\EpisodeSummaryReader {
    public function read(string $fromInclusive, string $untilExclusive, string $serviceOrEmpty): ?array
    {
        throw new RuntimeException('lectura_no_disponible');
    }
});
$err = $failing->dispatch('POST', '/indicators/IND-RA-01/calculate', $period('2026-01-01T00:00:00-06:00', '2026-02-01T00:00:00-06:00'));
$check('MicroHIS: fallo de persistencia => 503 LECTURA_NO_DISPONIBLE sin cifras', $err['status'] === 503
    && $err['body']['code'] === 'LECTURA_NO_DISPONIBLE' && !array_key_exists('percentage', $err['body']));

/** Escenario 4: sin datos (denominador 0) => estado, no porcentaje. */
$sinDatos = reportes_router(new InMemoryEpisodeSummaryReader())->dispatch('POST', '/indicators/IND-RA-01/calculate', $period('2026-05-01T00:00:00-06:00', '2026-06-01T00:00:00-06:00'));
$check('MicroHIS: sin datos del periodo => SIN_DATOS_EN_PERIODO sin porcentaje', $sinDatos['status'] === 200
    && $sinDatos['body']['state'] === 'SIN_DATOS_EN_PERIODO' && !array_key_exists('percentage', $sinDatos['body']));

/** Escenario 5: lectura incoherente (N_cancelado > N_finalizado). */
$coherente = new InMemoryEpisodeSummaryReader();
$coherente->forced = [10, 12];
$incoherente = reportes_router($coherente)->dispatch('POST', '/indicators/IND-RA-01/calculate', $period('2026-01-01T00:00:00-06:00', '2026-02-01T00:00:00-06:00'));
$check('MicroHIS: lectura incoherente => 503 LECTURA_NO_DISPONIBLE sin porcentaje', $incoherente['status'] === 503
    && !array_key_exists('percentage', $incoherente['body']));
$separado = reportes_router(new PdoEpisodeSummaryReader($pdo))->dispatch('GET', '/indicators/IND-RA-01/calculate', []);
$check('MicroHIS: GET no permitido => 404 ROUTE_NOT_FOUND', $separado['status'] === 404);

/** Escenario 6: estado cero valido (40 finalizadas, 0 canceladas). */
$cero = new InMemoryEpisodeSummaryReader();
$cero->returnNull = false;
$cero->finalized = 40;
$cero->cancelled = 0;
$zeroResult = reportes_router($cero)->dispatch('POST', '/indicators/IND-RA-01/calculate', $period('2026-01-01T00:00:00-06:00', '2026-02-01T00:00:00-06:00'));
$check('MicroHIS: cero valido => 0.00 con calculo y instante', $zeroResult['status'] === 200
    && $zeroResult['body']['percentage'] === 0.00 && isset($zeroResult['body']['computed_at']));

echo "run.php OK: {$checks} escenarios del indicador IND-RA-01.\n";
exit(0);

