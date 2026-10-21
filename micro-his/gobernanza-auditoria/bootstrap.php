<?php

declare(strict_types=1);

// Autocarga del namespace MicroHis\Gobernanza desde src/ (PHP vanilla, sin Composer ni framework).
spl_autoload_register(static function (string $class): void {
    $prefix = 'MicroHis\\Gobernanza\\';
    if (str_starts_with($class, $prefix)) {
        $file = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});
