<?php

declare(strict_types=1);

/**
 * Arnés mínimo de aserciones para las pruebas de la Semana 15 (PHP vanilla, sin PHPUnit).
 * check() acumula el resultado; summary() emite el resumen y el código de salida del proceso.
 */
$GLOBALS['__suite'] = ['total' => 0, 'fallos' => 0];

function check(string $name, bool $ok): void
{
    $GLOBALS['__suite']['total']++;
    $ok || $GLOBALS['__suite']['fallos']++;
    echo ($ok ? 'OK' : 'FALLO') . ": $name" . PHP_EOL;
}

function summary(string $suite): void
{
    $s = $GLOBALS['__suite'];
    echo sprintf('[%s] %d pruebas, %d fallos.', $suite, $s['total'], $s['fallos']) . PHP_EOL;
    exit($s['fallos'] === 0 ? 0 : 1);
}
