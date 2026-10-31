<?php

declare(strict_types=1);

// Autoload PSR-4 mínimo del micro-HIS: permite probar el núcleo sin Composer ni Laravel.

spl_autoload_register(static function (string $class): void {
    $prefix = 'MicroHis\\DashboardBase\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
