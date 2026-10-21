<?php

declare(strict_types=1);

/**
 * Arnés mínimo de aserciones (PHP vanilla, sin PHPUnit), mismo estilo que
 * micro-his/rbac/tests/harness.php. Cada suite termina con summary().
 */
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/fixtures.php';

$GLOBALS['__suite'] = ['total' => 0, 'fallos' => 0];

function check(string $id, string $name, bool $ok): void
{
    $GLOBALS['__suite']['total']++;
    $ok || $GLOBALS['__suite']['fallos']++;
    echo ($ok ? 'OK   ' : 'FALLO') . " [{$id}] {$name}" . PHP_EOL;
}

/** Ejecuta $fn y devuelve la clase de la excepción lanzada (o null). */
function thrown(callable $fn): ?string
{
    try {
        $fn();
    } catch (\Throwable $e) {
        return $e::class;
    }

    return null;
}

function section(string $title): void
{
    echo PHP_EOL . "== {$title} ==" . PHP_EOL;
}

function summary(string $suite): never
{
    $s = $GLOBALS['__suite'];
    echo PHP_EOL . sprintf('[%s] %d pruebas, %d fallos.', $suite, $s['total'], $s['fallos']) . PHP_EOL;
    exit($s['fallos'] === 0 ? 0 : 1);
}
