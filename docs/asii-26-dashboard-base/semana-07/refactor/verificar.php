<?php

declare(strict_types=1);
/* Verifica que el refactor conserva el comportamiento. Ejecutar: php refactor/verificar.php */

require __DIR__ . '/antes.php';
require __DIR__ . '/despues.php';

use function Refactor\Dashboard\tarjetasDespues;

$escenarios = [
    'ocupacion verde' => [['clave' => 'ocupacion', 'valor' => 85.0, 'meta' => 80.0]],
    'ocupacion amarillo (limite 90%)' => [['clave' => 'ocupacion', 'valor' => 72.0, 'meta' => 80.0]],
    'espera roja (menor es mejor)' => [['clave' => 'espera', 'valor' => 30.0, 'meta' => 20.0]],
    'citas verdes en el limite 100%' => [['clave' => 'citas_atendidas', 'valor' => 90.0, 'meta' => 90.0]],
    'clave desconocida se omite' => [['clave' => 'otro', 'valor' => 1.0, 'meta' => 1.0]],
];
$antes = static fn (array $e): array => tarjetasAntes($e);
$despues = static fn (array $e): array => tarjetasDespues($e);
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
