<?php

declare(strict_types=1);
/* Verifica que el refactor conserva el comportamiento. Ejecutar: php refactor/verificar.php */

require __DIR__ . '/antes.php';
require __DIR__ . '/despues.php';

use Refactor\Citas\Agenda;
use function Refactor\Citas\agendarCitaDespues;

$previa = ['paciente' => 'P-1', 'doctor' => 'D-1', 'desde' => '2026-11-02T09:00', 'hasta' => '2026-11-02T09:30'];
$escenarios = [
    'camino feliz' => ['paciente' => 'P-2', 'doctor' => 'D-2', 'desde' => '2026-11-02T10:00', 'hasta' => '2026-11-02T10:30'],
    'solape paciente' => ['paciente' => 'P-1', 'doctor' => 'D-9', 'desde' => '2026-11-02T09:15', 'hasta' => '2026-11-02T09:45'],
    'solape doctor' => ['paciente' => 'P-9', 'doctor' => 'D-1', 'desde' => '2026-11-02T08:45', 'hasta' => '2026-11-02T09:05'],
    'contigua (limite)' => ['paciente' => 'P-1', 'doctor' => 'D-1', 'desde' => '2026-11-02T09:30', 'hasta' => '2026-11-02T10:00'],
    'rango invertido' => ['paciente' => 'P-3', 'doctor' => 'D-3', 'desde' => '2026-11-02T11:00', 'hasta' => '2026-11-02T10:00'],
    'faltan campos' => ['paciente' => 'P-4'],
];
$antes = static function (array $e) use ($previa): array {
    $citas = [];
    agendarCitaAntes($previa, $citas);
    return agendarCitaAntes($e, $citas);
};
$despues = static function (array $e) use ($previa): array {
    $agenda = new Agenda();
    agendarCitaDespues($agenda, $previa);
    return agendarCitaDespues($agenda, $e);
};
$iguales = 0;
foreach ($escenarios as $nombre => $entrada) {
    $a = $antes($entrada);
    $d = $despues($entrada);
    $ok = $a === $d;
    $iguales += $ok ? 1 : 0;
    printf("  [%s] %s -> %s\n", $ok ? 'IGUAL' : 'DISTINTO', $nombre, json_encode($d, JSON_UNESCAPED_UNICODE));
}

/** Metricas simples: lineas de codigo y nivel maximo de indentacion (proxy de anidamiento). */
$metricas = static function (string $archivo): array {
    $lineas = array_filter(file($archivo, FILE_IGNORE_NEW_LINES), static fn (string $l): bool => trim($l) !== '' && !str_starts_with(trim($l), '/'));
    $nivel = max(array_map(static fn (string $l): int => intdiv(strlen($l) - strlen(ltrim($l)), 4), $lineas));
    return [count($lineas), $nivel, substr_count(implode("\n", $lineas), 'if (')];
};
[$la, $na, $ia] = $metricas(__DIR__ . '/antes.php');
[$ld, $nd, $id] = $metricas(__DIR__ . '/despues.php');
printf("\nComportamiento: %d/%d escenarios iguales.\n", $iguales, count($escenarios));
printf("Antes:   %d lineas, anidamiento max. %d, %d sentencias if.\n", $la, $na, $ia);
printf("Despues: %d lineas, anidamiento max. %d, %d sentencias if.\n", $ld, $nd, $id);
exit($iguales === count($escenarios) ? 0 : 1);
