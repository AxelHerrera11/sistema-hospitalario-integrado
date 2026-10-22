<?php

declare(strict_types=1);
/*
 * Evidencia Semana 2 (OCP) — ASII-27
 * Ejecutar: php docs/asii-27-reportes/semana-02/evidencia/demo-ocp.php (solo PHP 8.2+)
 */

// ---- ANTES: abierto a modificación (viola OCP) ----
final class ReporteServiceAntes
{
    public function exportar(array $filas, array $f, string $formato): string
    {
        $filas = array_values(array_filter($filas, static fn (array $r): bool =>
            (!isset($f['estado']) || $r['estado'] === $f['estado'])
            && (!isset($f['desde']) || $r['fecha'] >= $f['desde'])));
        if ($formato === 'csv') { return 'csv:' . count($filas); }
        if ($formato === 'pdf') { return 'pdf:' . count($filas); }
        throw new InvalidArgumentException("Formato no soportado: {$formato}");
    }
}
// ---- DESPUÉS: cerrado a modificación (aplica OCP) ----
interface FiltroReporte
{
    public function soporta(string $clave): bool;
    public function aplicar(array $filas, mixed $valor): array;
}
interface ExportadorReporte
{
    public function soporta(string $formato): bool;
    public function exportar(array $filas): string;
}
final class FiltroPorEstado implements FiltroReporte
{
    public function soporta(string $clave): bool { return $clave === 'estado'; }
    public function aplicar(array $filas, mixed $v): array
    { return array_values(array_filter($filas, static fn (array $r): bool => $r['estado'] === $v)); }
}
final class FiltroPorRangoFecha implements FiltroReporte
{
    public function soporta(string $clave): bool { return $clave === 'desde'; }
    public function aplicar(array $filas, mixed $v): array
    { return array_values(array_filter($filas, static fn (array $r): bool => $r['fecha'] >= $v)); }
}
final class ExportadorCsv implements ExportadorReporte
{
    public function soporta(string $formato): bool { return $formato === 'csv'; }
    public function exportar(array $filas): string
    {
        $lineas = array_map(static fn (array $r): string => implode(',', $r), $filas);
        return implode("\n", array_merge(['folio,departamento,estado,fecha'], $lineas));
    }
}
// Extensión NUEVA: sin tocar ExportadorCsv ni ReporteServiceDespues.
final class ExportadorExcel implements ExportadorReporte
{
    public function soporta(string $formato): bool { return $formato === 'xlsx'; }
    public function exportar(array $filas): string { return 'xlsx:' . count($filas); }
}
final class ReporteServiceDespues
{
    public function __construct(private iterable $filtros, private iterable $exportadores) {}
    public function exportar(array $filas, array $criterios, string $formato): string
    {
        foreach ($criterios as $clave => $valor) {
            foreach ($this->filtros as $filtro) {
                if ($filtro->soporta((string) $clave)) { $filas = $filtro->aplicar($filas, $valor); }
            }
        }
        foreach ($this->exportadores as $e) {
            if ($e->soporta($formato)) { return $e->exportar($filas); }
        }
        throw new InvalidArgumentException("Formato no soportado: {$formato}");
    }
}
// ---- Datos ficticios y ejecución ----
$datos = [
    ['folio' => 'A-1', 'departamento' => 'admision', 'estado' => 'abierta', 'fecha' => '2026-10-01'],
    ['folio' => 'A-2', 'departamento' => 'admision', 'estado' => 'cerrada', 'fecha' => '2026-10-05'],
    ['folio' => 'A-3', 'departamento' => 'laboratorio', 'estado' => 'abierta', 'fecha' => '2026-10-09'],
];
$criterios = ['estado' => 'abierta', 'desde' => '2026-10-02'];
$antes = new ReporteServiceAntes();
echo 'ANTES  csv: ', $antes->exportar($datos, $criterios, 'csv'), "\n";
try { $antes->exportar($datos, $criterios, 'xlsx'); }
catch (InvalidArgumentException $e) { echo 'ANTES  xlsx: ', $e->getMessage(), " (obliga a EDITAR la clase)\n"; }
$svc = new ReporteServiceDespues(
    [new FiltroPorEstado(), new FiltroPorRangoFecha()],
    [new ExportadorCsv(), new ExportadorExcel()]
);
$c1 = $svc->exportar($datos, $criterios, 'csv');
$c2 = $svc->exportar($datos, $criterios, 'csv');
echo "DESPUÉS csv:\n", $c1, "\n";
echo 'DESPUÉS xlsx (nuevo, orquestador intacto): ', $svc->exportar($datos, $criterios, 'xlsx'), "\n";
echo 'DESPUÉS reproducible: ', ($c1 === $c2 ? 'OK' : 'FALLA'), "\n";
try { $svc->exportar($datos, $criterios, 'pdf'); }
catch (InvalidArgumentException $e) { echo 'DESPUÉS pdf: excepción controlada -> ', $e->getMessage(), "\n"; }
