<?php

declare(strict_types=1);

namespace MicroHis\Admision\Presentation;

/** View: tablero HTML de camas disponibles para Admisión. Solo presenta; todo texto se escapa. */
final class AvailableBedsView
{
    /** @param list<array<string, mixed>> $beds */
    public function render(array $beds, string $type): string
    {
        $e = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $rows = implode('', array_map(static fn (array $b): string => "<tr><td>{$e($b['code'])}</td><td>{$e($b['type'])}</td></tr>", $beds));

        return "<!doctype html><html lang=\"es\"><meta charset=\"utf-8\"><title>Camas disponibles</title>"
            . '<h1>Camas disponibles' . ($type === '' ? '' : " · {$e($type)}") . '</h1><p>' . count($beds) . ' cama(s)</p>'
            . '<table><tr><th>Cama</th><th>Tipo</th></tr>' . ($rows ?: '<tr><td colspan="2">No hay camas disponibles: registrar en lista de espera.</td></tr>') . '</table></html>';
    }
}
