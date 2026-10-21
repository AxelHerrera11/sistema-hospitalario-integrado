<?php

declare(strict_types=1);

// Mide la razón de contraste WCAG 2.1 de los colores que especifican los wireframes de Semana 8.
// Lee los colores directamente de las fuentes Salt. Uso: php docs/catalogo-medicamentos/semana-09/evidencia/contraste.php

function luminance(string $hex): float
{
    $channels = array_map(static fn (string $pair): float => hexdec($pair) / 255, str_split(ltrim($hex, '#'), 2));
    $linear = array_map(static fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4, $channels);

    return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
}

function ratio(string $foreground, string $background): float
{
    [$light, $dark] = [max(luminance($foreground), luminance($background)), min(luminance($foreground), luminance($background))];

    return ($light + 0.05) / ($dark + 0.05);
}

$colors = [];
foreach (glob(__DIR__ . '/../../semana-08/fuentes/w*.puml') as $file) {
    preg_match_all('/<color:(#[0-9A-Fa-f]{6})>/', (string) file_get_contents($file), $found);
    foreach ($found[1] as $color) {
        $colors[strtoupper($color)][] = basename($file, '.puml');
    }
}
$failures = 0;
foreach ($colors as $color => $where) {
    $value = ratio($color, '#FFFFFF');
    $ok = $value >= 4.5;
    $failures += $ok ? 0 : 1;
    printf("%s sobre #FFFFFF: %.2f:1 (mínimo AA texto normal 4.5:1) %s en %s\n", $color, $value, $ok ? 'CUMPLE' : 'NO CUMPLE', implode(', ', array_unique($where)));
}
printf("Control: #767676 sobre #FFFFFF = %.2f:1 (valor de referencia conocido 4.54:1)\n", ratio('#767676', '#FFFFFF'));
echo $colors === [] ? "No se encontraron colores en los wireframes.\n" : '';
exit($failures === 0 && $colors !== [] ? 0 : 1);
