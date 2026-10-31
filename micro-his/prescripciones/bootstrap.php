<?php

declare(strict_types=1);

// Autocarga del namespace MicroHis\Prescripciones desde src/ (PHP vanilla, sin Composer ni framework).
spl_autoload_register(static function (string $class): void {
    $prefix = 'MicroHis\\Prescripciones\\';
    if (! str_starts_with($class, $prefix)) {
        return;
    }

    $path = __DIR__.'/src/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
    if (is_file($path)) {
        require $path;
    }
});
