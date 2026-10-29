<?php

declare(strict_types=1);
/* Contraste WCAG 2.x de los pares de color de la guia de estilos. Ejecutar: php evidencia/contraste.php */

function luminancia(string $hex): float
{
    $canal = static function (int $v): float {
        $c = $v / 255;
        return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
    };
    [$r, $g, $b] = array_map('hexdec', str_split(ltrim($hex, '#'), 2));
    return 0.2126 * $canal($r) + 0.7152 * $canal($g) + 0.0722 * $canal($b);
}

function contraste(string $a, string $b): float
{
    [$l1, $l2] = [luminancia($a), luminancia($b)];
    return (max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05);
}

/* [uso, texto, fondo, minimo] - 4.5 texto normal, 3.0 componentes de interfaz (1.4.11) */
$pares = [
    ['Texto principal sobre fondo', '#0f172a', '#f8fafc', 4.5],
    ['Texto secundario sobre fondo', '#475569', '#f8fafc', 4.5],
    ['Boton primario (blanco sobre azul)', '#ffffff', '#1d4ed8', 4.5],
    ['Encabezado (blanco sobre azul oscuro)', '#ffffff', '#1e3a8a', 4.5],
    ['Menu activo', '#1e3a8a', '#dbeafe', 4.5],
    ['Enlace / badge informativo', '#1d4ed8', '#ffffff', 4.5],
    ['Badge exito', '#15803d', '#ffffff', 4.5],
    ['Badge aviso', '#b45309', '#ffffff', 4.5],
    ['Badge / texto de error', '#b91c1c', '#ffffff', 4.5],
    ['Boton deshabilitado', '#475569', '#e2e8f0', 4.5],
    ['Borde de campo de formulario', '#64748b', '#ffffff', 3.0],
    ['Contorno de foco sobre fondo', '#1d4ed8', '#f8fafc', 3.0],
];
$fallas = 0;
foreach ($pares as [$uso, $texto, $fondo, $minimo]) {
    $ratio = contraste($texto, $fondo);
    $ok = $ratio >= $minimo;
    $fallas += $ok ? 0 : 1;
    printf("  [%s] %-40s %s / %s  %5.2f:1 (min %.1f)\n", $ok ? 'CUMPLE' : 'FALLA', $uso, $texto, $fondo, $ratio, $minimo);
}
printf("\n%d pares evaluados, %d por debajo del minimo.\n", count($pares), $fallas);
exit($fallas === 0 ? 0 : 1);
