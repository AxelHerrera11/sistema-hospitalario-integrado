<?php

namespace App\Console\Commands;

use App\Modules\Reception\Infrastructure\OrderProjection;
use Illuminate\Console\Command;

final class ReceptionImportOrder extends Command
{
    protected $signature = 'reception:import-order {file : JSON confiable de una orden clínica}';
    protected $description = 'Importa requisitos y referencias locales desde órdenes de laboratorio';
    public function handle(OrderProjection $projection): int
    {
        $content = file_get_contents($this->argument('file'));
        $projection->import(json_decode($content, true, 512, JSON_THROW_ON_ERROR));
        $this->info('Orden sincronizada');
        return self::SUCCESS;
    }
}
