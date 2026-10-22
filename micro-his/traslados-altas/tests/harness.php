<?php

declare(strict_types=1);

// Arnés mínimo de pruebas sin dependencias: cuenta verificaciones y fija el código de salida.

require_once __DIR__ . '/../bootstrap.php';

$failures = 0;
$total = 0;

function check(string $label, bool $condition): void
{
    global $failures, $total;
    $total++;
    echo ($condition ? 'PASS' : 'FAIL') . "  {$label}\n";
    $failures += $condition ? 0 : 1;
}

/** Devuelve la excepción lanzada si es de la clase esperada; null en cualquier otro caso. */
function thrown(callable $action, string $class): ?Throwable
{
    try {
        $action();
    } catch (Throwable $error) {
        return $error instanceof $class ? $error : null;
    }

    return null;
}

function finish(): never
{
    global $failures, $total;
    echo "\n" . ($failures === 0 ? "OK: {$total} verificaciones aprobadas" : "ERROR: {$failures} de {$total} verificaciones fallaron") . "\n";
    exit($failures === 0 ? 0 : 1);
}
