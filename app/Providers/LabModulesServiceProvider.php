<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

final class LabModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once base_path('micro-his/ingreso-resultados-lab/bootstrap.php');
        require_once base_path('micro-his/validacion-resultados-bioquimico/bootstrap.php');
        require_once base_path('micro-his/nativephp-extension-prototype/bootstrap.php');
    }
}
