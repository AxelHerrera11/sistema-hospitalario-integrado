<?php

declare(strict_types=1);

namespace MicroHis\CatalogoLab\Presentation;

/** View: tabla HTML de las pruebas vigentes. Solo presenta; todo texto se escapa. */
final class LabTestListView
{
    /** @param list<array<string, mixed>> $tests */
    public function render(array $tests, string $date): string
    {
        $e = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $rows = array_map(static fn (array $t): string => "<tr><td>{$e($t['code'])}</td><td>{$e($t['name'])}</td><td>{$e($t['specimen_type'])}</td>"
            . "<td>{$e($t['unit'])}</td><td>{$e($t['reference_low'])}–{$e($t['reference_high'])}</td><td>v{$e($t['version_no'])}</td></tr>", $tests);
        $body = $rows === [] ? '<tr><td colspan="6">No hay pruebas vigentes en esta fecha.</td></tr>' : implode('', $rows);

        return "<!doctype html><html lang=\"es\"><meta charset=\"utf-8\"><title>Catálogo de pruebas</title><h1>Pruebas vigentes al {$e($date)}</h1>"
            . "<table><tr><th>Código</th><th>Nombre</th><th>Muestra</th><th>Unidad</th><th>Referencia</th><th>Versión</th></tr>{$body}</table></html>";
    }
}
