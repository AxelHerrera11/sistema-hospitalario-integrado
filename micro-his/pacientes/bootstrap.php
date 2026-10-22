<?php
declare(strict_types=1);

date_default_timezone_set(getenv('MICROHIS_TIMEZONE') ?: 'America/Guatemala');

spl_autoload_register(static function (string $class): void {
    $prefix = 'MicroHis\\Pacientes\\';
    if (!str_starts_with($class, $prefix)) return;

    $file = __DIR__ . '/src/' .
        str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

    if (is_file($file)) require $file;
});