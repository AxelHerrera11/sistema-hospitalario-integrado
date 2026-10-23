<?php

declare(strict_types=1);

require __DIR__.'/../bootstrap.php';

use Reception\Domain\CompositeRequirementsValidator;
use Reception\Domain\SampleValidator;

$now = new DateTimeImmutable('2026-10-23T12:00:00Z');
$requirements = ['container' => 'EDTA', 'min_volume' => 3, 'max_minutes' => 60, 'min_temperature' => 15, 'max_temperature' => 25];
$input = ['type' => 'blood', 'container' => 'EDTA', 'volume' => 3, 'temperature' => 20, 'collected_at' => '2026-10-23T11:30:00Z'];
foreach ([new SampleValidator('blood'), new CompositeRequirementsValidator([new SampleValidator('blood')])] as $validator) {
    foreach ([[$input, null], [array_replace($input, ['volume' => 2.9]), 'volumen_insuficiente'], [array_replace($input, ['type' => 'urine']), 'tipo_incorrecto']] as [$observed, $expected]) {
        if ($validator->reason($observed, $requirements, $now) !== $expected) {
            throw new RuntimeException('Contrato LSP incumplido');
        }
    }
    echo 'PASS LSP '.get_class($validator).PHP_EOL;
}
echo 'OK 2 adaptadores, 6 comprobaciones; memoria, sin base de datos.'.PHP_EOL;
