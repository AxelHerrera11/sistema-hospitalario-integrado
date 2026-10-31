<?php

declare(strict_types=1);

/**
 * Evidencia de la Semana 9: razón de contraste WCAG 2.x (luminancia relativa) de los colores que el diseño de la Semana 8
 * usa para la severidad, «no evaluado», errores y texto de ayuda. Los tonos son los valores hex de Tailwind v3 (el HIS usa Tailwind 4, que los define en OKLCH);
 * el repositorio todavía no define los colores del módulo, así que la tabla fija el mínimo que deberá respetar.
 * Uso: php docs/signos-vitales/semana-09/evidencia/contraste.php
 */
function luminance(string $hex): float
{
    $channels = array_map(
        static fn (string $c): float => ($v = hexdec($c) / 255) <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4,
        str_split(ltrim($hex, '#'), 2),
    );
    return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
}

function ratio(string $a, string $b): float
{
    [$hi, $lo] = [max(luminance($a), luminance($b)), min(luminance($a), luminance($b))];
    return ($hi + 0.05) / ($lo + 0.05);
}

/** @var list<array{string, string, string, float}> $pairs uso, primer plano, fondo, mínimo exigido (4.5 texto, 3.0 no textual) */
$pairs = [
    ['Texto «Crítica» en rojo 600 sobre blanco', '#DC2626', '#FFFFFF', 4.5],
    ['Texto «Crítica» en rojo 700 sobre blanco', '#B91C1C', '#FFFFFF', 4.5],
    ['Texto «Advertencia» en ámbar 500 sobre blanco', '#F59E0B', '#FFFFFF', 4.5],
    ['Texto «Advertencia» en ámbar 700 sobre blanco', '#B45309', '#FFFFFF', 4.5],
    ['Texto «Sin valores fuera de umbral» en verde 600', '#16A34A', '#FFFFFF', 4.5],
    ['Texto «Sin valores fuera de umbral» en verde 700', '#15803D', '#FFFFFF', 4.5],
    ['Texto «No evaluado» en gris 400', '#9CA3AF', '#FFFFFF', 4.5],
    ['Texto «No evaluado» en gris 600', '#4B5563', '#FFFFFF', 4.5],
    ['Texto de ayuda y trace_id en gris 500', '#6B7280', '#FFFFFF', 4.5],
    ['Marcador de posición en gris 400', '#9CA3AF', '#FFFFFF', 4.5],
    ['Borde de campo en gris 300', '#D1D5DB', '#FFFFFF', 3.0],
    ['Borde de campo en gris 500', '#6B7280', '#FFFFFF', 3.0],
    ['Anillo de foco en azul 600', '#2563EB', '#FFFFFF', 3.0],
    ['Insignia «Crítica»: texto blanco sobre rojo 600', '#FFFFFF', '#DC2626', 4.5],
    ['Insignia «Advertencia»: texto blanco sobre ámbar 500', '#FFFFFF', '#F59E0B', 4.5],
    ['Insignia «Advertencia»: texto negro sobre ámbar 500', '#000000', '#F59E0B', 4.5],
];

$fails = 0;
foreach ($pairs as [$use, $fg, $bg, $min]) {
    $r = ratio($fg, $bg);
    $ok = $r >= $min;
    $ok || $fails++;
    printf("%s  %-52s %s sobre %s  %5.2f:1  (mínimo %.1f)%s", $ok ? 'CUMPLE ' : 'NO CUMPLE', $use, $fg, $bg, $r, $min, PHP_EOL);
}
printf("%d de %d combinaciones no cumplen.%s", $fails, count($pairs), PHP_EOL);
