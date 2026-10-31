<?php

declare(strict_types=1);
/*
 * Auditoria automatica de accesibilidad del prototipo (Semana 8) con DOMDocument.
 * Ejecutar: php evidencia/auditoria-accesibilidad.php [ruta-al-html]   (por defecto ../semana-08/prototipo/index.html)
 */

$ruta = $argv[1] ?? __DIR__ . '/../../semana-08/prototipo/index.html';
$html = (string) file_get_contents($ruta);
$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML('<?xml encoding="utf-8"?>' . $html);
$x = new DOMXPath($dom);

$controles = $x->query('//input|//select|//textarea');
$sinEtiqueta = 0;
foreach ($controles as $c) {
    $id = $c->getAttribute('id');
    $porFor = $id !== '' && $x->query("//label[@for='{$id}']")->length > 0;
    $envuelto = $x->query('ancestor::label', $c)->length > 0;
    $sinEtiqueta += ($porFor || $envuelto) ? 0 : 1;
}
$pantallas = $x->query('//section[contains(@class,"pantalla")]');
$sinH1 = 0;
foreach ($pantallas as $p) {
    $sinH1 += $x->query('.//h1|.//h2', $p)->length > 0 ? 0 : 1;
}
$deshabilitados = $x->query('//a[@aria-disabled="true"]');
$chequeos = [
    '3.1.1 idioma de la pagina (lang)' => $x->query('//html[@lang]')->length === 1,
    '2.4.2 titulo de la pagina' => trim((string) $x->query('//title')->item(0)?->textContent) !== '',
    '2.4.1 enlace para saltar al contenido' => $x->query('//a[@href="#contenido"]')->length > 0,
    '1.3.1 / 3.3.2 controles con etiqueta (' . $controles->length . ')' => $sinEtiqueta === 0,
    '1.3.1 cada pantalla tiene encabezado (' . $pantallas->length . ')' => $sinH1 === 0,
    '1.3.1 tablas con encabezados th' => $x->query('//table[not(.//th)]')->length === 0,
    '1.1.1 imagenes con alt' => $x->query('//img[not(@alt)]')->length === 0,
    '4.1.3 alertas con role status/alert' => $x->query('//*[contains(@class,"alerta")][not(@role)]')->length === 0,
    '2.4.7 estilo de foco visible definido' => str_contains($html, ':focus-visible'),
    '2.5.8 objetivo tactil >= 24px (usa 44px)' => str_contains($html, 'min-height:44px'),
    '1.4.10 reflow: media query movil' => str_contains($html, '@media (max-width:767px)'),
    '2.1.1 controles deshabilitados explicados sin depender de title' => $deshabilitados->length === 0,
];
$fallas = 0;
foreach ($chequeos as $nombre => $ok) {
    $fallas += $ok ? 0 : 1;
    printf("  [%s] %s\n", $ok ? 'CUMPLE' : 'REVISAR', $nombre);
}
printf("\n%d chequeos, %d a revisar.\n", count($chequeos), $fallas);
