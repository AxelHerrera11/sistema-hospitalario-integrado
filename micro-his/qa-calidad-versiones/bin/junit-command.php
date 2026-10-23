<?php

declare(strict_types=1);

/**
 * Ejecuta un comando y deja su resultado como JUnit (1 caso) más el log completo.
 *
 *   php micro-his/qa-calidad-versiones/bin/junit-command.php --suite=lint-pint \
 *       --junit=qa-evidence/lint/pint.xml --log=qa-evidence/lint/pint.log -- vendor/bin/pint --test
 *
 * Sale con el mismo código que el comando, para que el paso de CI también falle.
 */
use MicroHis\QaCalidad\Ci\JUnitWriter;

require __DIR__.'/../bootstrap.php';

$separator = array_search('--', $argv, true);
if ($separator === false || ! isset($argv[$separator + 1])) {
    fwrite(STDERR, "Uso: junit-command.php --suite=N --junit=F --log=F -- comando [args]\n");
    exit(3);
}
$opts = getopt('', ['suite:', 'junit:', 'log:']);
$suite = (string) ($opts['suite'] ?? '');
$junit = (string) ($opts['junit'] ?? '');
$log = (string) ($opts['log'] ?? '');
if ($suite === '' || $junit === '' || $log === '') {
    fwrite(STDERR, "Faltan --suite, --junit o --log.\n");
    exit(3);
}
is_dir(dirname($log)) || mkdir(dirname($log), 0775, true);

$command = array_slice($argv, $separator + 1);
$started = microtime(true);
// Arreglo de argumentos: sin shell intermedio, sin interpolación.
$process = proc_open($command, [1 => ['file', $log, 'w'], 2 => ['redirect', 1]], $pipes);
$code = is_resource($process) ? proc_close($process) : 127;
$seconds = microtime(true) - $started;
// Pie del log: así la evidencia nunca queda vacía aunque el comando no imprima nada.
file_put_contents($log, sprintf("[junit-command] %s → exit %d en %.2f s\n", implode(' ', $command), $code, $seconds), FILE_APPEND);

$writer = new JUnitWriter($suite);
$tail = implode("\n", array_slice(file($log, FILE_IGNORE_NEW_LINES) ?: [], -15));
$writer->add(implode(' ', $command), $code === 0 ? 'passed' : 'failed', $seconds, "exit {$code}\n{$tail}");
$writer->write($junit);
echo "[{$suite}] exit {$code} en ".round($seconds, 2)." s; log: {$log}".PHP_EOL;
exit($code);
