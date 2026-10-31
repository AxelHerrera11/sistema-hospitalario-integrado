<?php

declare(strict_types=1);
use MicroHis\QaCalidad\Ci\JUnitWriter;

/**
 * Arnés mínimo de aserciones (PHP vanilla, sin PHPUnit), mismo estilo que
 * micro-his/gobernanza-auditoria/tests/harness.php. Cada suite termina con summary().
 * Con QA_JUNIT_DIR definido, además escribe <suite>.xml para el quality gate.
 */
require_once __DIR__.'/../bootstrap.php';
require_once __DIR__.'/doubles.php';

$GLOBALS['__suite'] = ['total' => 0, 'fallos' => 0, 'casos' => [], 'inicio' => microtime(true)];

function check(string $id, string $name, bool $ok): void
{
    $GLOBALS['__suite']['total']++;
    $ok || $GLOBALS['__suite']['fallos']++;
    $GLOBALS['__suite']['casos'][] = [$id, $name, $ok];
    echo ($ok ? 'OK   ' : 'FALLO')." [{$id}] {$name}".PHP_EOL;
}

/** Ejecuta $fn y devuelve la excepción lanzada (o null). */
function thrown(callable $fn): ?Throwable
{
    try {
        $fn();
    } catch (Throwable $e) {
        return $e;
    }

    return null;
}

/** true si $fn lanza $class y, si se indica, con ese ->reason. */
function throwsReason(callable $fn, string $class, ?string $reason = null): bool
{
    $e = thrown($fn);

    return $e instanceof $class && ($reason === null || ($e->reason ?? null) === $reason);
}

function section(string $title): void
{
    echo PHP_EOL."== {$title} ==".PHP_EOL;
}

function summary(string $suite): never
{
    $s = $GLOBALS['__suite'];
    $dir = getenv('QA_JUNIT_DIR');
    if (is_string($dir) && $dir !== '') {
        $writer = new JUnitWriter("qa-calidad.{$suite}");
        foreach ($s['casos'] as [$id, $name, $ok]) {
            $writer->add("[{$id}] {$name}", $ok ? 'passed' : 'failed');
        }
        $writer->write("{$dir}/{$suite}.xml");
    }
    echo PHP_EOL.sprintf('[%s] %d pruebas, %d fallos.', $suite, $s['total'], $s['fallos']).PHP_EOL;
    exit($s['fallos'] === 0 ? 0 : 1);
}
