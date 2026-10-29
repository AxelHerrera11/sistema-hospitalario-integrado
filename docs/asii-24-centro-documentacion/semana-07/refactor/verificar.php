<?php

declare(strict_types=1);
/* Verifica que el refactor conserva el comportamiento. Ejecutar: php refactor/verificar.php */

require __DIR__ . '/antes.php';
require __DIR__ . '/despues.php';

use function Refactor\Documentacion\listarDocumentosDespues;

$catalogo = [
    ['titulo' => 'Manual de enfermeria', 'roles' => ['enfermeria']],
    ['titulo' => 'Guia de prescripcion', 'roles' => ['medico', 'farmacia']],
    ['titulo' => 'Protocolo de alergias', 'roles' => ['medico', 'enfermeria']],
];
$escenarios = ['admin' => 'admin', 'medico' => 'medico', 'enfermeria' => 'enfermeria', 'farmacia' => 'farmacia'];
$antes = static fn (string $rol): array => listarDocumentosAntes($catalogo, $rol);
$despues = static fn (string $rol): array => listarDocumentosDespues($catalogo, $rol);
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
