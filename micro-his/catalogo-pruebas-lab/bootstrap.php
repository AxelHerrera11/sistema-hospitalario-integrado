<?php

declare(strict_types=1);

// Autocarga del namespace MicroHis\CatalogoLab desde src/ (PHP vanilla, sin Composer ni framework).
spl_autoload_register(static function (string $class): void {
    $prefix = 'MicroHis\\CatalogoLab\\';
    if (str_starts_with($class, $prefix)) {
        require __DIR__ . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    }
});
