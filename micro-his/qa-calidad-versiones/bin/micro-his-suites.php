<?php

declare(strict_types=1);

/**
 * Corre tests/run.php de cada módulo de micro-his (de todos los compañeros) y
 * deja un caso JUnit por módulo. Un módulo sin tests/run.php queda "skipped":
 * se informa, no se cuenta como aprobado.
 *
 *   php micro-his/qa-calidad-versiones/bin/micro-his-suites.php qa-evidence/micro-his
 */
use MicroHis\QaCalidad\Ci\JUnitWriter;

require __DIR__.'/../bootstrap.php';

$out = $argv[1] ?? '';
if ($out === '') {
    fwrite(STDERR, "Uso: micro-his-suites.php <carpeta-salida>\n");
    exit(3);
}
is_dir($out) || mkdir($out, 0775, true);

$writer = new JUnitWriter('micro-his');
foreach (glob(dirname(__DIR__, 2).'/*', GLOB_ONLYDIR) ?: [] as $dir) {
    $module = basename($dir);
    $runner = $dir.'/tests/run.php';
    if (! is_file($runner)) {
        $writer->add($module, 'skipped', 0.0, 'El módulo no tiene tests/run.php');
        echo "SIN SUITE  {$module}".PHP_EOL;

        continue;
    }
    $log = "{$out}/{$module}.log";
    $started = microtime(true);
    $process = proc_open([PHP_BINARY, $runner], [1 => ['file', $log, 'w'], 2 => ['redirect', 1]], $pipes);
    $code = is_resource($process) ? proc_close($process) : 127;
    $seconds = microtime(true) - $started;
    $tail = implode("\n", array_slice(file($log, FILE_IGNORE_NEW_LINES) ?: [], -10));
    $writer->add($module, $code === 0 ? 'passed' : 'failed', $seconds, "exit {$code}\n{$tail}");
    echo ($code === 0 ? 'OK         ' : 'FALLO      ').$module.' ('.round($seconds, 2).' s)'.PHP_EOL;
}
$writer->write("{$out}/micro-his.xml");
exit($writer->failures() === 0 ? 0 : 1);
