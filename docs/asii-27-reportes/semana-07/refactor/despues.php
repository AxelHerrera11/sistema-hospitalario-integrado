<?php

declare(strict_types=1);
/* DESPUES: Exportador polimorfico + clase base para formatos delimitados (OCP). */

namespace Refactor\Reportes;

interface Exportador
{
    public function soporta(string $formato): bool;

    public function exportar(array $filas): string;
}

abstract class ExportadorDelimitado implements Exportador
{
    abstract protected function separador(): string;

    public function exportar(array $filas): string
    {
        $lineas = [array_keys($filas[0]), ...$filas];
        return implode('', array_map(fn (array $f): string => implode($this->separador(), $f) . "\n", $lineas));
    }
}

final class ExportadorCsv extends ExportadorDelimitado
{
    public function soporta(string $formato): bool { return $formato === 'csv'; }
    protected function separador(): string { return ','; }
}

final class ExportadorTsv extends ExportadorDelimitado
{
    public function soporta(string $formato): bool { return $formato === 'tsv'; }
    protected function separador(): string { return "\t"; }
}

final class ExportadorJson implements Exportador
{
    public function soporta(string $formato): bool { return $formato === 'json'; }
    public function exportar(array $filas): string { return json_encode(['data' => $filas, 'total' => count($filas)]); }
}

function exportarDespues(array $filas, string $formato, ?array $exportadores = null): string
{
    foreach ($exportadores ?? [new ExportadorCsv(), new ExportadorTsv(), new ExportadorJson()] as $exportador) {
        if ($exportador->soporta($formato)) {
            return $exportador->exportar($filas);
        }
    }
    return 'ERROR: report_format_unsupported';
}
